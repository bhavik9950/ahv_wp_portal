<?php

declare(strict_types=1);

namespace App\Services\Contacts;

use App\Enums\OptInStatus;
use App\Models\Contact;
use App\Models\OptInRecord;
use App\Services\Audit\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Records consent changes in the append-only opt_in_records ledger and keeps the
 * denormalised opt_in_status on the contact in sync.
 *
 * The campaign / send pipeline consults `isOptedOut()` before sending MARKETING
 * templates. Opt-out always wins and is never silently overridden.
 */
final class OptInService
{
    public function __construct(
        private readonly TenantContext $tenant,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{source?:string, campaign_id?:string, reference?:string, note?:string}  $context
     */
    public function optIn(Contact $contact, array $context = []): void
    {
        $this->record($contact, OptInStatus::OptedIn, $context);
    }

    /**
     * @param  array{source?:string, campaign_id?:string, reference?:string, note?:string}  $context
     */
    public function optOut(Contact $contact, array $context = []): void
    {
        $this->record($contact, OptInStatus::OptedOut, $context);
    }

    /**
     * Opt-out by phone number even if no Contact row exists yet (e.g. inbound
     * "STOP" message). Creates a ledger entry keyed on the phone.
     */
    public function optOutByPhone(string $phoneE164, array $context = []): void
    {
        $contact = Contact::query()->where('phone_e164', $phoneE164)->first();

        if ($contact !== null) {
            $this->optOut($contact, $context);

            return;
        }

        OptInRecord::create([
            'organization_id' => $this->tenant->id(),
            'phone_e164' => $phoneE164,
            'status' => 'opt_out',
            'source' => $context['source'] ?? 'inbound',
            'campaign_id' => $context['campaign_id'] ?? null,
            'reference' => $context['reference'] ?? null,
            'note' => $context['note'] ?? null,
            'recorded_by' => Auth::id(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function record(Contact $contact, OptInStatus $status, array $context): void
    {
        DB::transaction(function () use ($contact, $status, $context): void {
            OptInRecord::create([
                'organization_id' => $contact->organization_id,
                'contact_id' => $contact->getKey(),
                'phone_e164' => $contact->phone_e164,
                'status' => $status === OptInStatus::OptedIn ? 'opt_in' : 'opt_out',
                'source' => $context['source'] ?? 'manual',
                'campaign_id' => $context['campaign_id'] ?? null,
                'reference' => $context['reference'] ?? null,
                'note' => $context['note'] ?? null,
                'recorded_by' => Auth::id(),
            ]);

            $contact->forceFill([
                'opt_in_status' => $status->value,
                'opted_in_at' => $status === OptInStatus::OptedIn ? now() : $contact->opted_in_at,
                'opt_in_source' => $context['source'] ?? $contact->opt_in_source,
                'opted_out_at' => $status === OptInStatus::OptedOut ? now() : null,
            ])->save();
        });

        $this->audit->log(
            $status === OptInStatus::OptedIn ? 'contact.opted_in' : 'contact.opted_out',
            $contact,
            ['source' => $context['source'] ?? 'manual'],
        );
    }
}
