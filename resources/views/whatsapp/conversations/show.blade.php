<x-app-layout>
    <x-slot name="title">{{ $contact?->name ?? '+'.$phone }}</x-slot>

    @php($lastDate = null)

    <div class="max-w-2xl mx-auto space-y-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('whatsapp.conversations.index') }}" class="btn btn-ghost btn-sm btn-square">
                <i class="ti ti-arrow-left"></i>
            </a>
            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary font-semibold">
                {{ mb_strtoupper(mb_substr($contact?->name ?? '#', 0, 1)) }}
            </span>
            <div class="min-w-0">
                <div class="font-medium truncate">{{ $contact?->name ?? 'Unknown contact' }}</div>
                <div class="text-xs opacity-60 font-mono">+{{ $phone }}</div>
            </div>
        </div>

        <div class="card bg-base-200/50 border border-base-300">
            <div class="card-body gap-3 p-4">
                @foreach ($messages as $m)
                    @php($day = $m->created_at?->format('Y-m-d'))
                    @if ($day !== $lastDate)
                        @php($lastDate = $day)
                        <div class="flex items-center justify-center">
                            <span class="badge badge-ghost badge-sm bg-base-100">{{ $m->created_at?->isToday() ? 'Today' : $m->created_at?->format('d M Y') }}</span>
                        </div>
                    @endif

                    <div class="flex {{ $m->direction === 'outbound' ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[80%] rounded-2xl px-3 py-2 text-sm shadow-sm
                                    {{ $m->direction === 'outbound' ? 'bg-primary text-primary-content rounded-br-sm' : 'bg-base-100 rounded-bl-sm' }}">

                            @if ($m->type->value === 'image')
                                @if ($m->media)
                                    <a href="{{ $mediaUrls[$m->getKey()] }}" target="_blank" rel="noopener">
                                        <img src="{{ $mediaUrls[$m->getKey()] }}" alt="Image" class="rounded-lg max-h-72 object-cover mb-1">
                                    </a>
                                @elseif ($m->hasDownloadableMedia())
                                    <div class="flex items-center gap-2 opacity-70 py-2">
                                        <span class="loading loading-spinner loading-xs"></span> Downloading image…
                                    </div>
                                @else
                                    <div class="flex items-center gap-2 opacity-70 py-2"><i class="ti ti-photo-x"></i> Image unavailable</div>
                                @endif
                                @if ($m->bodyText())<div>{{ $m->bodyText() }}</div>@endif

                            @elseif ($m->type->value === 'video')
                                @if ($m->media)
                                    <video controls class="rounded-lg max-h-72 mb-1" src="{{ $mediaUrls[$m->getKey()] }}"></video>
                                @elseif ($m->hasDownloadableMedia())
                                    <div class="flex items-center gap-2 opacity-70 py-2">
                                        <span class="loading loading-spinner loading-xs"></span> Downloading video…
                                    </div>
                                @else
                                    <div class="flex items-center gap-2 opacity-70 py-2"><i class="ti ti-video-off"></i> Video unavailable</div>
                                @endif
                                @if ($m->bodyText())<div>{{ $m->bodyText() }}</div>@endif

                            @elseif ($m->type->value === 'audio')
                                @if ($m->media)
                                    <audio controls class="max-w-full" src="{{ $mediaUrls[$m->getKey()] }}"></audio>
                                @elseif ($m->hasDownloadableMedia())
                                    <div class="flex items-center gap-2 opacity-70 py-2">
                                        <span class="loading loading-spinner loading-xs"></span> Downloading voice message…
                                    </div>
                                @else
                                    <div class="flex items-center gap-2 opacity-70 py-2"><i class="ti ti-microphone-off"></i> Voice message unavailable</div>
                                @endif

                            @elseif ($m->type->value === 'document')
                                @if ($m->media)
                                    <a href="{{ $mediaUrls[$m->getKey()] }}" target="_blank" rel="noopener"
                                       class="flex items-center gap-2 {{ $m->direction === 'outbound' ? '' : 'link' }}">
                                        <i class="ti ti-file-description text-lg"></i>
                                        <span class="truncate">{{ $m->media->original_name }}</span>
                                    </a>
                                @elseif ($m->hasDownloadableMedia())
                                    <div class="flex items-center gap-2 opacity-70 py-2">
                                        <span class="loading loading-spinner loading-xs"></span> Downloading document…
                                    </div>
                                @else
                                    <div class="flex items-center gap-2 opacity-70 py-2"><i class="ti ti-file-off"></i> Document unavailable</div>
                                @endif

                            @elseif ($m->type->value === 'location')
                                @php($lat = data_get($m->payload, 'location.latitude'))
                                @php($lng = data_get($m->payload, 'location.longitude'))
                                <div class="flex items-center gap-2">
                                    <i class="ti ti-map-pin text-lg"></i>
                                    <div>
                                        <div>{{ $m->bodyText() ?? 'Location shared' }}</div>
                                        @if ($lat && $lng)
                                            <a class="link text-xs" target="_blank" rel="noopener"
                                               href="https://www.google.com/maps?q={{ $lat }},{{ $lng }}">View on map</a>
                                        @endif
                                    </div>
                                </div>

                            @elseif ($m->type->value === 'template')
                                <div class="text-[0.7rem] uppercase tracking-wide opacity-60 mb-0.5">
                                    <i class="ti ti-template"></i> {{ $m->template?->name ?? 'Template' }}
                                </div>
                                <div class="whitespace-pre-line">{{ $m->bodyText() ?? '—' }}</div>

                            @else
                                <div class="whitespace-pre-line">{{ $m->bodyText() ?? ucfirst($m->type->value) }}</div>
                            @endif

                            <div class="flex items-center justify-end gap-1 mt-1 text-[0.65rem] {{ $m->direction === 'outbound' ? 'text-primary-content/70' : 'opacity-50' }}">
                                <span>{{ $m->created_at?->format('H:i') }}</span>
                                @if ($m->direction === 'outbound')
                                    @if ($m->status->value === 'read')
                                        <i class="ti ti-checks"></i>
                                    @elseif ($m->status->value === 'delivered')
                                        <i class="ti ti-checks opacity-60"></i>
                                    @elseif ($m->status->value === 'sent')
                                        <i class="ti ti-check"></i>
                                    @elseif ($m->status->value === 'failed')
                                        <i class="ti ti-alert-triangle text-error"></i>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>

                    @if ($m->direction === 'outbound' && $m->status->value === 'failed' && $m->error_message)
                        <div class="flex justify-end">
                            <div class="max-w-[80%] text-xs text-error px-1">{{ $m->error_message }}</div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        <a href="{{ route('whatsapp.messages.index') }}" class="link link-hover text-xs opacity-60">
            <i class="ti ti-list"></i> View raw message log
        </a>
    </div>
</x-app-layout>
