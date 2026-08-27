<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Models\WhatsappBusinessAccount;
use App\Models\WhatsappPhoneNumber;
use App\Services\Audit\AuditLogger;
use App\Services\WhatsApp\Data\ConnectionCheck;
use App\Services\WhatsApp\Data\WabaCredentials;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Create / update the organization's WhatsApp Business Account configuration,
 * run the Meta connection validators, and sync its phone numbers.
 *
 * Secret fields (access_token, app_secret, webhook_verify_token) are only
 * written when a non-empty value is supplied — the settings form shows masked
 * placeholders and submits blanks to mean "leave unchanged".
 */
final class WabaConfigurationService
{
    /** @var list<string> */
    private const SECRET_FIELDS = ['access_token', 'app_secret', 'webhook_verify_token'];

    public function __construct(
        private readonly WhatsAppManager $manager,
        private readonly TenantContext $tenant,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function upsert(array $data, ?WhatsappBusinessAccount $account = null): WhatsappBusinessAccount
    {
        $account ??= new WhatsappBusinessAccount;
        $isNew = ! $account->exists;

        // .env bootstrap keys, used to seed a brand-new account.
        $bootstrapMap = [
            'access_token' => 'access_token',
            'app_secret' => 'app_secret',
            'webhook_verify_token' => 'webhook_verify_token',
        ];
        $boot = (array) config('services.whatsapp.bootstrap');

        foreach (self::SECRET_FIELDS as $field) {
            if (filled($data[$field] ?? null)) {
                continue;
            }
            // Blank on a NEW account → take the .env value if present.
            if ($isNew && filled($boot[$bootstrapMap[$field]] ?? null)) {
                $data[$field] = $boot[$bootstrapMap[$field]];
            } else {
                unset($data[$field]); // blank on update → keep the stored value
            }
        }

        if ($isNew && blank($data['waba_id'] ?? null) && filled($boot['business_account_id'] ?? null)) {
            $data['waba_id'] = $boot['business_account_id'];
        }
        if ($isNew && blank($data['app_id'] ?? null) && filled($boot['app_id'] ?? null)) {
            $data['app_id'] = $boot['app_id'];
        }
        if ($isNew && blank($data['meta_business_account_id'] ?? null) && filled($boot['meta_business_id'] ?? null)) {
            $data['meta_business_account_id'] = $boot['meta_business_id'];
        }

        $account->fill([
            'name' => $data['name'] ?? $account->name ?? 'WhatsApp Business Account',
            'meta_business_account_id' => $data['meta_business_account_id'] ?? null,
            'waba_id' => $data['waba_id'],
            'app_id' => $data['app_id'] ?? null,
            'api_version' => $data['api_version'] ?? null,
            'default_country_code' => $data['default_country_code'] ?? null,
        ]);

        foreach (self::SECRET_FIELDS as $field) {
            if (array_key_exists($field, $data)) {
                $account->{$field} = $data[$field];
            }
        }

        if ($isNew) {
            $account->organization_id = $this->tenant->id();
        }

        $account->save();

        $this->audit->log($isNew ? 'waba.created' : 'waba.updated', $account, [
            'waba_id' => $account->waba_id,
            'secret_fields_changed' => array_values(array_intersect(self::SECRET_FIELDS, array_keys($data))),
        ]);

        return $account;
    }

    /**
     * Run all connection validators and persist the resulting status snapshot.
     *
     * @return list<ConnectionCheck>
     */
    public function runConnectionChecks(WhatsappBusinessAccount $account): array
    {
        $defaultNumber = $account->phoneNumbers()->where('is_default', true)->first()
            ?? $account->phoneNumbers()->first();

        $creds = $this->manager->credentialsFor($account, $defaultNumber);

        // Not synced any phone numbers yet? Fall back to the .env bootstrap phone
        // id so the "Validate Phone Number" check can still run.
        if ($defaultNumber === null && filled(config('services.whatsapp.bootstrap.phone_number_id'))) {
            $creds = WabaCredentials::fromModel($account, (string) config('services.whatsapp.bootstrap.phone_number_id'));
        }

        try {
            $checks = $this->manager->driver()->runConnectionChecks($creds);
        } catch (Throwable $e) {
            $checks = [ConnectionCheck::fail('connection', 'Test Connection', $e->getMessage())];
        }

        $byKey = collect($checks)->keyBy('key');
        $allPassed = collect($checks)->every(fn (ConnectionCheck $c) => $c->passed);

        $account->forceFill([
            'connection_status' => $allPassed ? 'connected' : 'error',
            'token_status' => match (true) {
                ($byKey['token']->passed ?? false) => 'valid',
                $byKey->has('token') => 'invalid',
                default => 'unknown',
            },
            'token_last_checked_at' => now(),
            'last_error' => $allPassed ? null : collect($checks)
                ->reject(fn (ConnectionCheck $c) => $c->passed)
                ->map(fn (ConnectionCheck $c) => [$c->key => $c->message])
                ->values()
                ->all(),
        ])->save();

        $this->audit->log('waba.connection_checked', $account, [
            'result' => $allPassed ? 'connected' : 'error',
        ]);

        return $checks;
    }

    /**
     * Pull the phone numbers registered on the WABA into local records.
     */
    public function syncPhoneNumbers(WhatsappBusinessAccount $account): int
    {
        $creds = $this->manager->credentialsFor($account);
        $numbers = $this->manager->driver()->listPhoneNumbers($creds);

        $count = DB::transaction(function () use ($account, $numbers): int {
            $seen = [];

            foreach ($numbers as $raw) {
                $phoneNumberId = (string) ($raw['id'] ?? '');
                if ($phoneNumberId === '') {
                    continue;
                }

                $number = $account->phoneNumbers()->firstOrNew(['phone_number_id' => $phoneNumberId]);
                $number->organization_id = $account->organization_id;
                $number->fill([
                    'display_phone_number' => $raw['display_phone_number'] ?? null,
                    'verified_name' => $raw['verified_name'] ?? null,
                    'quality_rating' => $raw['quality_rating'] ?? null,
                    'messaging_limit_tier' => $raw['messaging_limit_tier'] ?? null,
                ]);
                $number->status = 'available';
                $number->last_synced_at = now();
                $number->save();

                $seen[] = $number->getKey();
            }

            // First number becomes default if none set yet.
            if ($account->phoneNumbers()->where('is_default', true)->doesntExist()) {
                $account->phoneNumbers()->orderBy('created_at')->first()?->update(['is_default' => true]);
            }

            return count($seen);
        });

        $this->audit->log('waba.phone_numbers_synced', $account, ['count' => $count]);

        return $count;
    }

    public function setDefaultPhoneNumber(WhatsappPhoneNumber $number): void
    {
        DB::transaction(function () use ($number): void {
            WhatsappPhoneNumber::query()
                ->where('whatsapp_business_account_id', $number->whatsapp_business_account_id)
                ->update(['is_default' => false]);

            $number->forceFill(['is_default' => true])->save();
        });

        $this->audit->log('waba.default_phone_number_set', $number, [
            'phone_number_id' => $number->phone_number_id,
        ]);
    }
}
