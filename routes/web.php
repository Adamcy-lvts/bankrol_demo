<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

// Static design-prototype demo served from public/ (shown to potential clients).
Route::get('/bamus', fn () => response()->file(public_path('bamus/index.html')));

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
