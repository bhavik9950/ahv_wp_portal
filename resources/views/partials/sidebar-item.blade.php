@php
    $allowed = $allowed ?? true;
    $badge = $badge ?? null;
@endphp
@if ($allowed)
    <li>
        @if ($url)
            <a href="{{ $url }}" @class(['active font-medium' => $active])>
                <i class="ti {{ $icon }} text-base"></i>
                <span class="flex-1">{!! $label !!}</span>
                @if ($badge)
                    <span class="badge badge-warning badge-xs ml-auto uppercase tracking-wider font-semibold text-[10px]">{{ $badge }}</span>
                @endif
            </a>
        @else
            <span class="opacity-40 cursor-not-allowed" title="Coming soon">
                <i class="ti {{ $icon }} text-base"></i>
                <span>{!! $label !!}</span>
                <span class="badge badge-ghost badge-xs ml-auto">soon</span>
            </span>
        @endif
    </li>
@endif

