<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserPreferencesController;
use App\Livewire\ProductCatalog;

use App\Livewire\ProductDetail;
use App\Livewire\MapPage;
use App\Livewire\EstimateSubmission;

// Guest Routes (Preferences - accessible before auth)
Route::get('/preferences/locale/{locale}', [UserPreferencesController::class, 'switch_locale'])
    ->name('preferences.locale');
Route::post('/preferences/theme/{theme}', [UserPreferencesController::class, 'switch_theme'])
    ->name('preferences.theme');

// Authenticated Routes Group
Route::middleware('auth')->group(function () {

    Route::get('/', ProductCatalog::class)->name('home');
    Route::get('/map', MapPage::class)->name('map');
    Route::get('/products/{product}', ProductDetail::class)->name('products.show');

    Route::get('/dashboard', fn() => view('dashboard'))->middleware('verified')->name('dashboard');

    Route::get('/profile',    [ProfileController::class, 'edit'])   ->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update']) ->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
});

// Auth
require __DIR__.'/auth.php';

