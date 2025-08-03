<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    /**
     * Display the tenant selector page.
     */
    public function index(): Response
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $tenants = $user->tenants()->get();

        return Inertia::render('tenant-selector', [
            'tenants' => $tenants,
            'currentTenant' => app('currentTenant'),
        ]);
    }

    /**
     * Switch to a different tenant.
     */
    public function switch(Request $request): RedirectResponse
    {
        $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
        ]);

        $user = \Illuminate\Support\Facades\Auth::user();
        $tenant = $user->tenants()->findOrFail($request->tenant_id);

        session()->flush();     // clear *all* session data
        Auth::login($user, true);  // log them back in (second param keeps "remember me")
        session()->put('tenantId', $tenant->id);
        $tenant->makeCurrent();

        return redirect()->back()->with('success', "Switched to tenant: {$tenant->name}");
    }

    /**
     * Get available tenants for the current user.
     */
    public function available(): \Illuminate\Http\JsonResponse
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $tenants = $user->tenants()->get();

        return response()->json([
            'tenants' => $tenants,
            'currentTenant' => app('currentTenant'),
        ]);
    }
} 