<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Controllers\Admin\HealthController;
use App\Http\Controllers\Admin\SystemControlController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Whatsapp\PhoneNumberController;
use App\Http\Controllers\Whatsapp\WabaSettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

// Public health probe — no secrets, no auth.
Route::get('/health', [HealthController::class, 'ping']);

// Authenticated, non-tenant routes (profile settings).
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

// Authenticated routes that operate on tenant-scoped data.
Route::middleware(['auth', 'tenant'])->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
        Route::view('/', 'whatsapp.overview')->name('overview');

        Route::get('settings', [WabaSettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [WabaSettingsController::class, 'update'])->name('settings.update');
        Route::post('settings/check', [WabaSettingsController::class, 'check'])->name('settings.check');

        Route::get('phone-numbers', [PhoneNumberController::class, 'index'])->name('phone-numbers.index');
        Route::post('phone-numbers/sync', [PhoneNumberController::class, 'sync'])->name('phone-numbers.sync');
        Route::post('phone-numbers/{phoneNumber}/default', [PhoneNumberController::class, 'setDefault'])->name('phone-numbers.default');
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

        // Platform-level destructive action.
        Route::post('controls/revoke/{account}', [SystemControlController::class, 'revokeIntegration'])
            ->middleware('super-admin')
            ->name('controls.revoke');
    });
});

require __DIR__.'/auth.php';
