<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name ?? $user->username,
                    'username' => $user->username,
                    'role_user' => $user->role_user,
                    'store_id' => $user->store_id,
                    'project_id' => $user->project_id,
                    'permissions' => $user->limitationAccess ? [
                        'bank' => $user->limitationAccess->canAccess('bank'),
                        'purchase' => $user->limitationAccess->canAccess('purchase'),
                        'access_user' => $user->limitationAccess->canAccess('access_user'),
                        'dashboard' => $user->limitationAccess->canAccess('dashboard'),
                        'deposit' => $user->limitationAccess->canAccess('deposit'),
                        'cost_account' => $user->limitationAccess->canAccess('cost_account'),
                        'invoice' => $user->limitationAccess->canAccess('invoice'),
                        'expenses' => $user->limitationAccess->canAccess('expenses'),
                        'privacy_policy' => $user->limitationAccess->canAccess('privacy_policy'),
                        'terms_condition' => $user->limitationAccess->canAccess('terms_condition'),
                        'admin' => $user->limitationAccess->canAccess('admin'),
                        'user' => $user->limitationAccess->canAccess('user'),
                        'region' => $user->limitationAccess->canAccess('region'),
                        'product' => $user->limitationAccess->canAccess('product'),
                        'store' => $user->limitationAccess->canAccess('store'),
                        'contact' => $user->limitationAccess->canAccess('contact'),
                        'tags' => $user->limitationAccess->canAccess('tags'),
                        'delete_invoice' => $user->limitationAccess->canAccess('delete_invoice'),
                        'edit_invoice' => $user->limitationAccess->canAccess('edit_invoice'),
                        'bypass_date' => $user->limitationAccess->canAccess('bypass_date'),
                        'notifications' => $user->limitationAccess->canAccess('notifications'),
                        'delete_approve_payment' => $user->limitationAccess->canAccess('delete_approve_payment'),
                    ] : null,
                ] : null
            ],
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error'),
            ],
        ];
    }
}
