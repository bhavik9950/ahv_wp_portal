<?php

declare(strict_types=1);

namespace App\Http\Requests\Whatsapp;

use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWabaSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can(Permission::WabaManage->value);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'waba_id' => ['required', 'string', 'max:64', 'regex:/^[0-9]+$/'],
            'meta_business_account_id' => ['nullable', 'string', 'max:64', 'regex:/^[0-9]+$/'],
            'app_id' => ['nullable', 'string', 'max:64', 'regex:/^[0-9]+$/'],
            'api_version' => ['nullable', 'string', 'regex:/^v[0-9]{1,3}\.[0-9]{1,3}$/'],
            'default_country_code' => ['nullable', 'string', 'regex:/^[0-9]{1,4}$/'],

            // Secrets: blank = leave unchanged.
            'access_token' => ['nullable', 'string', 'min:20', 'max:1000'],
            'app_secret' => ['nullable', 'string', 'min:16', 'max:255'],
            'webhook_verify_token' => ['nullable', 'string', 'min:8', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'waba_id.regex' => 'The WhatsApp Business Account ID must be numeric.',
            'api_version.regex' => 'API version must look like "v22.0".',
        ];
    }
}
