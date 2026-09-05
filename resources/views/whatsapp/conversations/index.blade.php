<x-app-layout>
    <x-slot name="title">Chats</x-slot>

    <div class="space-y-4">
        @if ($threads->isEmpty())
            <div class="alert text-sm">
                <i class="ti ti-info-circle"></i>
                <span>No conversations yet. They'll show up here as soon as a customer messages you or you send them one.</span>
            </div>
        @endif

        <div class="card bg-base-100 border border-base-300 divide-y divide-base-300">
            @foreach ($threads as $m)
                <a href="{{ route('whatsapp.conversations.show', $m->to_phone) }}"
                   class="flex items-center gap-3 p-3 hover:bg-base-200 transition-colors">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary font-semibold">
                        {{ mb_strtoupper(mb_substr($m->contact?->name ?? '#', 0, 1)) }}
                    </span>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-medium truncate">{{ $m->contact?->name ?? '+'.$m->to_phone }}</span>
                            <span class="text-xs opacity-50 shrink-0" title="{{ $m->created_at?->format('d M Y, H:i:s') }}">
                                {{ $m->created_at?->diffForHumans() }}
                            </span>
                        </div>
                        <div class="flex items-center gap-1.5 text-sm opacity-70 truncate">
                            @if ($m->direction === 'outbound')
                                <i class="ti ti-corner-up-right text-xs shrink-0"></i>
                            @endif
                            <span class="truncate">
                                @if ($m->type->value === 'image')
                                    <i class="ti ti-photo text-xs"></i> Photo
                                @elseif ($m->type->value === 'video')
                                    <i class="ti ti-video text-xs"></i> Video
                                @elseif ($m->type->value === 'audio')
                                    <i class="ti ti-mic text-xs"></i> Voice message
                                @elseif ($m->type->value === 'document')
                                    <i class="ti ti-file text-xs"></i> Document
                                @elseif ($m->type->value === 'location')
                                    <i class="ti ti-map-pin text-xs"></i> Location
                                @else
                                    {{ \Illuminate\Support\Str::limit($m->bodyText() ?? ucfirst($m->type->value), 70) }}
                                @endif
                            </span>
                        </div>
                    </div>

                    @if (($counts[$m->to_phone] ?? 0) > 1)
                        <span class="badge badge-sm badge-ghost shrink-0">{{ $counts[$m->to_phone] }}</span>
                    @endif
                    <i class="ti ti-chevron-right opacity-30 shrink-0"></i>
                </a>
            @endforeach
        </div>
    </div>
</x-app-layout>
