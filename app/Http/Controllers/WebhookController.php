<?php

namespace App\Http\Controllers;

use App\Models\Webhook;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function index(Request $request)
    {
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
            'events.*' => 'string|in:lead.created,lead.updated.status,lead.updated.temperature,lead.updated'
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id;
        $validated['is_active'] = true;

        $webhook = Webhook::create($validated);

        return response()->json($webhook, 201);
    }

    public function destroy(Webhook $webhook)
    {
        // Policy check implied (BelongsToTenant trait usually handles global scope, but explicit check is good)
        if ($webhook->tenant_id !== request()->user()->tenant_id) {
            abort(403);
        }
        
        $webhook->delete();

        return response()->noContent();
    }
}