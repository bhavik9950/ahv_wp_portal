@php
    /** @var list<array{date:string,outbound:int,delivered:int,failed:int}> $trend */
    $w = 820; $h = 240;
    $padL = 44; $padR = 16; $padT = 16; $padB = 28;
    $n = max(1, count($trend));
    $max = max(1, collect($trend)->max('outbound') ?? 1);

    $xFor = fn (int $i) => $n === 1 ? $padL : $padL + $i * (($w - $padL - $padR) / ($n - 1));
    $yFor = fn (int $v) => $h - $padB - ($v / $max) * ($h - $padT - $padB);

    $seriesDefs = [
        ['key' => 'outbound',  'name' => 'Sent',      'var' => '--series-1'],
        ['key' => 'delivered', 'name' => 'Delivered', 'var' => '--series-3'],
        ['key' => 'failed',    'name' => 'Failed',     'var' => '--series-8'],
    ];

    $paths = [];
    $jsSeries = [];
    foreach ($seriesDefs as $sd) {
        $pts = [];
        $jsPts = [];
        foreach ($trend as $i => $row) {
            $x = round($xFor($i), 1);
            $y = round($yFor((int) $row[$sd['key']]), 1);
            $pts[] = "{$x},{$y}";
            $jsPts[] = ['x' => $x, 'y' => $y, 'date' => \Illuminate\Support\Carbon::parse($row['date'])->format('d M'), 'value' => (int) $row[$sd['key']]];
        }
        $paths[$sd['key']] = 'M '.implode(' L ', $pts);
        $jsSeries[] = ['name' => $sd['name'], 'color' => "var({$sd['var']})", 'points' => $jsPts];
    }

    $areaPath = $paths['outbound']
        .' L '.round($xFor($n - 1), 1).','.round($yFor(0), 1)
        .' L '.round($xFor(0), 1).','.round($yFor(0), 1).' Z';

    $ticks = $n <= 8 ? range(0, $n - 1) : [0, intdiv($n, 2), $n - 1];

    $seriesJson = json_encode($jsSeries);
    $plotJson = json_encode(['x0' => $padL, 'x1' => $w - $padR, 'y0' => $padT, 'y1' => $h - $padB]);
@endphp

<figure class="viz relative"
        data-trend-chart
        data-series='{{ $seriesJson }}'
        data-plot='{{ $plotJson }}'>
    <figcaption class="flex items-center gap-4 text-xs mb-1 opacity-70">
        @foreach ($seriesDefs as $sd)
            <span class="inline-flex items-center gap-1">
                <span class="inline-block w-4 h-0.5 {{ $sd['key'] === 'failed' ? 'border-t-2 border-dashed' : '' }}"
                      style="{{ $sd['key'] === 'failed' ? 'border-color' : 'background' }}:var({{ $sd['var'] }})"></span>{{ $sd['name'] }}
            </span>
        @endforeach
    </figcaption>

    <svg viewBox="0 0 {{ $w }} {{ $h }}" class="w-full h-auto text-base-content" role="img"
         aria-label="Daily outbound message volume for the selected period">
        {{-- gridlines --}}
        @foreach ([0, 0.5, 1] as $g)
            @php($gy = $h - $padB - $g * ($h - $padT - $padB))
            <line x1="{{ $padL }}" x2="{{ $w - $padR }}" y1="{{ $gy }}" y2="{{ $gy }}"
                  stroke="currentColor" stroke-width="1" opacity="0.08" />
            <text x="{{ $padL - 8 }}" y="{{ $gy + 3 }}" text-anchor="end" font-size="10" fill="currentColor" opacity="0.5">
                {{ round($g * $max) }}
            </text>
        @endforeach

        <path d="{{ $areaPath }}" fill="var(--series-1)" opacity="0.10" />
        <path d="{{ $paths['outbound'] }}" fill="none" stroke="var(--series-1)" stroke-width="2" stroke-linejoin="round" />
        <path d="{{ $paths['delivered'] }}" fill="none" stroke="var(--series-3)" stroke-width="2" stroke-linejoin="round" />
        {{-- dashed so it's distinguishable from the aqua line without relying on hue --}}
        <path d="{{ $paths['failed'] }}" fill="none" stroke="var(--series-8)" stroke-width="1.5"
              stroke-linejoin="round" stroke-dasharray="4 3" opacity="0.9" />

        @foreach ($ticks as $i)
            <text x="{{ $xFor($i) }}" y="{{ $h - 8 }}" text-anchor="middle" font-size="10" fill="currentColor" opacity="0.5">
                {{ \Illuminate\Support\Carbon::parse($trend[$i]['date'])->format('d M') }}
            </text>
        @endforeach
    </svg>

    <div data-trend-tooltip hidden
         class="absolute pointer-events-none text-xs bg-base-100 border border-base-300 rounded-md shadow px-2 py-1 z-10"></div>
</figure>

<style>
    .viz {
        --series-1: #2a78d6;
        --series-3: #1baf7a;
        --series-8: #e34948;
        --chart-surface: #fcfcfb;
    }
    :root[data-theme="dark"] .viz,
    :root:not([data-theme="light"]) .viz {
        --series-1: #3987e5;
        --series-3: #199e70;
        --series-8: #e66767;
        --chart-surface: #1a1a19;
    }
</style>
