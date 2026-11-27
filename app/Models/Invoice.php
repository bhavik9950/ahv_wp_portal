<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    public const TYPE_SUBSCRIPTION = 'subscription';
    public const TYPE_MONTHLY_USAGE = 'monthly_usage';
    public const TYPE_CUSTOM = 'custom';

    public const STATUS_UNPAID = 'unpaid';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'invoice_number',
        'organization_id',
        'type',
        'client_name',
        'client_email',
        'client_address',
        'client_gst',
        'meta_billing_mode',
        'billing_month',
        'plan',
        'valid_from',
        'valid_until',
        'subtotal',
        'tax_percent',
        'tax_amount',
        'discount',
        'total_amount',
        'currency',
        'status',
        'payment_reference',
        'issue_date',
        'due_date',
        'paid_at',
        'sent_at',
        'sent_to_email',
        'notes',
        'metadata',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_until' => 'date',
            'issue_date' => 'date',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'sent_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'tax_percent' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class)->withoutGlobalScopes();
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_SUBSCRIPTION => 'Subscription / Membership',
            self::TYPE_MONTHLY_USAGE => 'Monthly Meta Usage',
            self::TYPE_CUSTOM => 'Custom Services Bill',
            default => ucfirst((string) $this->type),
        };
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public static function nextInvoiceNumber(): string
    {
        $prefix = 'AHV-'.now('Asia/Kolkata')->format('Ym').'-';
        $last = self::query()
            ->where('invoice_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('invoice_number');

        $seq = 1;
        if ($last && preg_match('/(\d+)$/', (string) $last, $m)) {
            $seq = ((int) $m[1]) + 1;
        }

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
