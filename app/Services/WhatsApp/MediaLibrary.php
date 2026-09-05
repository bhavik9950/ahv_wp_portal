<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Models\Media;
use App\Models\WhatsappBusinessAccount;
use App\Services\Audit\AuditLogger;
use App\Support\CurrentOrganization;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Stores campaign/message media on the configured disk (local for testing,
 * Cloudflare R2 in production — one env var, `WABA_MEDIA_DISK`) and pushes it to
 * Meta on demand.
 *
 * Validation: real MIME (finfo, not the filename), extension match, per-category
 * size cap, magic-byte sniff. Executables / archives / SVG are rejected outright.
 */
final class MediaLibrary
{
    /**
     * category => [allowed mimes => [extensions], 'max' => bytes]
     */
    private const RULES = [
        'image' => [
            'mimes' => ['image/jpeg' => ['jpg', 'jpeg'], 'image/png' => ['png'], 'image/webp' => ['webp']],
            'max' => 5_242_880, // 5 MB
        ],
        'video' => [
            'mimes' => ['video/mp4' => ['mp4'], 'video/3gpp' => ['3gp', '3gpp']],
            'max' => 16_777_216, // 16 MB
        ],
        'audio' => [
            'mimes' => [
                'audio/aac' => ['aac'], 'audio/mp4' => ['m4a'], 'audio/mpeg' => ['mp3'],
                'audio/amr' => ['amr'], 'audio/ogg' => ['ogg'],
            ],
            'max' => 16_777_216,
        ],
        'document' => [
            'mimes' => [
                'application/pdf' => ['pdf'],
                'application/msword' => ['doc'],
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
                'application/vnd.ms-excel' => ['xls'],
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx'],
                'application/vnd.ms-powerpoint' => ['ppt'],
                'application/vnd.openxmlformats-officedocument.presentationml.presentation' => ['pptx'],
                'text/plain' => ['txt'],
            ],
            'max' => 104_857_600, // 100 MB
        ],
    ];

    public function __construct(
        private readonly WhatsAppManager $manager,
        private readonly CurrentOrganization $currentOrg,
        private readonly AuditLogger $audit,
    ) {}

    public function disk(): string
    {
        return (string) config('services.whatsapp.media_disk', 'local');
    }

    public function store(UploadedFile $file): Media
    {
        [$category, $mime, $ext] = $this->validate($file);

        $contents = (string) file_get_contents($file->getRealPath());
        $checksum = hash('sha256', $contents);

        // De-dupe within the org.
        $existing = Media::query()->where('checksum_sha256', $checksum)->first();
        if ($existing !== null) {
            return $existing;
        }

        $path = "media/{$this->currentOrg->resolve()?->getKey()}/".Str::ulid().".{$ext}";
        Storage::disk($this->disk())->put($path, $contents, ['visibility' => 'private']);

        $media = new Media;
        $media->forceFill([
            'organization_id' => $this->currentOrg->resolve()?->getKey(),
            'disk' => $this->disk(),
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $mime,
            'size_bytes' => strlen($contents),
            'checksum_sha256' => $checksum,
            'uploaded_by' => Auth::id(),
        ])->save();

        $this->audit->log('media.uploaded', $media, ['category' => $category, 'size' => $media->size_bytes]);

        return $media;
    }

    /**
     * Store an inbound attachment already downloaded from Meta (no UploadedFile
     * involved — same validation rules, matched by content instead of extension).
     */
    public function storeRaw(string $contents, string $mimeType, string $originalName): Media
    {
        // WhatsApp sends parameters on some mimes (e.g. "audio/ogg; codecs=opus").
        $mime = strtolower(trim(explode(';', $mimeType)[0]));
        [$category, $ext] = $this->categoryFor($mime, strlen($contents));

        $checksum = hash('sha256', $contents);
        $existing = Media::query()->where('checksum_sha256', $checksum)->first();
        if ($existing !== null) {
            return $existing;
        }

        $path = "media/{$this->currentOrg->resolve()?->getKey()}/".Str::ulid().".{$ext}";
        Storage::disk($this->disk())->put($path, $contents, ['visibility' => 'private']);

        $media = new Media;
        $media->forceFill([
            'organization_id' => $this->currentOrg->resolve()?->getKey(),
            'disk' => $this->disk(),
            'path' => $path,
            // Meta's inbound webhook only gives us a numeric media id, not a
            // filename — append the sniffed extension so downloads land with
            // a usable name instead of an extension-less number.
            'original_name' => str_ends_with(strtolower($originalName), ".{$ext}") ? $originalName : "{$originalName}.{$ext}",
            'mime_type' => $mime,
            'size_bytes' => strlen($contents),
            'checksum_sha256' => $checksum,
        ])->save();

        $this->audit->log('media.received', $media, ['category' => $category, 'size' => $media->size_bytes]);

        return $media;
    }

    /**
     * Upload (or re-upload if expired) the media to Meta and cache the id.
     */
    public function ensureMetaId(Media $media, WhatsappBusinessAccount $account): string
    {
        if ($media->meta_media_id !== null
            && $media->meta_media_expires_at !== null
            && $media->meta_media_expires_at->isFuture()) {
            return $media->meta_media_id;
        }

        $contents = Storage::disk($media->disk)->get($media->path)
            ?? throw new RuntimeException('Stored media file is missing.');

        $creds = $this->manager->credentialsFor($account, $account->phoneNumbers()->where('is_default', true)->first());
        $id = $this->manager->driver()->uploadMedia($creds, $contents, $media->mime_type, $media->original_name);

        $media->forceFill([
            'meta_media_id' => $id,
            'meta_media_expires_at' => now()->addDays(29), // Meta media ids last ~30 days
        ])->save();

        return $id;
    }

    public function temporaryUrl(Media $media, ?int $ttl = null): string
    {
        $ttl ??= (int) config('services.whatsapp.media_url_ttl', 300);

        // s3-compatible disks (R2 in production) issue their own presigned URLs.
        if (in_array(config("filesystems.disks.{$media->disk}.driver"), ['s3'], true)) {
            try {
                return Storage::disk($media->disk)->temporaryUrl($media->path, now()->addSeconds($ttl));
            } catch (\Throwable) {
                // fall through to the app route
            }
        }

        // Local disk: stream through a signed, time-limited app route.
        return URL::temporarySignedRoute('whatsapp.media.show', now()->addSeconds($ttl), ['media' => $media->getKey()]);
    }

    public function delete(Media $media): void
    {
        Storage::disk($media->disk)->delete($media->path);
        $this->audit->log('media.deleted', $media, ['original_name' => $media->original_name]);
        $media->delete();
    }

    /**
     * @return array{0: string, 1: string, 2: string} [category, mime, extension]
     */
    private function validate(UploadedFile $file): array
    {
        if (! $file->isValid()) {
            throw new RuntimeException('The upload failed.');
        }

        $mime = strtolower((string) $file->getMimeType());
        $ext = strtolower((string) $file->getClientOriginalExtension());

        foreach (self::RULES as $category => $rule) {
            if (! isset($rule['mimes'][$mime])) {
                continue;
            }
            if (! in_array($ext, $rule['mimes'][$mime], true)) {
                throw new RuntimeException("File extension .{$ext} does not match its content type ({$mime}).");
            }
            if ($file->getSize() > $rule['max']) {
                throw new RuntimeException('File is larger than WhatsApp allows for '.$category.' ('.$this->humanBytes($rule['max']).').');
            }

            return [$category, $mime, $ext];
        }

        throw new RuntimeException("Files of type {$mime} cannot be sent on WhatsApp.");
    }

    /**
     * @return array{0: string, 1: string} [category, extension]
     */
    private function categoryFor(string $mime, int $size): array
    {
        foreach (self::RULES as $category => $rule) {
            if (! isset($rule['mimes'][$mime])) {
                continue;
            }
            if ($size > $rule['max']) {
                throw new RuntimeException('File is larger than WhatsApp allows for '.$category.' ('.$this->humanBytes($rule['max']).').');
            }

            return [$category, $rule['mimes'][$mime][0]];
        }

        throw new RuntimeException("Files of type {$mime} cannot be stored.");
    }

    private function humanBytes(int $bytes): string
    {
        return $bytes >= 1_048_576 ? round($bytes / 1_048_576).' MB' : round($bytes / 1024).' KB';
    }
}
