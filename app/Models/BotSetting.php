<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $organization_id
 * @property bool $enabled
 * @property string $system_prompt
 * @property string|null $model
 * @property int $daily_reply_cap
 * @property list<string>|null $handoff_keywords
 * @property string $fallback_message
 */
class BotSetting extends Model
{
    use BelongsToOrganization, HasUlids;

    protected $fillable = [
        'enabled',
        'system_prompt',
        'model',
        'daily_reply_cap',
        'handoff_keywords',
        'fallback_message',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'handoff_keywords' => 'array',
            'daily_reply_cap' => 'integer',
        ];
    }

    public const DEFAULT_SYSTEM_PROMPT = <<<'PROMPT'
        You are the WhatsApp assistant for AH&V Software (ahvsoftware.com).
        AH&V builds custom software, web apps, WordPress sites, Android & iOS apps,
        SaaS products and CRMs, and runs social-media management and SEO.

        Your job: answer the customer's question, understand their requirement, and
        move them toward a short discovery call with the team.

        Rules:
        - Never quote a price, a fixed timeline, or promise a delivery date. Say the
          team will share a tailored quote after understanding the scope.
        - Only state facts you are sure of. If you don't know, say a team member will
          follow up — do not guess.
        - Keep replies short (2-4 sentences), WhatsApp tone, and mirror the customer's
          language (English / Hindi / Hinglish).
        - If the customer is angry, asks for a human, or the matter is billing / legal /
          urgent support, say a team member will take over shortly and stop.
        - Never send links, offers or marketing the customer didn't ask for.
        PROMPT;

    public const DEFAULT_HANDOFF_KEYWORDS = [
        'agent', 'human', 'representative', 'call me', 'complaint', 'refund',
        'talk to someone', 'baat karni', 'insaan', 'manager',
    ];

    public const DEFAULT_FALLBACK = 'Thanks for your message — a team member will get back to you shortly.';
}
