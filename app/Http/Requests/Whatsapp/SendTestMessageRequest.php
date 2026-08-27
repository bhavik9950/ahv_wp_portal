<?php

declare(strict_types=1);

namespace App\Http\Requests\Whatsapp;

use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;

class SendTestMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can(Permission::MessageSend->value);
    }

    protected function prepareForValidation(): void
    {
        $recipients = collect(preg_split('/[\s,;]+/', (string) $this->input('recipients', '')))
            ->map(fn ($r) => preg_replace('/[^0-9]/', '', (string) $r))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->merge(['recipients' => $recipients]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'whatsapp_phone_number_id' => ['required', 'string', 'exists:whatsapp_phone_numbers,id'],
            'mode' => ['required', 'in:text,template'],
            'recipients' => ['required', 'array', 'min:1', 'max:5'],
            'recipients.*' => ['string', 'regex:/^[0-9]{8,15}$/'],

            'body' => ['required_if:mode,text', 'nullable', 'string', 'max:4096'],

            'template_id' => ['required_if:mode,template', 'nullable', 'string', 'exists:whatsapp_templates,id'],
            'variables' => ['nullable', 'array'],
            // WhatsApp rejects empty template parameters — every supplied
            // variable must have a value.
            'variables.*' => ['required', 'string', 'max:1024'],
        ];
    }

    public function messages(): array
    {
        return [
            'recipients.max' => 'Test sends are limited to 5 numbers at a time.',
            'recipients.*.regex' => 'Each recipient must be digits only in international format (e.g. 919876543210).',
            'variables.*.required' => 'Fill in every template variable — WhatsApp does not accept empty values.',
        ];
    }
}
