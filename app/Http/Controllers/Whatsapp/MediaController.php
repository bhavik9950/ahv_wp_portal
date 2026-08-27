<?php

declare(strict_types=1);

namespace App\Http\Controllers\Whatsapp;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\WhatsApp\MediaLibrary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    public function __construct(private readonly MediaLibrary $library) {}

    public function index(): View
    {
        $this->authorize('viewAny', Media::class);

        return view('whatsapp.media.index', [
            'items' => Media::query()->latest()->paginate(24),
            'disk' => $this->library->disk(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Media::class);

        $request->validate([
            'file' => ['required', 'file', 'max:102400'], // 100 MB hard cap; MediaLibrary enforces per-type limits
        ]);

        try {
            $this->library->store($request->file('file'));
        } catch (\RuntimeException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        return back()->with('flash_notify', ['type' => 'success', 'message' => 'Media uploaded.']);
    }

    /** Serve a stored file for the local disk via a signed, time-limited URL. */
    public function show(Request $request, Media $media): StreamedResponse
    {
        abort_unless($request->hasValidSignature(), 403);

        $disk = Storage::disk($media->disk);
        abort_unless($disk->exists($media->path), 404);

        return $disk->response($media->path, $media->original_name, [
            'Content-Type' => $media->mime_type,
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    public function destroy(Media $media): RedirectResponse
    {
        $this->authorize('delete', $media);

        $this->library->delete($media);

        return back()->with('flash_notify', ['type' => 'success', 'message' => 'Media deleted.']);
    }
}
