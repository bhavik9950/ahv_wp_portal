<x-app-layout>
    <x-slot name="title">Media</x-slot>

    @php($canManage = auth()->user()->can(\App\Enums\Permission::CampaignManage->value) || auth()->user()->can(\App\Enums\Permission::TemplateManage->value))

    <div class="space-y-4">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <p class="text-sm opacity-70">
                Stored on the <span class="badge badge-outline badge-sm">{{ $disk }}</span> disk.
                Files are uploaded to Meta automatically the first time a campaign uses them.
            </p>
            @if ($canManage)
                <form method="POST" action="{{ route('whatsapp.media.store') }}" enctype="multipart/form-data" class="flex items-center gap-2">
                    @csrf
                    <input type="file" name="file" class="file-input file-input-bordered file-input-sm" required
                           accept="image/jpeg,image/png,video/mp4,video/3gpp,audio/*,application/pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt">
                    <button class="btn btn-sm btn-primary"><i class="ti ti-upload"></i> Upload</button>
                </form>
            @endif
        </div>

        @error('file')<div class="alert alert-error text-sm"><i class="ti ti-x"></i><span>{{ $message }}</span></div>@enderror

        <div class="grid gap-3 grid-cols-2 sm:grid-cols-3 lg:grid-cols-4">
            @forelse ($items as $media)
                <div class="card bg-base-100 border border-base-300">
                    <div class="card-body p-3">
                        <div class="flex items-center gap-2">
                            <i class="ti {{ ['image'=>'ti-photo','video'=>'ti-video','audio'=>'ti-music','document'=>'ti-file-text'][$media->category()] }} text-xl opacity-70"></i>
                            <div class="min-w-0">
                                <div class="text-sm truncate" title="{{ $media->original_name }}">{{ $media->original_name }}</div>
                                <div class="text-xs opacity-50">{{ $media->humanSize() }} · {{ $media->category() }}</div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between mt-2">
                            <a href="{{ app(\App\Services\WhatsApp\MediaLibrary::class)->temporaryUrl($media) }}"
                               target="_blank" rel="noopener" class="link text-xs">Preview</a>
                            @if ($canManage)
                                <form method="POST" action="{{ route('whatsapp.media.destroy', $media) }}" data-confirm="Delete this file?">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-xs btn-ghost text-error"><i class="ti ti-trash"></i></button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <p class="col-span-full text-center opacity-60 py-8">No media uploaded yet.</p>
            @endforelse
        </div>

        {{ $items->links() }}
    </div>
</x-app-layout>
