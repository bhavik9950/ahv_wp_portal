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

    public function isImporting(): bool
    {
        return $this->status === 'importing';
    }

    public function isBusy(): bool
    {
        return in_array($this->status, ['pending', 'queued', 'analyzing', 'importing'], true);
    }

    /**
     * Busy but nothing has changed for a while — the queue worker is probably
     * not running.
     */
    public function looksStuck(): bool
    {
        return $this->isBusy()
            && $this->updated_at !== null
            && $this->updated_at->lt(now()->subMinutes(2));
    }

    public function isFinished(): bool
    {
        return in_array($this->status, ['completed', 'failed'], true);
    }

    /** 0–100 — rows inserted so far vs the valid rows to import. */
    public function progressPercent(): int
    {
        $target = (int) ($this->valid_rows ?? 0);

        if ($target < 1) {
            return $this->status === 'completed' ? 100 : 0;
        }

        return (int) min(100, round(((int) ($this->imported_rows ?? 0)) / $target * 100));
    }
}
