<?php

namespace App\Http\Controllers;

use App\Jobs\DispatchWebhookBatchJob;
use App\Jobs\SendToN8NJob;
use App\Models\Form;
use App\Models\Lead;
use App\Models\Webhook;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class PublicFormController extends Controller
{
    public function show(string $uuid)
    {
        $form = Form::where('id', $uuid)
            ->where('is_active', true)
            ->first();

        // Manual check prevents default Laravel 404 page, returns JSON instead
        if (!$form) {
            return response()->json(['message' => 'This form is not found or temporarily closed.'], 404);
        }

        return response()->json([
            'id' => $form->id,
            'name' => $form->name,
            'schema' => $form->schema,
        ]);
    }

    public function submit(Request $request, string $uuid)
    {
        // 1. Fast lookup (avoid two queries)
        $form = Form::where('id', $uuid)
            ->where('is_active', true)
            ->first();

        if (!$form) {     
            return response()->json(['message' => 'This form is not found or temporarily closed.'], 404); 
        }
        
        // 2. Create lead in one clean call
        $lead = Lead::create([
            'tenant_id' => $form->tenant_id,
            'form_id'   => $form->id,
            'source'    => 'form',
            'payload'   => $request->all(),
        ]);

        // 3. Prepare webhook payload (small & clean)
        $payload = Arr::only($lead->toArray(), [
            'id',
            'payload',
            'source',
            'temperature',
            'status',
            'meta_data',
        ]);

        // 4. Cached webhooks (high-performance, correct JSON matching)
        $webhooks = Cache::remember(
            "form:webhooks:{$form->getKey()}",
            60,
            fn() => $form->webhooks()
                ->whereJsonContains('events', 'form.submission')
                ->get()
        );

        // 5. Dispatch webhooks to queue for async execution
        DispatchWebhookBatchJob::dispatch(
            data: $payload,
            webhooks: $webhooks,
            event: 'form.submission'
        );

        return response()->json([
            'message' => 'Submitted successfully.',
            'lead_id' => $lead->id,
        ], 201);
    }

}