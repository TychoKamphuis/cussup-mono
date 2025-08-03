<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\TenantController;

Route::withoutMiddleware([
    \Spatie\Multitenancy\Http\Middleware\NeedsTenant::class,    
    \Spatie\Multitenancy\Http\Middleware\EnsureValidTenantSession::class
])->get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    // Tenant routes
    Route::get('tenants', [TenantController::class, 'index'])->name('tenants.index');
    Route::post('tenants/switch', [TenantController::class, 'switch'])->name('tenants.switch');
    Route::get('tenants/available', [TenantController::class, 'available'])->name('tenants.available');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
