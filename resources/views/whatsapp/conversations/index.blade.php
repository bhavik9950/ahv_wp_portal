<x-app-layout>
    <x-slot name="title">Chats</x-slot>

    <div class="space-y-4">
        {{-- Header & Search Bar --}}
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 bg-base-100 p-3.5 rounded-2xl border border-base-300 shadow-2xs">
            <div>
                <h1 class="text-lg font-bold flex items-center gap-2">
                    <i class="ti ti-messages text-primary"></i> WhatsApp Inbox & Conversations
                </h1>
                <p class="text-xs opacity-60">Manage direct customer chats, review inbound replies, and respond in real-time.</p>
            </div>

            <form method="GET" action="{{ route('whatsapp.conversations.index') }}" class="flex items-center gap-1.5 shrink-0">
                @if ($filter !== 'all')
                    <input type="hidden" name="filter" value="{{ $filter }}">
                @endif
                <div class="join w-full sm:w-64">
                    <input type="text" name="q" value="{{ $search }}" placeholder="Search phone, name..."
                           class="input input-sm input-bordered join-item w-full text-xs font-mono">
                    <button type="submit" class="btn btn-sm btn-primary join-item px-3" title="Search chats">
                        <i class="ti ti-search"></i>
                    </button>
                </div>
                @if ($search)
                    <a href="{{ route('whatsapp.conversations.index', ['filter' => $filter !== 'all' ? $filter : null]) }}"
                       class="btn btn-sm btn-ghost btn-square text-error" title="Clear search">
                        <i class="ti ti-x"></i>
                    </a>
                @endif
            </form>
        </div>

        {{-- Filter Tabs / Quick Filter Pills --}}
        <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs">
            <a href="{{ route('whatsapp.conversations.index', ['filter' => 'all', 'q' => $search ?: null]) }}"
               class="btn btn-sm rounded-xl {{ $filter === 'all' ? 'btn-neutral font-bold shadow-xs' : 'btn-ghost bg-base-100 border border-base-300' }}">
                All Chats
                <span class="badge badge-sm {{ $filter === 'all' ? 'badge-primary' : 'badge-ghost' }} font-mono font-bold">{{ $tabCounts['all'] ?? 0 }}</span>
            </a>

            <a href="{{ route('whatsapp.conversations.index', ['filter' => 'needs_reply', 'q' => $search ?: null]) }}"
               class="btn btn-sm rounded-xl {{ $filter === 'needs_reply' ? 'btn-warning text-warning-content font-bold shadow-xs' : 'btn-ghost bg-base-100 border border-base-300 hover:border-warning' }}">
                <i class="ti ti-mail-forward"></i>
                Needs Reply (Awaiting Response)
                <span class="badge badge-sm {{ $filter === 'needs_reply' ? 'bg-amber-900 text-white' : 'badge-warning' }} font-mono font-bold">{{ $tabCounts['needs_reply'] ?? 0 }}</span>
            </a>

            <a href="{{ route('whatsapp.conversations.index', ['filter' => 'inbound', 'q' => $search ?: null]) }}"
               class="btn btn-sm rounded-xl {{ $filter === 'inbound' ? 'btn-primary font-bold shadow-xs' : 'btn-ghost bg-base-100 border border-base-300' }}">
                <i class="ti ti-message-reply"></i>
                Customer Replied (Inbound)
                <span class="badge badge-sm {{ $filter === 'inbound' ? 'badge-neutral' : 'badge-ghost' }} font-mono font-bold">{{ $tabCounts['inbound'] ?? 0 }}</span>
            </a>

            <a href="{{ route('whatsapp.conversations.index', ['filter' => 'active_window', 'q' => $search ?: null]) }}"
               class="btn btn-sm rounded-xl {{ $filter === 'active_window' ? 'btn-success text-white font-bold shadow-xs' : 'btn-ghost bg-base-100 border border-base-300' }}">
                <i class="ti ti-bolt"></i>
                24h Window Active
                <span class="badge badge-sm {{ $filter === 'active_window' ? 'badge-neutral' : 'badge-success badge-outline' }} font-mono font-bold">{{ $tabCounts['active_window'] ?? 0 }}</span>
            </a>
        </div>

        @if ($threads->isEmpty())
            <div class="card bg-base-100 border border-base-300 p-8 text-center space-y-2">
                <i class="ti ti-messages-off text-4xl opacity-30 mx-auto"></i>
                <h3 class="font-bold text-base">No conversations found</h3>
                <p class="text-xs opacity-60 max-w-sm mx-auto">
                    @if ($filter === 'needs_reply')
                        Awesome! There are no pending customer replies awaiting your response.
                    @elseif ($filter === 'inbound')
                        No customer has sent an inbound message yet.
                    @elseif ($filter === 'active_window')
                        There are currently no active 24-hour service windows open.
                    @elseif ($search)
                        No conversation matches "<strong>{{ $search }}</strong>".
                    @else
                        Conversations will appear here as soon as you broadcast a campaign or a customer messages you.
                    @endif
                </p>
                @if ($filter !== 'all' || $search)
                    <div class="pt-2">
                        <a href="{{ route('whatsapp.conversations.index') }}" class="btn btn-xs btn-outline">
                            Show All Chats
                        </a>
                    </div>
                @endif
            </div>
        @else
            <div class="card bg-base-100 border border-base-300 divide-y divide-base-300 shadow-2xs overflow-hidden">
                @foreach ($threads as $m)
                    @php($hasCustomerReplied = isset($inboundPhones[$m->to_phone]))
                    @php($hasActiveWindow = isset($activeWindowPhones[$m->to_phone]))
                    @php($isNeedsReply = $m->direction === 'inbound')

                    <a href="{{ route('whatsapp.conversations.show', $m->to_phone) }}"
                       class="flex items-center gap-3 p-3.5 hover:bg-base-200/70 transition-all group {{ $isNeedsReply ? 'bg-amber-50/40' : '' }}">
                        {{-- Avatar --}}
                        <div class="relative shrink-0">
                            <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl {{ $isNeedsReply ? 'bg-warning/20 text-warning font-bold' : ($hasActiveWindow ? 'bg-emerald-100 text-emerald-700 font-bold' : 'bg-primary/10 text-primary font-semibold') }}">
                                {{ mb_strtoupper(mb_substr($m->contact?->name ?? '#', 0, 1)) }}
                            </span>
                            @if ($isNeedsReply)
                                <span class="absolute -top-1 -right-1 w-3.5 h-3.5 rounded-full bg-amber-500 border-2 border-white ring-1 ring-amber-300 animate-pulse" title="Awaiting reply"></span>
                            @elseif ($hasActiveWindow)
                                <span class="absolute -top-1 -right-1 w-3.5 h-3.5 rounded-full bg-emerald-500 border-2 border-white" title="24-hr window active"></span>
                            @endif
                        </div>

                        {{-- Details --}}
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2 flex-wrap sm:flex-nowrap">
                                <div class="flex items-center gap-2 truncate">
                                    <span class="font-bold text-sm tracking-tight truncate text-slate-900">
                                        {{ $m->contact?->name ?? '+'.$m->to_phone }}
                                    </span>
                                    @if ($m->contact && $m->contact->name)
                                        <span class="text-xs font-mono opacity-50 hidden xs:inline">+{{ $m->to_phone }}</span>
                                    @endif

                                    {{-- Status Badges --}}
                                    @if ($isNeedsReply)
                                        <span class="badge badge-warning badge-xs font-bold gap-1 shrink-0">
                                            <i class="ti ti-mail-forward"></i> Awaiting Reply
                                        </span>
                                    @elseif ($hasActiveWindow)
                                        <span class="badge badge-xs bg-emerald-100 text-emerald-800 font-bold border-emerald-300 shrink-0">
                                            ⚡ 24h Window
                                        </span>
                                    @elseif ($hasCustomerReplied)
                                        <span class="badge badge-success badge-outline badge-xs font-medium shrink-0">
                                            Customer Replied
                                        </span>
                                    @endif
                                </div>

                                <span class="text-[0.7rem] opacity-50 shrink-0 font-medium" title="{{ $m->created_at?->timezone('Asia/Kolkata')->format('d M Y, h:i:s A') }}">
                                    {{ $m->created_at?->diffForHumans() }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between gap-2 mt-0.5">
                                <div class="flex items-center gap-1.5 text-xs truncate {{ $isNeedsReply ? 'font-semibold text-slate-900' : 'opacity-70' }}">
                                    @if ($m->direction === 'outbound')
                                        <i class="ti ti-corner-up-right text-xs shrink-0 text-slate-400"></i>
                                    @else
                                        <i class="ti ti-corner-down-left text-xs shrink-0 text-primary"></i>
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
                                        @elseif ($m->type->value === 'reaction')
                                            <i class="ti ti-mood-smile text-xs"></i> Reacted: {{ data_get($m->payload, 'reaction.emoji') ?? '👍' }}
                                        @else
                                            {{ \Illuminate\Support\Str::limit($m->bodyText() ?? ucfirst($m->type->value), 80) }}
                                        @endif
                                    </span>
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    @if (($counts[$m->to_phone] ?? 0) > 1)
                                        <span class="badge badge-sm badge-ghost font-mono text-[0.7rem]">{{ $counts[$m->to_phone] }}</span>
                                    @endif
                                    <span class="btn btn-xs btn-primary btn-outline opacity-0 group-hover:opacity-100 transition-opacity hidden sm:inline-flex gap-1 py-0 px-2 font-bold">
                                        Reply →
                                    </span>
                                    <i class="ti ti-chevron-right opacity-30 text-xs"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
