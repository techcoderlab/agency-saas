<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\TenantSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user || ! $user->isSuperAdmin()) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        return Tenant::orderByDesc('id')->get();
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
            'n8n_webhook_url' => ['nullable', 'url', 'max:2048'],
        ]);

        $tenant = Tenant::create([
            'name' => $validated['name'],
            'domain' => $validated['domain'] ?? null,
            'status' => $validated['status'] ?? 'active',
            'n8n_webhook_url' => $validated['n8n_webhook_url'] ?? null,
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
            'n8n_webhook_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
        ]);

        $tenant->fill($validated);
        $tenant->save();

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


