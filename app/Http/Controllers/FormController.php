<?php

namespace App\Http\Controllers;

use App\Models\Form;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FormController extends Controller
{
    public function index(Request $request)
    {
        if (! $request->user()->tokenCan('forms:read')) {
            abort(403, 'Access denied: Missing "forms:read" permission');
        }

        return Form::orderByDesc('created_at')->get();
    }

    public function store(Request $request)
    {

        $user = $request->user();

        if (! $user || $user->isSuperAdmin()) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        if (! $request->user()->tokenCan('forms:write')) {
            abort(403, 'Access denied: Missing "forms:write" permission');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'schema' => ['required', 'array'],
            'webhook_url' => ['nullable', 'url', 'max:2048'],
            'webhook_secret' => ['nullable', 'string', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

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
        $user = $request->user();

        if (! $user || $user->isSuperAdmin()) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        if (! $request->user()->tokenCan('forms:write')) {
            abort(403, 'Access denied: Missing "forms:write" permission');
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'schema' => ['sometimes', 'array'],
            'webhook_url' => ['sometimes','nullable', 'url', 'max:2048'],
            'webhook_secret' => ['sometimes','nullable', 'string', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $form->fill($validated);
        $form->save();

        return response()->json($form);
    }

    public function destroy(Request $request, Form $form)
    {
        $user = $request->user();

        if (! $user || $user->isSuperAdmin()) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        if (! $request->user()->tokenCan('forms:write')) {
            abort(403, 'Access denied: Missing "forms:write" permission');
        }

        $form->delete();

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}


