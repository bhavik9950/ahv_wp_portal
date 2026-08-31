<x-app-layout>
    <x-slot name="title">New Template</x-slot>

    @php($v1 = '{{1}}')
    @php($v2 = '{{2}}')
    <div class="max-w-2xl">
        <div class="alert alert-info mb-4 text-sm">
            <i class="ti ti-info-circle"></i>
            <span>Templates are reviewed by Meta. Approval is not guaranteed and cannot be bypassed.
                Only APPROVED templates become available for campaigns.</span>
        </div>

        <form method="POST" action="{{ route('whatsapp.templates.store') }}" enctype="multipart/form-data"
              x-data="{ header: '{{ old('header_type', 'none') }}', buttons: {{ old('buttons') ? count(old('buttons')) : 0 }} }"
              data-loading data-loading-text="Submitting…"
              class="card bg-base-100 border border-base-300">
            @csrf
            <div class="card-body space-y-4">

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label"><span class="label-text">Name</span></label>
                        <input name="name" value="{{ old('name') }}" class="input input-bordered w-full" placeholder="order_dispatched_update" required>
                        @error('name')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label"><span class="label-text">Language</span></label>
                        <input name="language" value="{{ old('language', $language) }}" class="input input-bordered w-full" required>
                        @error('language')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="label"><span class="label-text">Category</span></label>
                    <select name="category" class="select select-bordered w-full" required>
                        @foreach (['UTILITY','MARKETING','AUTHENTICATION'] as $c)
                            <option value="{{ $c }}" @selected(old('category') === $c)>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label"><span class="label-text">Header</span></label>
                    <select name="header_type" x-model="header" class="select select-bordered w-full">
                        <option value="none">None</option>
                        <option value="text">Text</option>
                        <option value="image">Image</option>
                        <option value="video">Video</option>
                        <option value="document">Document</option>
                    </select>
                    <input x-show="header === 'text'" name="header_text" value="{{ old('header_text') }}"
                           class="input input-bordered w-full mt-2" placeholder="Header text (max 60)" maxlength="60">
                    @error('header_text')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror

                    <div x-show="['image','video','document'].includes(header)" class="mt-2 space-y-1">
                        <input type="file" name="sample_media"
                               class="file-input file-input-bordered file-input-sm w-full"
                               accept="image/jpeg,image/png,video/mp4,video/3gpp,application/pdf">
                        <p class="text-xs opacity-60">
                            A sample file Meta uses for review — <span class="opacity-100">not sent to customers</span>.
                            Image ≤ 5 MB, video ≤ 16 MB, PDF ≤ 100 MB.
                        </p>
                        @error('sample_media')<p class="text-error text-xs">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="label"><span class="label-text">Body</span></label>
                    <textarea name="body" rows="4" class="textarea textarea-bordered w-full" required
                              placeholder="Hello {{ $v1 }}, your order {{ $v2 }} has been dispatched.">{{ old('body') }}</textarea>
                    <p class="text-xs opacity-60 mt-1">Use {{ $v1 }}, {{ $v2 }} … for variables — sequential, no gaps.</p>
                    @error('body')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="label"><span class="label-text">Footer (optional)</span></label>
                    <input name="footer" value="{{ old('footer') }}" class="input input-bordered w-full" maxlength="60">
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <label class="label"><span class="label-text">Buttons (optional)</span></label>
                        <div class="flex gap-1">
                            <button type="button" class="btn btn-xs" x-on:click="buttons = Math.min(10, buttons + 1)">Add</button>
                            <button type="button" class="btn btn-xs btn-ghost" x-on:click="buttons = Math.max(0, buttons - 1)">Remove</button>
                        </div>
                    </div>
                    <template x-for="i in buttons" :key="i">
                        <div class="grid grid-cols-3 gap-2 mb-2">
                            <select :name="`buttons[${i-1}][type]`" class="select select-bordered select-sm">
                                <option value="quick_reply">Quick reply</option>
                                <option value="url">URL</option>
                                <option value="phone">Phone</option>
                            </select>
                            <input :name="`buttons[${i-1}][text]`" class="input input-bordered input-sm" placeholder="Button text" maxlength="25">
                            <input :name="`buttons[${i-1}][url]`" class="input input-bordered input-sm" placeholder="https:// or phone">
                        </div>
                    </template>
                </div>

                @if ($errors->any())
                    <div class="alert alert-error text-sm">
                        <ul class="list-disc ml-4">
                            @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <div class="flex gap-2">
                    <button class="btn btn-primary"><i class="ti ti-send"></i> Submit to Meta</button>
                    <a href="{{ route('whatsapp.templates.index') }}" class="btn btn-ghost">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
