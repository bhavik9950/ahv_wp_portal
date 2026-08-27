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

class WhatsappTemplate extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<WhatsappTemplateFactory> */
    use HasFactory;

    use HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'language',
        'category',
        'components',
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

    public function statusEnum(): TemplateStatus
    {
        return TemplateStatus::fromMeta($this->status);
    }

    public function isSendable(): bool
    {
        return $this->statusEnum()->isSendable();
    }

    /** Distinct {{n}} placeholders referenced anywhere in the components. */
    public function variablePlaceholders(): array
    {
        $json = json_encode($this->components) ?: '';
        preg_match_all('/\{\{\s*(\d+)\s*\}\}/', $json, $matches);

        return array_values(array_unique(array_map('intval', $matches[1])));
    }
}
