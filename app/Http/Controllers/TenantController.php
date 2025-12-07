<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\TenantSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TenantController extends Controller
{

    public function getModulesForTenant(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        if($user->isNotSuperAdmin()){
            return response()->json(array_values(array_filter(config('modules.available_modules'), function ($value) use ($user) {
                return in_array($value['id'], $user->tenant->enabled_modules);
            })));
            
        }

        return response()->json(config('modules.available_modules'));
    }

    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user || ! $user->isSuperAdmin()) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        // Eager load settings to get crm_config
        return response()->json([
            'tenants' => Tenant::with('settings')->orderByDesc('id')->get(), 
            'available_modules' => config('modules.available_modules')
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if (! $user || ! $user->isSuperAdmin()) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        // 1. Validate Base Fields
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['nullable', 'string', 'max:255', 'unique:tenants,domain'],
            'status' => ['nullable', 'in:active,suspended'],
            'enabled_modules' => ['array'], 
            'enabled_modules.*' => ['string'],
            
            // 2. Validate CRM Config Structure (Strict)
            'crm_config' => ['nullable', 'array'],
            'crm_config.entity_name_singular' => ['required_with:crm_config', 'string'],
            'crm_config.entity_name_plural' => ['required_with:crm_config', 'string'],
            'crm_config.statuses' => ['required_with:crm_config', 'array', 'min:1'],
            'crm_config.statuses.*.slug' => ['required_with:crm_config', 'string', 'distinct'],
            'crm_config.statuses.*.label' => ['required_with:crm_config', 'string'],
            'crm_config.statuses.*.color' => ['required_with:crm_config', 'string'],
        ]);

        $tenant = Tenant::create([
            'name' => $validated['name'],
            'domain' => $validated['domain'] ?? null,
            'status' => $validated['status'] ?? 'active',
            'enabled_modules' => $validated['enabled_modules'] ?? ['leads', 'forms'], 
        ]);

        // Save CRM Config if provided
        if (isset($validated['crm_config'])) {
            TenantSetting::create([
                'tenant_id' => $tenant->id,
                'crm_config' => $validated['crm_config']
            ]);
        }

        return response()->json($tenant->load('settings'), Response::HTTP_CREATED);
    }

    public function update(Request $request, Tenant $tenant)
    {
        $user = $request->user();

        if (! $user || ! $user->isSuperAdmin()) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'domain' => ['sometimes', 'nullable', 'string', 'max:255', 'unique:tenants,domain,' . $tenant->id],
            'status' => ['sometimes', 'in:active,suspended'],
            'enabled_modules' => ['sometimes', 'array'],
            
            // Strict Validation for Updates too
            'crm_config' => ['nullable', 'array'],
            'crm_config.entity_name_singular' => ['required_with:crm_config', 'string'],
            'crm_config.entity_name_plural' => ['required_with:crm_config', 'string'],
            'crm_config.statuses' => ['required_with:crm_config', 'array', 'min:1'],
            'crm_config.statuses.*.slug' => ['required_with:crm_config', 'string', 'distinct'],
            'crm_config.statuses.*.label' => ['required_with:crm_config', 'string'],
            'crm_config.statuses.*.color' => ['required_with:crm_config', 'string'],
        ]);

        $tenant->update($validated); 

        // Update CRM Config if provided
        if (isset($validated['crm_config'])) {
            TenantSetting::updateOrCreate(
                ['tenant_id' => $tenant->id],
                ['crm_config' => $validated['crm_config']]
            );
        }

        return response()->json($tenant->load('settings'));

    }

    public function destroy(Request $request, Tenant $tenant)
    {
        $user = $request->user();

        if (! $user || ! $user->isSuperAdmin()) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $tenant->delete();

        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    public function updateCrmConfig(Request $request)
    {
        $validated = $request->validate([
            'entity_name_singular' => 'required|string|max:50',
            'entity_name_plural' => 'required|string|max:50',
            'statuses' => 'required|array|min:1',
            'statuses.*.slug' => 'required|string|distinct',
            'statuses.*.label' => 'required|string',
            'statuses.*.color' => 'required|string'
        ]);

        $settings = TenantSetting::firstOrCreate(
            ['tenant_id' => $request->user()->tenant_id]
        );

        $settings->crm_config = $validated;
        $settings->save();

        return response()->json($settings->crm_config);
    }
}


