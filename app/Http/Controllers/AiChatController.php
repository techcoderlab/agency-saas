<?php

namespace App\Http\Controllers;

use App\Models\AiChat;
use App\Models\ChatMessage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiChatController extends Controller
{
    use AuthorizesRequests; // Laravel 11 may put this in base Controller

    public function index()
    {
        $this->authorize('viewAny', AiChat::class);

        // Trait automatically filters by tenant_id
        return response()->json(AiChat::latest()->get());
    }

    public function store(Request $request)
    {
        $this->authorize('create', AiChat::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'webhook_url' => 'required|url',
            'webhook_secret' => 'nullable|string',
            'welcome_message' => 'nullable|string',
        ]);

        $chat = AiChat::create($validated);
        return response()->json($chat, 201);
    }

    public function update(Request $request, AiChat $aiChat)
    {
        // Policy authorization check recommended here (e.g., $this->authorize('update', $aiChat))
        $this->authorize('update', $aiChat);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'webhook_url' => 'required|url',
            'webhook_secret' => 'nullable|string',
            'welcome_message' => 'nullable|string',
        ]);

        $aiChat->update($validated);
        return response()->json($aiChat);
    }

    public function destroy(AiChat $aiChat)
    {
        $this->authorize('delete', $aiChat);

        $aiChat->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }

    /**
     * Get Chat History with Pagination (Cursor-based)
     */
    public function history(Request $request, AiChat $aiChat)
    {

        $this->authorize('view', $aiChat);

        $limit = 25; // Strict limit
        $beforeId = $request->input('before_id'); // The cursor

        // 1. Build Query (Latest messages first)
        $query = ChatMessage::where('ai_chat_id', $aiChat->id)
            ->where('user_id', Auth::id())
            ->orderBy('id', 'desc');

        // 2. Apply Cursor (Load messages OLDER than the top one)
        if ($beforeId) {
            $query->where('id', '<', $beforeId);
        }

        // 3. Fetch Data
        $messages = $query->take($limit)->get();

        // 4. Check if more exist (for infinite scroll)
        $lastMsg = $messages->last();
        $hasMore = $lastMsg ? ChatMessage::where('ai_chat_id', $aiChat->id)
            ->where('user_id', Auth::id())
            ->where('id', '<', $lastMsg->id)
            ->exists() : false;

        // 5. Transform for Deep Chat (Reverse to chronological: Old -> New)
        $formatted = $messages->reverse()->values()->map(function ($msg) {
            $m = ['role' => $msg->role, 'text' => $msg->content];
            if ($msg->files) $m['files'] = $msg->files;
            return $m;
        });

        return response()->json([
            'messages' => $formatted,
            'has_more' => $hasMore,
            'next_cursor' => $lastMsg ? $lastMsg->id : null
        ]);
    }

    /**
     * Check Webhook Status
     */
    public function checkConnection(AiChat $aiChat)
    {
        $this->authorize('view', $aiChat);

        try {
            $http = Http::timeout(3);
    
            if ($aiChat->webhook_secret) {
                $http->withHeaders(['Authorization' => $aiChat->webhook_secret]);
            }
    
            $start = microtime(true);
    
            // Universal non-triggering health check
            $response = $http->send('OPTIONS', $aiChat->webhook_url);
    
            $latency = round((microtime(true) - $start) * 1000, 2);
    
            $statusCode = $response->status();

            $isActive = in_array($statusCode, [200, 204]);

    
            return response()->json([
                'status' => $isActive ? 'active' : 'inactive',
                'code' => $statusCode,
                'latency_ms' => $latency,
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'inactive',
                'message' => 'Unreachable',
                'latency_ms' => null
            ]);
        }
    }



    // 5. PROXY (The Magic Part)
    // Front-end sends message here -> We forward to n8n -> Return n8n response
    public function chat(Request $request, AiChat $aiChat)
    {
        // --- DEBUG START: Log Incoming Data ---
        Log::info('--- AI Chat Request Start ---');
        Log::info('Inputs:', $request->except('files'));
        // --- DEBUG END ---

        $user = Auth::user();
        $url = $aiChat->webhook_url;
        
        $sessionId = "user_{$user->id}_agent_{$aiChat->id}";

        // 1. ROBUST TEXT EXTRACTION
        // Priority A: Check the 'text_content' field we added in the interceptor
        $userText = $request->input('text_content');

        // Priority B: Fallback to parsing 'messages' (if text-only JSON request)
        if (!$userText) {
            $rawMessages = $request->input('messages');
            // If it's a string (multipart), decode it
            if (is_string($rawMessages)) {
                $rawMessages = json_decode($rawMessages, true);
            }
            // If it's already an array (json request), use it
            $incoming = is_array($rawMessages) ? ($rawMessages[0] ?? []) : [];
            $userText = $incoming['text'] ?? '';
        }

        Log::info('Final Extracted Text:', ['text' => $userText]);

        // 2. Handle File Storage
        $n8nFiles = []; 
        $dbFiles = []; 

        if ($request->hasFile('files')) {
            // Force array to handle single/multiple files safely
            $uploadedFiles = $request->file('files');
            if (!is_array($uploadedFiles)) {
                $uploadedFiles = [$uploadedFiles];
            }

            foreach ($uploadedFiles as $file) {
                try {
                    $tenantId = $user->tenant_id ?? 'default';
                    $filename = \Illuminate\Support\Str::random(20) . '.' . $file->getClientOriginalExtension();
                    $folder = "tenants/{$tenantId}/chat_uploads/" . date('Y');
                    
                    $path = $file->storeAs($folder, $filename, 'public');
                    
                    if ($path) {
                        $fullUrl = asset('storage/' . $path);
                        $mime = $file->getMimeType();

                        $type = 'file';
                        if (str_starts_with($mime, 'image')) $type = 'image';
                        if (str_starts_with($mime, 'audio')) $type = 'audio';

                        $dbFiles[] = ['type' => $type, 'name' => $file->getClientOriginalName(), 'src' => $fullUrl];
                        $n8nFiles[] = ['filename' => $file->getClientOriginalName(), 'url' => $fullUrl, 'mimeType' => $mime];
                    }
                } catch (\Exception $e) {
                    Log::error("File Upload Error: " . $e->getMessage());
                }
            }
        }

        // 3. Save USER Message
        ChatMessage::create([
            'ai_chat_id' => $aiChat->id,
            'user_id' => $user->id,
            'role' => 'user',
            'content' => $userText,
            'files' => count($dbFiles) > 0 ? $dbFiles : null
        ]);

        // 4. Send Payload to n8n
        $payload = [
            'sessionId' => $sessionId,
            'text' => $userText, // This should now be populated
            'files' => $n8nFiles
        ];

        $http = Http::timeout(120);
        if ($aiChat->webhook_secret) {
            $http->withHeaders(['Authorization' => $aiChat->webhook_secret]);
        }

        try {
            $response = $http->post($url, $payload);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Connection Failed: ' . $e->getMessage()], 200);
        }

        if ($response->failed()) {
            return response()->json(['error' => 'AI Error: ' . $response->body()], 200);
        }

        // 5. Save AI Response
        $responseData = $response->json();
        
        $aiText = '';
        if (isset($responseData['output'])) $aiText = $responseData['output'];
        elseif (isset($responseData['text'])) $aiText = $responseData['text'];
        elseif (isset($responseData['message'])) $aiText = $responseData['message'];
        elseif (isset($responseData['data']) && is_string($responseData['data'])) $aiText = $responseData['data'];
        else $aiText = json_encode($responseData);

        ChatMessage::create([
            'ai_chat_id' => $aiChat->id,
            'user_id' => $user->id,
            'role' => 'ai',
            'content' => $aiText,
        ]);

        return response()->json($responseData);
    }
}