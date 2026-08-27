<?php

declare(strict_types=1);

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

// Authenticated, non-tenant routes (profile settings).
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

// Authenticated routes that operate on tenant-scoped data.
Route::middleware(['auth', 'tenant'])->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    // WhatsApp overview placeholder — module screens are added per phase.
    Route::view('/whatsapp', 'whatsapp.overview')->name('whatsapp.overview');
});

require __DIR__.'/auth.php';
