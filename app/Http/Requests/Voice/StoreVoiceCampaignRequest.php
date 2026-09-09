<?php

declare(strict_types=1);

namespace App\Http\Requests\Voice;

use App\Enums\Permission;
use App\Support\Scoped;
use Illuminate\Foundation\Http\FormRequest;

class StoreVoiceCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can(Permission::CampaignManage->value);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],

            // Audio clip — MediaLibrary enforces the real MIME/extension check and
            // the 16 MB audio cap; these are the formats it accepts.
            'audio' => ['required', 'file', 'max:16384', 'mimes:mp3,ogg,m4a,aac,amr'],

            'audience_type' => ['required', 'in:all,groups,contacts'],
            'group_ids' => ['array'],
            'group_ids.*' => [Scoped::exists('contact_groups')],
            'contact_ids' => ['array'],
            'contact_ids.*' => [Scoped::exists('contacts')],
            'exclude_group_ids' => ['array'],
            'exclude_group_ids.*' => [Scoped::exists('contact_groups')],

            'delay_seconds' => ['required', 'integer', 'min:0', 'max:120'],

            'mode' => ['required', 'in:now,schedule'],
            'scheduled_at' => ['nullable', 'required_if:mode,schedule', 'date', 'after:now'],

            'consent_confirmed' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'consent_confirmed.accepted' => 'You must confirm the recipients consented and the campaign follows TRAI / DND rules.',
            'audio.mimes' => 'The audio must be an MP3, OGG, M4A, AAC or AMR file.',
        ];
    }
}
