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

        // Get raw modules from the tenant's plan
        $modules = $tenantManager->getEnabledModules(); // Assuming this returns slugs or objects

        // Map slugs to UI-ready navigation (Labels and SVG Paths)
        $moduleNav = collect($modules)->map(function ($slug) {
            return $this->getModuleMetadata($slug);
        })->filter();

        return response()->json([
            'user' => $user,
            'permissions' => $permissions,
            'active_tenant' => $tenantManager->getTenant(),
            'enabled_modules' => $modules, // Slugs for logic checks
            'module_nav' => $moduleNav, // Rich objects for the sidebar
        ]);
    }

    private function getModuleMetadata($slug)
    {
        $metadata = [
            'leads' => [
                'label' => 'Leads',
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
            'ai-chat' => [
                'label' => 'AI Chat',
                'route' => '/admin/ai-chat',
                'icon' => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z'
            ],
        ];

        return isset($metadata[$slug]) ? array_merge(['slug' => $slug], $metadata[$slug]) : null;
    }
}
