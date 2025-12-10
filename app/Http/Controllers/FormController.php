<?php

namespace App\Http\Controllers;

use App\Models\Form;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FormController extends Controller
{
    use AuthorizesRequests; // Laravel 11 may put this in base Controller

    public function index(Request $request)
    {
        $this->authorize('viewAny', Form::class);
        return Form::with([
            'webhooks' => fn($hooks) => $hooks->select('id', 'form_id','name','url','is_active'),
        ])->orderByDesc('created_at')->get();
    }

    public function store(Request $request)
    {

        $this->authorize('create', Form::class);


        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'schema' => ['required', 'array'],
            'webhook_url' => ['nullable', 'url', 'max:2048'],
            'webhook_secret' => ['nullable', 'string', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        /* Assign explicitly null values to stop preventing user values as webhook functionality from Froms are disabled for now */
        $validated['webhook_url'] = null;
        $validated['webhook_secret'] = null;

        $form = Form::create([
            'name' => $validated['name'],
            'schema' => $validated['schema'],
            'webhook_url' => $validated['webhook_url'] ?? null,
            'webhook_secret' => $validated['webhook_secret'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json($form, Response::HTTP_CREATED);
    }

    public function update(Request $request, Form $form)
    {
        $this->authorize('update', $form);


        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'schema' => ['sometimes', 'array'],
            'webhook_url' => ['sometimes','nullable', 'url', 'max:2048'],
            'webhook_secret' => ['sometimes','nullable', 'string', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        /* Assign explicitly null values to stop preventing user values as webhook functionality from Froms are disabled for now */
        $validated['webhook_url'] = null;
        $validated['webhook_secret'] = null;

        $form->fill($validated);
        $form->save();

        return response()->json($form);
    }

    public function destroy(Request $request, Form $form)
    {
        $this->authorize('delete', $form);

        $form->delete();

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}


