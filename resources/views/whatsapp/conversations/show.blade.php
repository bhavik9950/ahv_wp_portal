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
            <div class="min-w-0 flex-1">
                <div class="font-medium truncate">{{ $contact?->name ?? 'Unknown contact' }}</div>
                <div class="text-xs opacity-60 font-mono">+{{ $phone }}</div>
            </div>
            <a href="tel:+{{ $phone }}" class="btn btn-ghost btn-sm btn-square" title="Call this number from your device">
                <i class="ti ti-phone-call"></i>
            </a>
        </div>

        <div class="card bg-base-200/50 border border-base-300">
            <div class="card-body gap-3 p-4">
                @foreach ($messages as $m)
                    @php($day = $m->created_at?->timezone('Asia/Kolkata')->format('Y-m-d'))
                    @if ($day !== $lastDate)
                        @php($lastDate = $day)
                        <div class="flex items-center justify-center">
                            <span class="badge badge-ghost badge-sm bg-base-100">{{ $m->created_at?->timezone('Asia/Kolkata')->isToday() ? 'Today' : $m->created_at?->timezone('Asia/Kolkata')->format('d M Y') }}</span>
                        </div>
                    @endif

                    <div class="flex {{ $m->direction === 'outbound' ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[80%] rounded-2xl px-3 py-2 text-sm shadow-sm
                                    {{ $m->direction === 'outbound' ? 'bg-primary text-primary-content rounded-br-sm' : 'bg-base-100 rounded-bl-sm' }}">

                            @if ($m->type->value === 'image')
                                @if ($m->media)
                                    <div class="relative inline-block mb-1">
                                        <a href="{{ $mediaUrls[$m->getKey()] }}" target="_blank" rel="noopener">
                                            <img src="{{ $mediaUrls[$m->getKey()] }}" alt="Image" class="rounded-lg max-h-72 object-cover">
                                        </a>
                                        <a href="{{ $mediaUrls[$m->getKey()] }}" download="{{ $m->media->original_name }}"
                                           class="absolute top-1.5 right-1.5 inline-flex h-7 w-7 items-center justify-center rounded-full bg-black/50 text-white hover:bg-black/70"
                                           title="Download image">
                                            <i class="ti ti-download text-sm"></i>
                                        </a>
                                    </div>
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
                                    <video controls class="rounded-lg max-h-72 mb-1 block" src="{{ $mediaUrls[$m->getKey()] }}"></video>
                                    <a href="{{ $mediaUrls[$m->getKey()] }}" download="{{ $m->media->original_name }}"
                                       class="text-xs {{ $m->direction === 'outbound' ? 'text-primary-content/80' : 'link' }}">
                                        <i class="ti ti-download"></i> Download
                                    </a>
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
                                    <div class="flex items-center gap-2">
                                        <audio controls class="max-w-full" src="{{ $mediaUrls[$m->getKey()] }}"></audio>
                                        <a href="{{ $mediaUrls[$m->getKey()] }}" download="{{ $m->media->original_name }}"
                                           class="{{ $m->direction === 'outbound' ? 'text-primary-content/80' : 'opacity-60' }}"
                                           title="Download">
                                            <i class="ti ti-download"></i>
                                        </a>
                                    </div>
                                @elseif ($m->hasDownloadableMedia())
                                    <div class="flex items-center gap-2 opacity-70 py-2">
                                        <span class="loading loading-spinner loading-xs"></span> Downloading voice message…
                                    </div>
                                @else
                                    <div class="flex items-center gap-2 opacity-70 py-2"><i class="ti ti-microphone-off"></i> Voice message unavailable</div>
                                @endif

                            @elseif ($m->type->value === 'document')
                                @if ($m->media)
                                    <a href="{{ $mediaUrls[$m->getKey()] }}" download="{{ $m->media->original_name }}"
                                       class="flex items-center gap-2 {{ $m->direction === 'outbound' ? '' : 'link' }}">
                                        <i class="ti ti-file-description text-lg"></i>
                                        <span class="truncate">{{ $m->media->original_name }}</span>
                                        <i class="ti ti-download text-sm opacity-70"></i>
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

                            @elseif ($m->type->value === 'reaction')
                                <div class="text-2xl py-1">{{ data_get($m->payload, 'reaction.emoji') ?? '👍' }}</div>

                            @elseif ($m->type->value === 'sticker')
                                @if ($m->media)
                                    <img src="{{ $mediaUrls[$m->getKey()] }}" alt="Sticker" class="rounded-lg max-h-36 object-contain">
                                @else
                                    <div class="italic text-xs opacity-70 py-1"><i class="ti ti-mood-smile"></i> Sticker</div>
                                @endif

                            @elseif ($m->type->value === 'unsupported')
                                <div class="italic text-xs opacity-70 flex items-center gap-1.5 py-1">
                                    <i class="ti ti-info-circle text-warning"></i>
                                    <span>Unsupported message format (e.g. sticker, poll, or system message)</span>
                                </div>

                            @else
                                <div class="whitespace-pre-line">{{ $m->bodyText() ?? ucfirst($m->type->value) }}</div>
                            @endif

                            <div class="flex items-center justify-end gap-1 mt-1 text-[0.65rem] {{ $m->direction === 'outbound' ? 'text-primary-content/70' : 'opacity-50' }}">
                                <span>{{ $m->created_at?->timezone('Asia/Kolkata')->format('h:i A') }}</span>
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

        {{-- 24-Hour Customer Service Window Status Banner --}}
        @if ($isWindowActive)
            <div class="flex items-center justify-between text-xs px-3.5 py-2 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-xl shadow-2xs">
                <div class="flex items-center gap-2 font-medium">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse shrink-0"></span>
                    <span><strong>24-Hour Service Window Active</strong> · Direct free-text reply permitted (₹0 Meta fee)</span>
                </div>
                <div class="text-[0.7rem] opacity-75 font-mono shrink-0 ml-2">
                    Valid until {{ $windowExpiresAt?->timezone('Asia/Kolkata')->format('d M, h:i A') }}
                </div>
            </div>
        @else
            <div class="flex items-center justify-between text-xs px-3.5 py-2.5 bg-amber-50 text-amber-900 border border-amber-200 rounded-xl shadow-2xs flex-wrap gap-2">
                <div class="flex items-center gap-2 font-medium">
                    <i class="ti ti-alert-triangle text-amber-600 text-base shrink-0"></i>
                    <span><strong>24-Hour Service Window Inactive</strong> · Meta requires an approved template message to message this user.</span>
                </div>
                @if (isset($approvedTemplates) && $approvedTemplates->isNotEmpty())
                    <button type="button" onclick="document.getElementById('send-template-modal').showModal()" class="btn btn-xs btn-warning gap-1 shadow-2xs font-bold">
                        <i class="ti ti-template"></i> Send Template
                    </button>
                @endif
            </div>
        @endif

        {{-- Chat Composer / Reply Bar --}}
        @if ($canReply ?? true)
            <div class="sticky bottom-3 z-30 bg-base-100/95 backdrop-blur-md p-3 rounded-2xl border border-base-300 shadow-md">
                <form method="POST" action="{{ route('whatsapp.conversations.reply', $phone) }}" data-turbo="false" id="chat-reply-form" class="space-y-2">
                    @csrf
                    <div class="flex items-end gap-2 bg-base-200/50 p-2 rounded-xl border border-base-300 focus-within:border-primary focus-within:bg-base-100 transition-all">
                        @if (isset($approvedTemplates) && $approvedTemplates->isNotEmpty())
                            <button type="button" onclick="document.getElementById('send-template-modal').showModal()"
                                    class="btn btn-sm btn-ghost btn-circle text-slate-500 hover:text-primary hover:bg-primary/10 shrink-0"
                                    title="Send an approved WhatsApp template">
                                <i class="ti ti-template text-lg"></i>
                            </button>
                        @endif

                        <textarea name="body" id="reply-body-input" rows="1" required
                                  placeholder="{{ $isWindowActive ? 'Type your reply to customer... (Press Enter to send, Shift+Enter for new line)' : '24-hr window expired. Click the template icon to send an approved template.' }}"
                                  {{ ! $isWindowActive ? 'disabled' : '' }}
                                  class="textarea textarea-ghost p-1.5 text-sm w-full leading-normal resize-none focus:outline-hidden min-h-[40px] max-h-36 bg-transparent"></textarea>

                        <button type="submit" id="reply-send-btn"
                                {{ ! $isWindowActive ? 'disabled' : '' }}
                                class="btn btn-primary btn-sm rounded-xl px-4 gap-1.5 shrink-0 shadow-xs">
                            <span class="hidden sm:inline font-bold">Send</span>
                            <i class="ti ti-send"></i>
                        </button>
                    </div>

                    <div class="flex items-center justify-between px-1 text-[0.7rem] opacity-60">
                        <span>Press <strong>Enter</strong> to send &bull; <strong>Shift + Enter</strong> for new line</span>
                        @if ($phoneNumber)
                            <span>Sending via: <strong>+{{ $phoneNumber->display_phone_number ?? $phoneNumber->phone_number_id }}</strong></span>
                        @endif
                    </div>
                </form>
            </div>
        @endif

        {{-- Modal for Quick-Sending an Approved Template --}}
        @if (isset($approvedTemplates) && $approvedTemplates->isNotEmpty())
            <dialog id="send-template-modal" class="modal">
                <div class="modal-box max-w-lg">
                    <form method="dialog">
                        <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                    </form>
                    <h3 class="font-bold text-base mb-1 flex items-center gap-2">
                        <i class="ti ti-template text-primary"></i> Send Approved Template
                    </h3>
                    <p class="text-xs opacity-70 mb-4">
                        Templates are approved by Meta and can be sent anytime, even when the 24-hour service window is closed.
                    </p>

                    <form method="POST" action="{{ route('whatsapp.conversations.reply', $phone) }}" data-turbo="false" class="space-y-4">
                        @csrf
                        <div class="form-control">
                            <label class="label py-1" for="modal_template_select">
                                <span class="label-text text-xs font-semibold">Select Approved Template</span>
                            </label>
                            <select name="template_id" id="modal_template_select" required class="select select-bordered select-sm w-full text-xs" onchange="previewSelectedTemplate(this)">
                                <option value="" disabled selected>— Choose template —</option>
                                @foreach ($approvedTemplates as $tpl)
                                    <option value="{{ $tpl->id }}" data-body="{{ $tpl->bodyText() }}" data-lang="{{ $tpl->language }}">
                                        {{ $tpl->name }} ({{ strtoupper($tpl->language) }} - {{ ucfirst(strtolower($tpl->category)) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div id="template-preview-container" class="hidden p-3 rounded-xl bg-base-200/60 border border-base-300 text-xs">
                            <div class="font-semibold text-[0.7rem] opacity-60 uppercase mb-1">Template Content Preview:</div>
                            <div id="template-preview-body" class="whitespace-pre-line text-slate-800"></div>
                        </div>

                        <div class="modal-action">
                            <button type="button" onclick="document.getElementById('send-template-modal').close()" class="btn btn-sm btn-ghost">Cancel</button>
                            <button type="submit" class="btn btn-sm btn-primary gap-1 font-bold">
                                <i class="ti ti-send"></i> Send Template Now
                            </button>
                        </div>
                    </form>
                </div>
            </dialog>
        @endif

        <div class="flex items-center justify-between pt-2">
            <a href="{{ route('whatsapp.messages.index') }}" class="link link-hover text-xs opacity-60">
                <i class="ti ti-list"></i> View raw message log
            </a>
            <span class="text-xs opacity-50">Total {{ $messages->count() }} message(s)</span>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto scroll to bottom
            window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });

            const textarea = document.getElementById('reply-body-input');
            const form = document.getElementById('chat-reply-form');

            if (textarea && form) {
                // Auto expand textarea
                textarea.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = Math.min(this.scrollHeight, 140) + 'px';
                });

                // Enter to submit (Shift+Enter for newline)
                textarea.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        if (this.value.trim().length > 0) {
                            form.submit();
                        }
                    }
                });
            }
        });

        function previewSelectedTemplate(select) {
            const selectedOpt = select.options[select.selectedIndex];
            const body = selectedOpt.getAttribute('data-body');
            const container = document.getElementById('template-preview-container');
            const bodyElem = document.getElementById('template-preview-body');

            if (body) {
                bodyElem.textContent = body;
                container.classList.remove('hidden');
            } else {
                container.classList.add('hidden');
            }
        }
    </script>
</x-app-layout>
