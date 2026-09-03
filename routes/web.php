<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Controllers\Admin\HealthController;
use App\Http\Controllers\Admin\SystemControlController;
use App\Http\Controllers\Admin\WebhookEventController;
use App\Http\Controllers\Campaigns\CampaignController;
use App\Http\Controllers\Campaigns\CampaignReportController;
use App\Http\Controllers\Contacts\ContactController;
use App\Http\Controllers\Contacts\ContactExportController;
use App\Http\Controllers\Contacts\ContactGroupController;
use App\Http\Controllers\Contacts\ContactImportController;
use App\Http\Controllers\Contacts\UnsubscribeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\Whatsapp\MediaController;
use App\Http\Controllers\Whatsapp\MessageController;
use App\Http\Controllers\Whatsapp\PhoneNumberController;
use App\Http\Controllers\Whatsapp\TemplateController;
use App\Http\Controllers\Whatsapp\TestSendController;
use App\Http\Controllers\Whatsapp\WabaSettingsController;
use App\Models\Contact;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

// Public health probe — no secrets, no auth.
Route::get('/health', [HealthController::class, 'ping']);

// Public, signed unsubscribe page (tenant scope bypassed for binding).
Route::bind('publicContact', fn ($id) => Contact::query()->withoutGlobalScopes()->findOrFail($id));
Route::middleware('signed')->group(function () {
    Route::get('/unsubscribe/{publicContact}', [UnsubscribeController::class, 'show'])->name('unsubscribe');
    Route::post('/unsubscribe/{publicContact}', [UnsubscribeController::class, 'update'])->name('unsubscribe.confirm');
});

// Authenticated, non-tenant routes (profile settings).
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

// Authenticated routes that operate on tenant-scoped data.
Route::middleware(['auth', 'tenant'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
        Route::view('/', 'whatsapp.overview')->name('overview');

        Route::get('reports', [ReportsController::class, 'index'])->name('reports.index');

        Route::get('settings', [WabaSettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [WabaSettingsController::class, 'update'])->name('settings.update');
        Route::post('settings/check', [WabaSettingsController::class, 'check'])->name('settings.check');

        Route::get('phone-numbers', [PhoneNumberController::class, 'index'])->name('phone-numbers.index');
        Route::post('phone-numbers/sync', [PhoneNumberController::class, 'sync'])->name('phone-numbers.sync');
        Route::post('phone-numbers/{phoneNumber}/default', [PhoneNumberController::class, 'setDefault'])->name('phone-numbers.default');

        // Templates
        Route::get('templates', [TemplateController::class, 'index'])->name('templates.index');
        Route::get('templates/create', [TemplateController::class, 'create'])->name('templates.create');
        Route::post('templates', [TemplateController::class, 'store'])->name('templates.store');
        Route::post('templates/sync', [TemplateController::class, 'sync'])->name('templates.sync');
        Route::get('templates/{template}', [TemplateController::class, 'show'])->name('templates.show');
        Route::post('templates/{template}/header-sample', [TemplateController::class, 'headerSample'])->name('templates.header-sample');
        Route::delete('templates/{template}', [TemplateController::class, 'destroy'])->name('templates.destroy');

        // Test send
        Route::get('test-send', [TestSendController::class, 'create'])->name('test-send.create');
        Route::post('test-send', [TestSendController::class, 'store'])->name('test-send.store');

        // Messages / conversation viewer
        Route::get('messages', [MessageController::class, 'index'])->name('messages.index');
        Route::get('messages/{message}', [MessageController::class, 'show'])->name('messages.show');

        // Media library
        Route::get('media', [MediaController::class, 'index'])->name('media.index');
        Route::post('media', [MediaController::class, 'store'])->name('media.store');
        Route::get('media/{media}', [MediaController::class, 'show'])->name('media.show');
        Route::delete('media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');

        // Contacts
        Route::get('contacts/export', ContactExportController::class)->name('contacts.export');
        Route::get('contacts/import', [ContactImportController::class, 'create'])->name('contacts.import.create');
        Route::post('contacts/import', [ContactImportController::class, 'store'])->name('contacts.import.store');
        Route::get('contacts/import/{import}/map', [ContactImportController::class, 'map'])->name('contacts.import.map');
        Route::post('contacts/import/{import}/analyze', [ContactImportController::class, 'analyze'])->name('contacts.import.analyze');
        Route::get('contacts/import/{import}', [ContactImportController::class, 'show'])->name('contacts.import.show');
        Route::post('contacts/import/{import}/commit', [ContactImportController::class, 'commit'])->name('contacts.import.commit');
        Route::get('contacts/import/{import}/errors', [ContactImportController::class, 'errors'])->name('contacts.import.errors');

        Route::get('contacts', [ContactController::class, 'index'])->name('contacts.index');
        Route::get('contacts/create', [ContactController::class, 'create'])->name('contacts.create');
        Route::post('contacts', [ContactController::class, 'store'])->name('contacts.store');
        Route::get('contacts/{contact}', [ContactController::class, 'show'])->name('contacts.show');
        Route::put('contacts/{contact}', [ContactController::class, 'update'])->name('contacts.update');
        Route::delete('contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');
        Route::post('contacts/{contact}/opt-in', [ContactController::class, 'optIn'])->name('contacts.opt-in');
        Route::post('contacts/{contact}/opt-out', [ContactController::class, 'optOut'])->name('contacts.opt-out');

        // Groups
        Route::get('groups', [ContactGroupController::class, 'index'])->name('groups.index');
        Route::post('groups', [ContactGroupController::class, 'store'])->name('groups.store');
        Route::put('groups/{group}', [ContactGroupController::class, 'update'])->name('groups.update');
        Route::delete('groups/{group}', [ContactGroupController::class, 'destroy'])->name('groups.destroy');
        Route::post('groups/assign', [ContactGroupController::class, 'assign'])->name('groups.assign');

        // Campaigns
        Route::get('campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
        Route::post('campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
        Route::get('campaigns/{campaign}/edit', [CampaignController::class, 'edit'])->name('campaigns.edit');
        Route::put('campaigns/{campaign}', [CampaignController::class, 'update'])->name('campaigns.update');
        Route::get('campaigns/{campaign}/audience-preview', [CampaignController::class, 'audiencePreview'])->name('campaigns.audience-preview');
        Route::post('campaigns/{campaign}/test', [CampaignController::class, 'test'])->name('campaigns.test');
        Route::post('campaigns/{campaign}/launch', [CampaignController::class, 'launch'])->name('campaigns.launch');
        Route::post('campaigns/{campaign}/pause', [CampaignController::class, 'pause'])->name('campaigns.pause');
        Route::post('campaigns/{campaign}/resume', [CampaignController::class, 'resume'])->name('campaigns.resume');
        Route::post('campaigns/{campaign}/cancel', [CampaignController::class, 'cancel'])->name('campaigns.cancel');
        Route::delete('campaigns/{campaign}', [CampaignController::class, 'destroy'])->name('campaigns.destroy');
        Route::get('campaigns/{campaign}/report', [CampaignReportController::class, 'show'])->name('campaigns.report');
        Route::get('campaigns/{campaign}/report/export', [CampaignReportController::class, 'export'])->name('campaigns.report.export');
    });

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('health', [HealthController::class, 'show'])
            ->middleware('can:'.Permission::OrgManage->value)
            ->name('health');

        Route::middleware('can:'.Permission::OrgManage->value)->group(function () {
            Route::get('controls', [SystemControlController::class, 'index'])->name('controls');
            Route::post('controls/sending', [SystemControlController::class, 'toggleSending'])->name('controls.sending');
            Route::post('controls/pause-campaigns', [SystemControlController::class, 'pauseAllCampaigns'])->name('controls.pause-campaigns');
        });

        Route::middleware('can:'.Permission::AuditView->value)->group(function () {
            Route::get('webhook-events', [WebhookEventController::class, 'index'])->name('webhook-events.index');
            Route::get('webhook-events/{webhookEvent}', [WebhookEventController::class, 'show'])->name('webhook-events.show');
        });

        // Platform-level destructive action.
        Route::post('controls/revoke/{account}', [SystemControlController::class, 'revokeIntegration'])
            ->middleware('super-admin')
            ->name('controls.revoke');
    });
});

require __DIR__.'/auth.php';
