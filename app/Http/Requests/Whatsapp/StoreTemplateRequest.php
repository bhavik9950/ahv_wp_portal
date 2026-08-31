<?php

declare(strict_types=1);

namespace App\Http\Requests\Whatsapp;

use App\Enums\Permission;
use App\Services\WhatsApp\Templates\TemplateComposer;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can(Permission::TemplateSubmit->value);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:512', 'regex:/^[a-z0-9_]+$/'],
            'language' => ['required', 'string', 'max:15'],
            'category' => ['required', 'in:MARKETING,UTILITY,AUTHENTICATION'],

            'header_type' => ['nullable', 'in:none,text,image,video,document'],
            'header_text' => ['nullable', 'required_if:header_type,text', 'string', 'max:60'],
            // A sample file Meta uses for review (not sent to customers). Caps:
            // image 5 MB, video 16 MB, document 100 MB.
            'sample_media' => [
                'nullable',
                'required_if:header_type,image,video,document',
                'file',
                'max:102400',
                'mimes:jpg,jpeg,png,mp4,3gp,pdf',
            ],

            'body' => ['required', 'string', 'max:1024'],
            'footer' => ['nullable', 'string', 'max:60'],

            'buttons' => ['nullable', 'array', 'max:10'],
            'buttons.*.type' => ['required_with:buttons', 'in:quick_reply,url,phone'],
            'buttons.*.text' => ['required_with:buttons', 'string', 'max:25'],
            'buttons.*.url' => ['nullable', 'required_if:buttons.*.type,url', 'url', 'max:2000'],
            'buttons.*.phone_number' => ['nullable', 'required_if:buttons.*.type,phone', 'string', 'max:20'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $errors = app(TemplateComposer::class)->structuralErrors((string) $this->input('body', ''));
            foreach ($errors as $message) {
                $v->errors()->add('body', $message);
            }

            $sample = $this->file('sample_media');
            if ($sample !== null) {
                $capKb = match ($this->input('header_type')) {
                    'image' => 5 * 1024,
                    'video' => 16 * 1024,
                    default => 100 * 1024,
                };
                if ($sample->getSize() / 1024 > $capKb) {
                    $v->errors()->add('sample_media', 'The sample file is larger than Meta allows for this header type.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Template name may only contain lowercase letters, numbers and underscores.',
        ];
    }
}
