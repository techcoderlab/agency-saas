<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class ApiKeyController extends Controller
{
    /**
     * list of available permissions/scopes
     */
    const AVAILABLE_ABILITIES = [
        'leads:read',
        'leads:write',
        'forms:read',
        'forms:write',
    ];

   /**
     * List all active API keys for the user (Excluding internal app tokens).
     */
    public function index(Request $request)
    {
        // Return tokens with their metadata (not the secret key)
        // We filter out 'api' because that is the token name used by AuthController for the UI session
        return $request->user()->tokens()
            ->where('name', '!=', 'api') 
            ->select('id', 'name', 'abilities', 'last_used_at', 'created_at')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Create a new API key with specific permissions.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'abilities' => 'required|array',
            'abilities.*' => 'in:' . implode(',', self::AVAILABLE_ABILITIES)
        ]);

        // Create the token using Sanctum
        // The plainTextToken is ONLY available on this object instance
        $token = $request->user()->createToken($validated['name'], $validated['abilities']);

        return response()->json([
            'message' => 'API Key generated successfully',
            'token' => $token->plainTextToken, // The secret key (shown only once)
            'entry' => [
                'id' => $token->accessToken->id,
                'name' => $token->accessToken->name,
                'abilities' => $token->accessToken->abilities,
                'last_used_at' => null,
                'created_at' => now(),
            ]
        ], 201);
    }

    /**
     * Revoke (delete) an API key.
     */
    public function destroy(Request $request, string $tokenId)
    {
        // Ensure the user owns this token before deleting
        $request->user()->tokens()->where('id', $tokenId)->delete();

        return response()->json(['message' => 'API Key revoked']);
    }
}