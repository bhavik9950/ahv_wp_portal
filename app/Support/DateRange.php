<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Carbon;

final class DateRange
{
    public function __construct(
        public readonly Carbon $from,
        public readonly Carbon $to,
        public readonly string $key,
    ) {}

    /**
     * @param  array{range?:string, from?:string, to?:string}  $input
     */
    public static function fromRequest(array $input, string $timezone = 'UTC'): self
    {
        $key = $input['range'] ?? 'last_7_days';
        $now = Carbon::now($timezone);

        return match ($key) {
            'today' => new self($now->copy()->startOfDay(), $now->copy()->endOfDay(), 'today'),
            'yesterday' => new self($now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay(), 'yesterday'),
            'last_30_days' => new self($now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay(), 'last_30_days'),
            'custom' => self::custom($input, $now),
            default => new self($now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay(), 'last_7_days'),
        };
    }

    private static function custom(array $input, Carbon $now): self
    {
        try {
            $from = isset($input['from']) ? Carbon::parse($input['from']) : $now->copy()->subDays(6);
            $to = isset($input['to']) ? Carbon::parse($input['to']) : $now->copy();
        } catch (\Throwable) {
            $from = $now->copy()->subDays(6);
            $to = $now->copy();
        }

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        // Cap the span so a chart can't be asked to render thousands of buckets.
        if ($from->diffInDays($to) > 180) {
            $from = $to->copy()->subDays(180);
        }

        return new self($from->startOfDay(), $to->endOfDay(), 'custom');
    }

    /** @return list<array{key: string, label: string}> */
    public static function presets(): array
    {
        return [
            ['key' => 'today', 'label' => 'Today'],
            ['key' => 'yesterday', 'label' => 'Yesterday'],
            ['key' => 'last_7_days', 'label' => 'Last 7 days'],
            ['key' => 'last_30_days', 'label' => 'Last 30 days'],
        ];
    }
}
