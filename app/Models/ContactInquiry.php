<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $phone
 * @property string|null $email
 * @property string|null $business_name
 * @property string $service_interested
 * @property string|null $message
 * @property string $status
 * @property string|null $admin_notes
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ContactInquiry extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'business_name',
        'service_interested',
        'message',
        'status',
        'admin_notes',
        'ip_address',
        'user_agent',
    ];
}
