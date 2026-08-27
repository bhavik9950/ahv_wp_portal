<x-app-layout>
    <x-slot name="title">{{ $contact->name ?: 'Contact' }}</x-slot>

    @php($canManage = auth()->user()->can(\App\Enums\Permission::ContactManage->value))

    <div class="max-w-2xl space-y-4">
        <div class="flex items-center justify-between">
            <a href="{{ route('whatsapp.contacts.index') }}" class="btn btn-ghost btn-sm"><i class="ti ti-arrow-left"></i> Contacts</a>
            @if ($canManage)
                <form method="POST" action="{{ route('whatsapp.contacts.destroy', $contact) }}" data-confirm="Delete this contact?">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-error btn-outline"><i class="ti ti-trash"></i> Delete</button>
                </form>
            @endif
        </div>

        <div class="card bg-base-100 border border-base-300">
            <div class="card-body">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="card-title text-base">{{ $contact->name ?: '—' }}</h2>
                        <p class="font-mono text-sm opacity-70">+{{ $contact->phone_e164 }}</p>
                        @if ($contact->email)<p class="text-sm opacity-70">{{ $contact->email }}</p>@endif
                    </div>
                    <div class="text-right">
                        @php($m = [\App\Enums\OptInStatus::OptedIn->value => 'badge-success', \App\Enums\OptInStatus::OptedOut->value => 'badge-error'])
                        <span class="badge {{ $m[$contact->opt_in_status->value] ?? 'badge-ghost' }}">{{ $contact->opt_in_status->label() }}</span>
                        @if ($canManage)
                            <div class="mt-2 flex gap-1 justify-end">
                                <form method="POST" action="{{ route('whatsapp.contacts.opt-in', $contact) }}">@csrf
                                    <button class="btn btn-xs btn-ghost" @disabled($contact->opt_in_status === \App\Enums\OptInStatus::OptedIn)>Opt in</button>
                                </form>
                                <form method="POST" action="{{ route('whatsapp.contacts.opt-out', $contact) }}" data-confirm="Opt this contact out of marketing?">@csrf
                                    <button class="btn btn-xs btn-ghost text-error" @disabled($contact->isOptedOut())>Opt out</button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>

                @if ($contact->groups->isNotEmpty())
                    <div class="mt-2 flex flex-wrap gap-1">
                        @foreach ($contact->groups as $g)<span class="badge badge-outline badge-sm">{{ $g->name }}</span>@endforeach
                    </div>
                @endif

                @if ($contact->custom_fields)
                    <div class="divider text-xs">Custom fields</div>
                    <dl class="text-sm grid grid-cols-2 gap-x-4 gap-y-1">
                        @foreach ($contact->custom_fields as $k => $v)
                            <dt class="opacity-60">{{ $k }}</dt><dd>{{ $v }}</dd>
                        @endforeach
                    </dl>
                @endif
            </div>
        </div>

        <div class="card bg-base-100 border border-base-300">
            <div class="card-body">
                <h2 class="card-title text-base">Consent history</h2>
                <ul class="text-sm space-y-1">
                    @forelse ($contact->optInRecords as $r)
                        <li class="flex items-center gap-2">
                            <span class="badge badge-sm {{ $r->status === 'opt_in' ? 'badge-success' : 'badge-error' }}">{{ $r->status === 'opt_in' ? 'Opted in' : 'Opted out' }}</span>
                            <span class="opacity-60">{{ $r->created_at?->format('d M Y H:i') }}</span>
                            <span class="opacity-60">· {{ $r->source }}</span>
                            @if ($r->note)<span class="opacity-50">— {{ $r->note }}</span>@endif
                        </li>
                    @empty
                        <li class="opacity-60">No consent events recorded.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
