<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TenantManager;
use Illuminate\Support\Facades\Auth;

class BootstrapController extends Controller
{
    public function __invoke(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $tenantManager = app(TenantManager::class);

        $user->load('roles', 'permissions');
        $permissions = $user->getAllPermissions()->pluck('name');

        $moduleSlugs = $tenantManager->getEnabledModules();

        // Map slugs to rich UI objects for the sidebar
        $moduleNav = collect($moduleSlugs)->map(function ($slug) {
            return $this->getModuleMetadata($slug);
        })->filter()->values();

        return response()->json([
            'user' => $user,
            'permissions' => $permissions,
            'active_tenant' => $tenantManager->getTenant(),
            'enabled_modules' => $moduleSlugs,
            'module_nav' => $moduleNav, // This was previously empty
        ]);
    }

    private function getModuleMetadata($slug)
    {
        $metadata = [
            'leads' => [
                'label' => 'Opportunities',
                'route' => '/admin/leads',
                'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'
            ],
            'forms' => [
                'label' => 'Forms',
                'route' => '/admin/forms',
                'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'
            ],
            'webhooks' => [
                'label' => 'Webhooks',
                'route' => '/admin/webhooks',
                'icon' => 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1'
            ],
            'ai_chats' => [
                'label' => 'AI Chats',
                'route' => '/admin/ai-chats',
                'icon' => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z'
            ],
            'api_keys' => [
                'label' => 'API Keys',
                'route' => '/admin/api-keys',
                'icon' => 'M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-9 0V4a1 1 0 011-1h4a1 1 0 011 1v2m-9 0a1 1 0 00-1 1v1m10-1v-1a1 1 0 011-1h4a1 1 0 011 1v1m-9 0a1 1 0 00-1 1v10M20 7H9.75M9.75 7a.75.75 0 110-1.5.75.75 0 010 1.5zM12.75 12a.75.75 0 110-1.5.75.75 0 010 1.5zM12.75 15a.75.75 0 110-1.5.75.75 0 010 1.5zM15.75 18a.75.75 0 110-1.5.75.75 0 010 1.5z'
            ],
        ];

        return isset($metadata[$slug]) ? array_merge(['slug' => $slug], $metadata[$slug]) : null;
    }
}
