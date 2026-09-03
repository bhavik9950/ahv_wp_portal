<?php

declare(strict_types=1);

namespace App\Http\Requests\Contacts;

use App\Enums\Permission;
use App\Support\Scoped;
use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can(Permission::ContactManage->value);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:25'],
            'country_code' => ['nullable', 'string', 'regex:/^[0-9]{1,4}$/'],
            'email' => ['nullable', 'email', 'max:180'],
            'custom_fields' => ['nullable', 'array'],
            'custom_fields.*' => ['nullable', 'string', 'max:500'],
            'groups' => ['nullable', 'array'],
            'groups.*' => ['string', Scoped::exists('contact_groups')],
        ];
    }
}
