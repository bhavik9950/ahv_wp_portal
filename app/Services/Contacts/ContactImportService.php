<?php

declare(strict_types=1);

namespace App\Services\Contacts;

use App\Enums\OptInStatus;
use App\Models\Contact;
use App\Models\ContactImport;
use App\Services\Audit\AuditLogger;
use App\Services\WhatsApp\PhoneNumberNormalizer;
use App\Support\CurrentOrganization;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Csv\EscapeFormula;
use League\Csv\Reader;
use League\Csv\Writer;

/**
 * Two-phase CSV contact import:
 *   analyze()  — parse + validate every row, produce counts and a CSV of the
 *                invalid/duplicate rows for the user to download. Nothing written.
 *   commit()   — insert the valid, non-duplicate rows as contacts (chunked).
 *
 * Large files are processed row-by-row; never loaded whole into memory.
 */
final class ContactImportService
{
    /** Insert batch size — also how often the "imported so far" count updates. */
    private const CHUNK = 100;

    public function __construct(
        private readonly TenantContext $tenant,
        private readonly CurrentOrganization $currentOrg,
        private readonly AuditLogger $audit,
    ) {}

    public function analyze(ContactImport $import): void
    {
        $import->update(['status' => 'analyzing']);

        $normalizer = $this->normalizer();
        $map = $import->column_map ?? [];
        $options = $import->options ?? [];
        $groupId = $options['group_id'] ?? null;

        $reader = $this->reader($import);
        $header = $reader->getHeader();

        $errorRows = [];
        $seenInFile = [];
        $existing = Contact::query()->pluck('id', 'phone_e164');
        $alreadyInGroup = $groupId !== null
            ? DB::table('contact_group_contact')->where('contact_group_id', $groupId)->pluck('contact_id')->flip()
            : null;

        $total = $valid = $invalid = $duplicate = 0;

        foreach ($reader->getRecords() as $record) {
            $total++;
            $row = $this->mapRow($record, $map);

            // Let the preview page show the row count climbing while a big file scans.
            if ($total % self::CHUNK === 0) {
                ContactImport::query()->whereKey($import->getKey())->update([
                    'total_rows' => $total, 'valid_rows' => $valid,
                    'invalid_rows' => $invalid, 'duplicate_rows' => $duplicate,
                ]);
            }

            $parsed = $normalizer->parse($row['phone'] ?? null, $row['country_code'] ?? null);

            if ($parsed === null) {
                $invalid++;
                $errorRows[] = $record + ['_reason' => 'invalid phone number'];

                continue;
            }

            $e164 = $parsed['e164'];

            if (isset($seenInFile[$e164])) {
                $duplicate++;
                $errorRows[] = $record + ['_reason' => 'duplicate in file'];

                continue;
            }

            if ($existing->has($e164)) {
                $contactId = $existing->get($e164);
                if ($groupId === null || ($alreadyInGroup !== null && $alreadyInGroup->has($contactId))) {
                    $duplicate++;
                    $errorRows[] = $record + ['_reason' => $groupId !== null ? 'already in group' : 'already exists'];

                    continue;
                }
            }

            $seenInFile[$e164] = true;
            $valid++;
        }

        $reportPath = null;
        if ($errorRows !== []) {
            $reportPath = "imports/{$import->getKey()}-errors.csv";
            $csv = Writer::createFromString();
            $csv->addFormatter(new EscapeFormula);
            $csv->insertOne([...$header, '_reason']);
            $csv->insertAll($errorRows);
            Storage::disk($import->disk)->put($reportPath, $csv->toString());
        }

        $import->update([
            'status' => 'analyzed',
            'total_rows' => $total,
            'valid_rows' => $valid,
            'invalid_rows' => $invalid,
            'duplicate_rows' => $duplicate,
            'error_report_path' => $reportPath,
        ]);
    }

    public function commit(ContactImport $import): void
    {
        // The controller flips 'analyzed' → 'importing' when it dispatches, so
        // accept both; anything else (already completed / failed) is a no-op.
        if (! in_array($import->status, ['analyzed', 'importing'], true)) {
            return;
        }

        $import->update(['status' => 'importing', 'imported_rows' => 0]);

        $normalizer = $this->normalizer();
        $map = $import->column_map ?? [];
        $options = $import->options ?? [];
        $groupId = $options['group_id'] ?? null;
        $optInSource = $options['opt_in_source'] ?? 'csv_import';
        $markOptedIn = (bool) ($options['mark_opted_in'] ?? false);

        $existing = Contact::query()->pluck('id', 'phone_e164');
        $seen = [];
        $imported = 0;
        $batch = [];
        $existingGroupLinks = [];

        foreach ($this->reader($import)->getRecords() as $record) {
            $row = $this->mapRow($record, $map);
            $parsed = $normalizer->parse($row['phone'] ?? null, $row['country_code'] ?? null);
            if ($parsed === null) {
                continue;
            }

            $e164 = $parsed['e164'];
            if (isset($seen[$e164])) {
                continue;
            }
            $seen[$e164] = true;

            if ($existing->has($e164)) {
                if ($groupId !== null) {
                    $existingGroupLinks[] = $existing->get($e164);
                    if (count($existingGroupLinks) >= self::CHUNK) {
                        $imported += $this->linkExistingToGroup($groupId, $existingGroupLinks);
                        $existingGroupLinks = [];
                        ContactImport::query()->whereKey($import->getKey())->update(['imported_rows' => $imported]);
                    }
                }
                continue;
            }

            $batch[] = [
                'id' => (string) Str::ulid(),
                'organization_id' => $this->tenant->id(),
                'name' => $row['name'] ?? null,
                'email' => $row['email'] ?? null,
                'country_code' => $parsed['country_code'],
                'phone_e164' => $e164,
                'phone_hash' => PhoneNumberNormalizer::hash($e164),
                'custom_fields' => json_encode($this->customFields($record, $map)),
                'opt_in_status' => $markOptedIn ? OptInStatus::OptedIn->value : OptInStatus::Unknown->value,
                'opted_in_at' => $markOptedIn ? now() : null,
                'opt_in_source' => $markOptedIn ? $optInSource : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= self::CHUNK) {
                $imported += $this->flush($batch, $groupId);
                $batch = [];
                // Live progress for the auto-refreshing preview page.
                ContactImport::query()->whereKey($import->getKey())->update(['imported_rows' => $imported]);
            }
        }

        if ($batch !== []) {
            $imported += $this->flush($batch, $groupId);
        }

        if ($groupId !== null && $existingGroupLinks !== []) {
            $imported += $this->linkExistingToGroup($groupId, $existingGroupLinks);
        }

        $import->update(['status' => 'completed', 'imported_rows' => $imported]);

        $this->audit->log('contact.imported', $import, [
            'imported' => $imported,
            'total' => $import->total_rows,
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $batch
     */
    private function flush(array $batch, ?string $groupId): int
    {
        return DB::transaction(function () use ($batch, $groupId): int {
            Contact::query()->insert($batch);

            if ($groupId !== null) {
                DB::table('contact_group_contact')->insertOrIgnore(array_map(fn ($row) => [
                    'contact_group_id' => $groupId,
                    'contact_id' => $row['id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $batch));
            }

            return count($batch);
        });
    }

    /**
     * @param  list<string>  $contactIds
     */
    private function linkExistingToGroup(string $groupId, array $contactIds): int
    {
        $rows = array_map(fn ($id) => [
            'contact_group_id' => $groupId,
            'contact_id' => $id,
            'created_at' => now(),
            'updated_at' => now(),
        ], $contactIds);

        return (int) DB::table('contact_group_contact')->insertOrIgnore($rows);
    }

    private function reader(ContactImport $import): Reader
    {
        $contents = Storage::disk($import->disk)->get($import->path) ?? '';
        $reader = Reader::createFromString($contents);
        $reader->setHeaderOffset(0);

        return $reader;
    }

    /**
     * @param  array<string, string>  $record
     * @param  array<string, string>  $map  header => field
     * @return array<string, string>
     */
    private function mapRow(array $record, array $map): array
    {
        $out = [];
        foreach ($map as $header => $field) {
            if ($field !== '' && isset($record[$header])) {
                $out[$field] = trim((string) $record[$header]);
            }
        }

        return $out;
    }

    /**
     * @param  array<string, string>  $record
     * @param  array<string, string>  $map
     * @return array<string, string>
     */
    private function customFields(array $record, array $map): array
    {
        $mapped = array_keys($map);
        $custom = [];
        foreach ($record as $header => $value) {
            if (! in_array($header, $mapped, true) && trim((string) $value) !== '') {
                $custom[$header] = trim((string) $value);
            }
        }

        return $custom;
    }

    private function normalizer(): PhoneNumberNormalizer
    {
        return PhoneNumberNormalizer::make($this->currentOrg->resolve()?->settings['default_country_code'] ?? null);
    }
}
