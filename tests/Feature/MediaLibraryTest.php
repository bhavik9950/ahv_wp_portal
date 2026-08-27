<?php

declare(strict_types=1);

use App\Models\Media;
use App\Models\WhatsappBusinessAccount;
use App\Models\WhatsappPhoneNumber;
use App\Services\WhatsApp\MediaLibrary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('stores an uploaded image on the configured (local) disk', function () {
    $org = makeOrganization();
    $manager = makeMember($org, 'campaign_manager');
    Storage::fake('local');

    $file = UploadedFile::fake()->image('promo.jpg', 400, 300);

    $this->actingAs($manager)->post(route('whatsapp.media.store'), ['file' => $file])
        ->assertRedirect();

    $media = Media::sole();
    expect($media->disk)->toBe('local')
        ->and($media->category())->toBe('image')
        ->and($media->mime_type)->toBe('image/jpeg');
    Storage::disk('local')->assertExists($media->path);
});

it('rejects a disallowed file type', function () {
    $org = makeOrganization();
    $manager = makeMember($org, 'campaign_manager');
    Storage::fake('local');

    $bad = UploadedFile::fake()->create('run.exe', 10, 'application/x-msdownload');

    $this->actingAs($manager)
        ->from(route('whatsapp.media.index'))
        ->post(route('whatsapp.media.store'), ['file' => $bad])
        ->assertSessionHasErrors('file');

    expect(Media::count())->toBe(0);
});

it('rejects an image that is over the WhatsApp size limit', function () {
    $org = makeOrganization();
    bindTenant($org);
    Storage::fake('local');

    // 6 MB "jpeg" — over the 5 MB image cap.
    $big = UploadedFile::fake()->create('huge.jpg', 6144, 'image/jpeg');

    expect(fn () => app(MediaLibrary::class)->store($big))
        ->toThrow(RuntimeException::class, 'larger than WhatsApp allows');
});

it('serves a stored file only with a valid signature', function () {
    $org = makeOrganization();
    $viewer = makeMember($org, 'campaign_manager');
    Storage::fake('local');
    $media = Media::factory()->for($org)->create(['disk' => 'local', 'path' => 'media/x.jpg']);
    Storage::disk('local')->put('media/x.jpg', 'binarydata');

    $signed = app(MediaLibrary::class)->temporaryUrl($media);

    $this->actingAs($viewer)->get($signed)->assertOk();
    $this->actingAs($viewer)->get(route('whatsapp.media.show', $media))->assertForbidden(); // unsigned
});

it('uploads to Meta once and caches the id until it expires', function () {
    $org = makeOrganization();
    bindTenant($org);
    $account = WhatsappBusinessAccount::factory()->for($org)->create();
    WhatsappPhoneNumber::factory()->forAccount($account)->create(['is_default' => true]);
    Storage::fake('local');
    $media = Media::factory()->for($org)->create(['disk' => 'local', 'path' => 'media/y.jpg']);
    Storage::disk('local')->put('media/y.jpg', 'jpegbytes');

    $lib = app(MediaLibrary::class);
    $id1 = $lib->ensureMetaId($media->fresh(), $account);
    $id2 = $lib->ensureMetaId($media->fresh(), $account);

    expect($id1)->toStartWith('mock-media-')
        ->and($id2)->toBe($id1)
        ->and($media->fresh()->meta_media_expires_at)->not->toBeNull();
});
