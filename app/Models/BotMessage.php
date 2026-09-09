<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $organization_id
 * @property string $role
 * @property string $content
 */
class BotMessage extends Model
{
    use BelongsToOrganization, HasUlids;

    protected $fillable = [
        'bot_conversation_id',
        'message_id',
        'role',
        'content',
        'model',
        'tokens_in',
        'tokens_out',
    ];

    /** @return BelongsTo<BotConversation, $this> */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(BotConversation::class, 'bot_conversation_id');
    }
}
