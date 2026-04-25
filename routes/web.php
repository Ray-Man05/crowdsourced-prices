<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserPreferencesController;
use App\Livewire\ProductCatalog;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


Route::get('/preferences/locale/{locale}', [UserPreferencesController::class, 'switch_locale'])
    ->name('preferences.locale');
Route::post('/preferences/theme/{theme}', [UserPreferencesController::class, 'switch_theme'])
    ->name('preferences.theme');

Route::get('/', ProductCatalog::class)->name('home')->middleware('auth');


use App\Livewire\MapPage;

Route::get('/map', MapPage::class)->name('map')->middleware('auth');

require __DIR__.'/auth.php';
