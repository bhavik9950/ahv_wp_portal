<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $organization_id
 * @property string $disk
 * @property string $path
 * @property string $mime_type
 * @property string $original_name
 * @property int $size_bytes
 * @property string|null $meta_media_id
 * @property Carbon|null $meta_media_expires_at
 */
class Media extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<MediaFactory> */
    use HasFactory;

    use HasUlids;

    protected $table = 'media';

    protected $fillable = [
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
        'checksum_sha256',
        'meta_media_id',
        'meta_media_expires_at',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'meta_media_expires_at' => 'datetime',
        ];
    }

    public function category(): string
    {
        return match (true) {
            str_starts_with($this->mime_type, 'image/') => 'image',
            str_starts_with($this->mime_type, 'video/') => 'video',
            str_starts_with($this->mime_type, 'audio/') => 'audio',
            default => 'document',
        };
    }

    public function humanSize(): string
    {
        $b = $this->size_bytes;

        return $b >= 1_048_576 ? round($b / 1_048_576, 1).' MB' : round($b / 1024).' KB';
    }
}
