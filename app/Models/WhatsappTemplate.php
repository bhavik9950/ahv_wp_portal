<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TemplateStatus;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\WhatsappTemplateFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $organization_id
 * @property string $whatsapp_business_account_id
 * @property string $name
 * @property string $language
 * @property string|null $category
 * @property string $status
 * @property array<int, array<string, mixed>>|null $components
 * @property array<string, mixed>|null $raw_meta
 * @property string|null $rejection_reason
 * @property Carbon|null $last_synced_at
 */
class WhatsappTemplate extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<WhatsappTemplateFactory> */
    use HasFactory;

    use HasUlids, SoftDeletes;

    protected $fillable = [
        'whatsapp_business_account_id',
        'name',
        'language',
        'category',
        'components',
        'header_sample_media_id',
    ];

    protected function casts(): array
    {
        return [
            'components' => 'array',
            'raw_meta' => 'array',
            'last_synced_at' => 'datetime',
        ];
    }

    public function businessAccount(): BelongsTo
    {
        return $this->belongsTo(WhatsappBusinessAccount::class, 'whatsapp_business_account_id');
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function headerSampleMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'header_sample_media_id');
    }

    public function statusEnum(): TemplateStatus
    {
        return TemplateStatus::fromMeta($this->status);
    }

    public function isSendable(): bool
    {
        return $this->statusEnum()->isSendable();
    }

    /** True when the template's header expects a media parameter at send time. */
    public function hasMediaHeader(): bool
    {
        return in_array($this->headerFormat(), ['IMAGE', 'VIDEO', 'DOCUMENT'], true);
    }

    /** Distinct {{n}} placeholders referenced anywhere in the components, sorted. */
    public function variablePlaceholders(): array
    {
        $json = json_encode($this->components) ?: '';
        preg_match_all('/\{\{\s*(\d+)\s*\}\}/', $json, $matches);

        $numbers = array_values(array_unique(array_map('intval', $matches[1])));
        sort($numbers);

        return $numbers;
    }

    /**
     * One component by its Meta type (HEADER / BODY / FOOTER / BUTTONS).
     *
     * @return array<string, mixed>|null
     */
    public function component(string $type): ?array
    {
        foreach ($this->components ?? [] as $component) {
            if (strtoupper((string) ($component['type'] ?? '')) === strtoupper($type)) {
                return $component;
            }
        }

        return null;
    }

    /** TEXT / IMAGE / VIDEO / DOCUMENT / LOCATION, or null when there is no header. */
    public function headerFormat(): ?string
    {
        $header = $this->component('HEADER');

        return $header === null ? null : strtoupper((string) ($header['format'] ?? 'TEXT'));
    }

    public function headerText(): ?string
    {
        $header = $this->component('HEADER');

        return $this->headerFormat() === 'TEXT' ? ($header['text'] ?? null) : null;
    }

    public function bodyText(): ?string
    {
        return $this->component('BODY')['text'] ?? null;
    }

    public function footerText(): ?string
    {
        return $this->component('FOOTER')['text'] ?? null;
    }

    /**
     * Button labels in order (quick-reply, URL, phone, …).
     *
     * @return list<string>
     */
    public function buttonLabels(): array
    {
        $buttons = $this->component('BUTTONS')['buttons'] ?? [];

        return array_values(array_filter(array_map(
            static fn ($b) => is_array($b) ? trim((string) ($b['text'] ?? '')) : '',
            is_array($buttons) ? $buttons : [],
        )));
    }

    /**
     * Meta's example value for each body {{n}}, e.g. [1 => 'bhavi', 2 => 'ORD-42'].
     *
     * @return array<int, string>
     */
    public function bodyVariableExamples(): array
    {
        $example = $this->component('BODY')['example']['body_text'][0] ?? [];

        if (! is_array($example)) {
            return [];
        }

        $map = [];
        foreach (array_values($example) as $i => $value) {
            $map[$i + 1] = (string) $value;
        }

        return $map;
    }
}
