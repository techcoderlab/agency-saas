<?php

namespace App\Http\Controllers;

use App\Models\Webhook;
use Illuminate\Http\Request;
use App\Traits\BelongsToTenant; // Assuming this trait handles tenant scoping
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class WebhookController extends Controller
{
    use AuthorizesRequests; // Laravel 11 may put this in base Controller

    public function index(Request $request)
    {
        $this->authorize('viewAny', Webhook::class);

        // Fetch webhooks for current tenant
        return Webhook::where('tenant_id', $request->user()->tenant_id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function store(Request $request)
    {
        $this->authorize('create', Webhook::class);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'url' => 'required|url',
            'secret' => 'nullable|string|max:255',
            'events' => 'required|array',
            'events.*' => 'string'
        ]);

        $webhook = new Webhook($validated);
        $webhook->tenant_id = $request->user()->tenant_id;
        $webhook->is_active = true;
        $webhook->save();

        return response()->json($webhook, 201);
    }

    public function destroy(Request $request, $id)
    {
        $webhook = Webhook::where('tenant_id', $request->user()->tenant_id)
            ->where('id', $id)
            ->firstOrFail();

        $this->authorize('delete', $webhook);
            
        $webhook->delete();

        return response()->noContent();
    }
}