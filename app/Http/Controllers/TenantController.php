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

        return response()->json(['tenants' => Tenant::orderByDesc('id')->get(), 'available_modules' => config('modules.available_modules')]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if (! $user || ! $user->isSuperAdmin()) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['nullable', 'string', 'max:255', 'unique:tenants,domain'],
            'status' => ['nullable', 'in:active,suspended'],
            'enabled_modules' => ['array'], // Validate array
            'enabled_modules.*' => ['string'],
        ]);

        $tenant = Tenant::create([
            'name' => $validated['name'],
            'domain' => $validated['domain'] ?? null,
            'status' => $validated['status'] ?? 'active',
            'enabled_modules' => $validated['enabled_modules'] ?? ['leads', 'forms'], // Default
        ]);

        return response()->json($tenant, Response::HTTP_CREATED);
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
        ]);

        $tenant->update($validated); // Fillable handles JSON

        return response()->json($tenant);

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


