<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::withoutMiddleware([
    \Spatie\Multitenancy\Http\Middleware\NeedsTenant::class,    
    \Spatie\Multitenancy\Http\Middleware\EnsureValidTenantSession::class
])->get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
