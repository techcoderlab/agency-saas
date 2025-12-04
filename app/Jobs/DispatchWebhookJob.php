<?php

namespace App\Jobs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Webhook;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DispatchWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60];

    /**
     * @param Model|Collection $data Accepts a single Model OR a Collection of models
     */
    public function __construct(
        public Model|Collection $data, 
        public Webhook $webhook,
        public string $event
    ) {}

    public function handle(): void
    {
        if (!$this->webhook->is_active) {
            return;
        }

        // 1. Determine Payload Structure (Single vs List)
        if ($this->data instanceof Collection) {
            // Handle Empty Collection Edge Case
            if ($this->data->isEmpty()) {
                Log::warning("Webhook {$this->webhook->id} triggered with empty collection for event: {$this->event}");
                return;
            }

            // Pluralize key: e.g., 'leads'
            $className = Str::lower(class_basename($this->data->first()));
            $jsonKey = Str::plural($className); 
            $payloadData = $this->data->toArray();
            $logIdentifier = "Collection of {$jsonKey} (Count: {$this->data->count()})";
        } else {
            // Singular key: e.g., 'lead' (Backward Compatibility)
            $jsonKey = Str::lower(class_basename($this->data));
            $payloadData = $this->data->toArray();
            $logIdentifier = "{$jsonKey} #{$this->data->getKey()}";
        }

        // 2. Build Payload
        $payload = [
            'event' => $this->event,
            'timestamp' => now()->toIso8601String(),
            $jsonKey => $payloadData, // Dynamic key (lead vs leads)
        ];
        
        // 3. Prepare Headers & Security
        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'AgencySaaS-Webhook/1.0'
        ];

        if ($this->webhook->secret) {
            $signature = hash_hmac('sha256', json_encode($payload), $this->webhook->secret);
            $headers['X-Webhook-Signature'] = $signature;
        }

        // 4. Send Request
        try {
            Http::withHeaders($headers)
                ->timeout(10)
                ->post($this->webhook->url, $payload)
                ->throw();
        } catch (\Exception $e) {
            Log::error("Webhook {$this->webhook->id} failed for {$logIdentifier}: " . $e->getMessage());
            throw $e; // Trigger retry mechanism
        }
    }
}