<?php

declare(strict_types=1);

namespace App\Http\Requests\Campaigns;

use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCampaignRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'whatsapp_phone_number_id' => ['sometimes', 'nullable', 'string', 'exists:whatsapp_phone_numbers,id'],
            'template_id' => ['sometimes', 'nullable', 'string', 'exists:whatsapp_templates,id'],
            'media_id' => ['sometimes', 'nullable', 'string', 'exists:media,id'],
            'send_delay_seconds' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:60'],
            'timezone' => ['sometimes', 'string', 'timezone'],

            'variable_map' => ['sometimes', 'array'],
            'variable_map.*.type' => ['required_with:variable_map', 'in:static,contact_field,custom_field'],
            'variable_map.*.value' => ['nullable', 'string', 'max:1024'],

            'audience_filter' => ['sometimes', 'array'],
            'audience_filter.type' => ['required_with:audience_filter', 'in:all,groups,contacts'],
            'audience_filter.group_ids' => ['nullable', 'array'],
            'audience_filter.group_ids.*' => ['string', 'exists:contact_groups,id'],
            'audience_filter.contact_ids' => ['nullable', 'array'],
            'audience_filter.contact_ids.*' => ['string', 'exists:contacts,id'],
            'audience_filter.exclude_group_ids' => ['nullable', 'array'],
            'audience_filter.exclude_group_ids.*' => ['string', 'exists:contact_groups,id'],
            'audience_filter.opt_in' => ['nullable', 'in:opted_in,any'],
        ];
    }
}
