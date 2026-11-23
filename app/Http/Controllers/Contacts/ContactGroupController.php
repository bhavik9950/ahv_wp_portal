<?php

declare(strict_types=1);

namespace App\Http\Controllers\Contacts;

use App\Enums\OptInStatus;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactGroup;
use App\Services\WhatsApp\PhoneNumberNormalizer;
use App\Support\CurrentOrganization;
use App\Support\Scoped;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ContactGroupController extends Controller
{
    public function index(): View
    {
        $this->authorizePermission(Permission::ContactView);

        return view('contacts.groups.index', [
            'groups' => ContactGroup::query()->withCount('contacts')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission(Permission::ContactManage);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        ContactGroup::query()->firstOrCreate(['name' => $data['name']], ['description' => $data['description'] ?? null]);

        return back()->with('flash_notify', ['type' => 'success', 'message' => 'Group created.']);
    }

    public function update(Request $request, ContactGroup $group): RedirectResponse
    {
        $this->authorizePermission(Permission::ContactManage);

        $group->update($request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:255'],
        ]));

        return back()->with('flash_notify', ['type' => 'success', 'message' => 'Group updated.']);
    }

    public function destroy(ContactGroup $group): RedirectResponse
    {
        $this->authorizePermission(Permission::ContactManage);

        $group->contacts()->detach();
        $group->delete();

        return back()->with('flash_notify', ['type' => 'success', 'message' => 'Group deleted.']);
    }

    /**
     * Quickly import or paste contacts directly into this group.
     */
    public function addContacts(Request $request, ContactGroup $group): RedirectResponse
    {
        $this->authorizePermission(Permission::ContactManage);

        $request->validate([
            'numbers' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'max:10240'],
        ]);

        $rawNumbers = [];

        // 1. Parse pasted text (comma or newline separated)
        if ($request->filled('numbers')) {
            $lines = preg_split('/[\r\n,]+/', (string) $request->input('numbers'), -1, PREG_SPLIT_NO_EMPTY);
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed !== '') {
                    $rawNumbers[] = $trimmed;
                }
            }
        }

        // 2. Parse uploaded file (CSV or plain text)
        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            $path = $request->file('file')->getRealPath();
            $handle = fopen($path, 'r');
            if ($handle !== false) {
                $firstRow = fgetcsv($handle);
                $phoneColIndex = 0;
                if ($firstRow !== false) {
                    $hasPhoneHeader = false;
                    foreach ($firstRow as $idx => $col) {
                        $cleanCol = strtolower(trim((string) $col, "\xEF\xBB\xBF \t\n\r\0\x0B\"'"));
                        if (in_array($cleanCol, ['phone', 'phone_number', 'mobile', 'number', 'recipient', 'wa_id', 'contact'], true)) {
                            $phoneColIndex = $idx;
                            $hasPhoneHeader = true;
                            break;
                        }
                    }
                    if (! $hasPhoneHeader && isset($firstRow[$phoneColIndex])) {
                        $val = trim((string) $firstRow[$phoneColIndex]);
                        if ($val !== '') {
                            $rawNumbers[] = $val;
                        }
                    }
                }
                while (($row = fgetcsv($handle)) !== false) {
                    if (isset($row[$phoneColIndex])) {
                        $val = trim((string) $row[$phoneColIndex]);
                        if ($val !== '') {
                            $rawNumbers[] = $val;
                        }
                    }
                }
                fclose($handle);
            }
        }

        if (empty($rawNumbers)) {
            return back()->with('flash_notify', [
                'type' => 'error',
                'message' => 'No phone numbers provided. Please paste numbers or upload a CSV file.',
            ]);
        }

        $currentOrg = app(CurrentOrganization::class)->resolve();
        $orgId = $group->organization_id;
        $normalizer = PhoneNumberNormalizer::make($currentOrg?->settings['default_country_code'] ?? null);

        $validE164 = [];
        foreach ($rawNumbers as $raw) {
            $parsed = $normalizer->parse($raw);
            if ($parsed !== null) {
                $validE164[$parsed['e164']] = $parsed;
            }
        }

        if (empty($validE164)) {
            return back()->with('flash_notify', [
                'type' => 'error',
                'message' => 'No valid phone numbers could be extracted.',
            ]);
        }

        $existing = Contact::query()
            ->whereIn('phone_e164', array_keys($validE164))
            ->pluck('id', 'phone_e164');

        $contactIds = [];
        $newContacts = [];

        foreach ($validE164 as $e164 => $parsed) {
            $e164Str = (string) $parsed['e164'];
            if ($existing->has($e164Str)) {
                $contactIds[] = $existing->get($e164Str);
            } else {
                $newId = (string) Str::ulid();
                $newContacts[] = [
                    'id' => $newId,
                    'organization_id' => $orgId,
                    'name' => null,
                    'country_code' => $parsed['country_code'],
                    'phone_e164' => $e164Str,
                    'phone_hash' => PhoneNumberNormalizer::hash($e164Str),
                    'opt_in_status' => OptInStatus::OptedIn->value,
                    'opted_in_at' => now(),
                    'opt_in_source' => 'group_add',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $contactIds[] = $newId;
            }
        }

        if (! empty($newContacts)) {
            foreach (array_chunk($newContacts, 500) as $chunk) {
                Contact::query()->insert($chunk);
            }
        }

        $links = array_map(fn ($cid) => [
            'contact_group_id' => $group->id,
            'contact_id' => $cid,
            'created_at' => now(),
            'updated_at' => now(),
        ], $contactIds);

        $addedCount = 0;
        foreach (array_chunk($links, 500) as $chunk) {
            $addedCount += (int) DB::table('contact_group_contact')->insertOrIgnore($chunk);
        }

        return back()->with('flash_notify', [
            'type' => 'success',
            'message' => count($contactIds)." contact(s) processed. {$addedCount} newly added to group “{$group->name}”.",
        ]);
    }

    /**
     * Bulk assign / remove contacts to a group from the contacts list.
     */
    public function assign(Request $request): RedirectResponse
    {
        $this->authorizePermission(Permission::ContactManage);

        $data = $request->validate([
            'group_id' => ['nullable', 'required_without:new_group_name', 'string', Scoped::exists('contact_groups')],
            'new_group_name' => ['nullable', 'required_without:group_id', 'string', 'max:80'],
            'contact_ids' => ['required', 'array', 'min:1', 'max:5000'],
            'contact_ids.*' => ['string', Scoped::exists('contacts')],
            'action' => ['required', 'in:add,remove'],
        ]);

        $group = filled($data['group_id'] ?? null)
            ? ContactGroup::query()->findOrFail($data['group_id'])
            : ContactGroup::query()->firstOrCreate(['name' => trim($data['new_group_name'])]);

        $ids = Contact::query()->whereKey($data['contact_ids'])->pluck('id')->all();

        $data['action'] === 'add'
            ? $group->contacts()->syncWithoutDetaching($ids)
            : $group->contacts()->detach($ids);

        return back()->with('flash_notify', [
            'type' => 'success',
            'message' => count($ids).' contact(s) '.($data['action'] === 'add' ? 'added to' : 'removed from').' “'.$group->name.'”.',
        ]);
    }

    private function authorizePermission(Permission $permission): void
    {
        abort_unless((bool) request()->user()?->can($permission->value), 403);
    }
}
