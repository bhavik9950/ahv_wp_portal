<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BotConversationMode;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $organization_id
 * @property BotConversationMode $mode
 * @property string $wa_phone
 * @property int $replies_today
 * @property Carbon|null $replies_today_on
 * @property Carbon|null $last_inbound_at
 * @property Carbon|null $last_bot_reply_at
 * @property Carbon|null $handoff_at
 */
class BotConversation extends Model
{
    use BelongsToOrganization, HasUlids;

    protected $fillable = [
        'contact_id',
        'wa_phone',
        'wa_phone_hash',
        'mode',
        'last_inbound_at',
        'last_bot_reply_at',
        'replies_today',
        'replies_today_on',
        'handoff_reason',
        'handoff_at',
    ];

    protected function casts(): array
    {
        return [
            'mode' => BotConversationMode::class,
            'last_inbound_at' => 'datetime',
            'last_bot_reply_at' => 'datetime',
            'handoff_at' => 'datetime',
            'replies_today_on' => 'date',
        ];
    }

    /** @return BelongsTo<Contact, $this> */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /** @return HasMany<BotMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(BotMessage::class)->orderBy('created_at');
    }

    /** Replies used today, resetting the counter when the date rolls over. */
    public function repliesUsedToday(): int
    {
        if ($this->replies_today_on?->isToday() !== true) {
            return 0;
        }

        return $this->replies_today;
    }

    public function recordBotReply(): void
    {
        $today = now()->toDateString();
        $count = $this->replies_today_on?->isToday() === true ? $this->replies_today + 1 : 1;

        $this->forceFill([
            'replies_today' => $count,
            'replies_today_on' => $today,
            'last_bot_reply_at' => now(),
        ])->save();
    }
}
