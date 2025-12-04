<?php

namespace App\Http\Controllers;

use App\Models\Webhook;
use Illuminate\Http\Request;
use App\Traits\BelongsToTenant; // Assuming this trait handles tenant scoping

class WebhookController extends Controller
{
    public function index(Request $request)
    {
        // Fetch webhooks for current tenant
        return Webhook::where('tenant_id', $request->user()->tenant_id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function store(Request $request)
    {
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
            
        $webhook->delete();

        return response()->noContent();
    }
}