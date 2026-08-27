<?php

declare(strict_types=1);

namespace App\Services\Contacts;

use App\Models\Contact;
use App\Services\Audit\AuditLogger;
use App\Services\WhatsApp\PhoneNumberNormalizer;
use App\Support\CurrentOrganization;
use RuntimeException;

final class ContactService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly CurrentOrganization $currentOrg,
    ) {}

    /**
     * @param  array{name?:string, phone?:string, country_code?:string, email?:string, custom_fields?:array<string,mixed>}  $data
     */
    public function create(array $data): Contact
    {
        $parsed = $this->normalizer()->parse($data['phone'] ?? null, $data['country_code'] ?? null);

        if ($parsed === null) {
            throw new RuntimeException('That phone number is not a valid international number.');
        }

        if (Contact::query()->where('phone_e164', $parsed['e164'])->exists()) {
            throw new DuplicateContactException($parsed['e164']);
        }

        $contact = new Contact;
        $contact->forceFill([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'country_code' => $parsed['country_code'],
            'phone_e164' => $parsed['e164'],
            'phone_hash' => PhoneNumberNormalizer::hash($parsed['e164']),
            'custom_fields' => $data['custom_fields'] ?? [],
        ]);
        $contact->save();

        $this->audit->log('contact.created', $contact);

        return $contact;
    }

    /**
     * @param  array{name?:string, phone?:string, country_code?:string, email?:string, custom_fields?:array<string,mixed>}  $data
     */
    public function update(Contact $contact, array $data): Contact
    {
        if (array_key_exists('phone', $data) && filled($data['phone'])) {
            $parsed = $this->normalizer()->parse($data['phone'], $data['country_code'] ?? $contact->country_code);
            if ($parsed === null) {
                throw new RuntimeException('That phone number is not a valid international number.');
            }

            $clash = Contact::query()
                ->where('phone_e164', $parsed['e164'])
                ->whereKeyNot($contact->getKey())
                ->exists();

            if ($clash) {
                throw new DuplicateContactException($parsed['e164']);
            }

            $contact->forceFill([
                'phone_e164' => $parsed['e164'],
                'phone_hash' => PhoneNumberNormalizer::hash($parsed['e164']),
                'country_code' => $parsed['country_code'],
            ]);
        }

        $contact->fill([
            'name' => $data['name'] ?? $contact->name,
            'email' => $data['email'] ?? $contact->email,
            'custom_fields' => $data['custom_fields'] ?? $contact->custom_fields,
        ]);
        $contact->save();

        $this->audit->log('contact.updated', $contact);

        return $contact;
    }

    public function delete(Contact $contact): void
    {
        $this->audit->log('contact.deleted', $contact, ['phone' => substr($contact->phone_e164, -4)]);
        $contact->delete();
    }

    private function normalizer(): PhoneNumberNormalizer
    {
        return PhoneNumberNormalizer::make($this->currentOrg->resolve()?->settings['default_country_code'] ?? null);
    }
}
