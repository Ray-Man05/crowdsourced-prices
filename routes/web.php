<?php

use App\Http\Controllers\LandingController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserPreferencesController;
use App\Livewire\ProductCatalog;
use App\Livewire\ProductDetail;
use App\Livewire\MapPage;
use App\Livewire\Admin\CategoryManager;
use App\Livewire\Admin\UnitManager;
use App\Livewire\Admin\ProductManager;
use App\Livewire\Dashboard;

// Public landing page
Route::get('/', [LandingController::class, 'show'])->name('landing');

// Guest Routes (Preferences - accessible before auth)
Route::get('/preferences/locale/{locale}', [UserPreferencesController::class, 'switch_locale'])
    ->name('preferences.locale');
Route::post('/preferences/theme/{theme}', [UserPreferencesController::class, 'switch_theme'])
    ->name('preferences.theme');

// Authenticated Routes Group
Route::middleware('auth')->group(function () {

    Route::get('/catalog', ProductCatalog::class)->name('catalog');
    Route::get('/map', MapPage::class)->name('map');
    Route::get('/products/{product}', ProductDetail::class)->name('products.show');

    // Route::get('/dashboard', fn() => view('dashboard'))->middleware('verified')->name('dashboard');

    Route::get('/profile',           [ProfileController::class, 'edit'])           ->name('profile.edit');
    Route::patch('/profile',         [ProfileController::class, 'update'])         ->name('profile.update');
    Route::patch('/profile/location',[ProfileController::class, 'updateLocation'])->name('profile.location');
    Route::delete('/profile',        [ProfileController::class, 'destroy'])        ->name('profile.destroy');
    
});

Route::get('/dashboard', Dashboard::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/categories', CategoryManager::class)->name('categories');
    Route::get('/units',      UnitManager::class)    ->name('units');
    Route::get('/products',   ProductManager::class) ->name('products');
});

// Auth
require __DIR__.'/auth.php';

