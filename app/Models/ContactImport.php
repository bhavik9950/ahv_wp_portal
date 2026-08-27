<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $organization_id
 * @property string $status
 * @property string $disk
 * @property string $path
 * @property array<string, string>|null $column_map
 * @property array<string, mixed>|null $options
 */
class ContactImport extends Model
{
    use BelongsToOrganization, HasUlids;

    // Internal model — written only by the import controller/service/jobs.
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'column_map' => 'array',
            'options' => 'array',
        ];
    }

    public function isAnalyzed(): bool
    {
        return $this->status === 'analyzed';
    }

    public function isFinished(): bool
    {
        return in_array($this->status, ['completed', 'failed'], true);
    }
}
