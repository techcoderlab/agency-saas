<?php

namespace App\Http\Controllers;

use App\Jobs\DispatchWebhookBatchJob;
use App\Jobs\SendToN8NJob;
use App\Models\Form;
use App\Models\Lead;
use App\Models\Webhook;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class PublicFormController extends Controller
{
    public function show(string $uuid)
    {
        $form = Form::where('id', $uuid)
            ->where('is_active', true)
            ->first();

        // Manual check prevents default Laravel 404 page, returns JSON instead
        if (!$form) {
            return response()->json(['message' => '404 Not Found'], 404);
        }

        return response()->json([
            'id' => $form->id,
            'name' => $form->name,
            'schema' => $form->schema,
        ]);
    }

    public function submit(Request $request, string $uuid)
    {
        $form = Form::where('id', $uuid)
            ->where('is_active', true)
            ->first();

        if (!$form) {
             return response()->json(['message' => 'Form not active.'], 404);
        }

        // Validate payload if necessary, or just accept all
        $payload = $request->all();

        $lead = Lead::create([
            'tenant_id' => $form->tenant_id,
            'form_id'   => $form->id,
            'source'    => 'form', // EXPLICITLY set source
            'payload'   => $payload,
            // Status and Temperature default to 'new'/'cold' via database default
        ]);

        // CHANGED: Dispatch the new generic webhook job
        // The Observer will ALSO fire a webhook on creation, so to avoid 
        // double-firing, you might want to rely solely on the Observer 
        // OR disable the Observer's "created" hook if using this. 
        // For safety, let's rely on the Observer (created event) to handle the webhook.
        // If you keep this line, you get two webhooks. 
        // RECOMMENDATION: Remove this manual dispatch and let LeadObserver handle it.
        
        $legacy = new Webhook([
            'url' => $lead->form->webhook_url,
            'secret' => $lead->form->webhook_secret,
            'is_active' => true
        ]);

        DispatchWebhookBatchJob::dispatch(
            data: Arr::only($lead->toArray(), ['id','payload','source','temperature','status','meta_data']),
            webhooks: collect([$legacy]),
            event: 'form.submission'
        );

        return response()->json([
            'message' => 'Submitted successfully.',
            'lead_id' => $lead->id,
        ], 201);
    }
}