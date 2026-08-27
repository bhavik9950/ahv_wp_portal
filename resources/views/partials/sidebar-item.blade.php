@php($allowed = $allowed ?? true)
@if ($allowed)
    <li>
        @if ($url)
            <a href="{{ $url }}" @class(['active font-medium' => $active])>
                <i class="ti {{ $icon }} text-base"></i>
                <span>{!! $label !!}</span>
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
