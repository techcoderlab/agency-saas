<?php

namespace App\Http\Controllers;

use App\Jobs\SendToN8NJob;
use App\Models\Form;
use App\Models\Lead;
use Illuminate\Http\Request;

class PublicFormController extends Controller
{
    public function show(string $uuid)
    {
        $form = Form::where('id', $uuid)
            ->where('is_active', true)
            ->firstOrFail();

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
            ->firstOrFail();

        $payload = $request->all();

        $lead = Lead::create([
            'tenant_id' => $form->tenant_id,
            'form_id' => $form->id,
            'payload' => $payload,
        ]);

        SendToN8NJob::dispatch($lead)->onQueue('database');

        return response()->json([
            'message' => 'Submitted successfully.',
            'lead_id' => $lead->id,
        ], 201);
    }
}


