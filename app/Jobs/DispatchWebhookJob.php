<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Models\Webhook;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DispatchWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60];

    public function __construct(
        public Lead $lead,
        public Webhook $webhook,
        public string $event
    ) {}

    public function handle(): void
    {
        if (!$this->webhook->is_active) {
            return;
        }

        // Payload structure
        $payload = [
            'event' => $this->event,
            'timestamp' => now()->toIso8601String(),
            'lead' => $this->lead->toArray(),
        ];
        
        // Security: Sign the payload if a secret exists
        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'AgencySaaS-Webhook/1.0'
        ];

        if ($this->webhook->secret) {
            $signature = hash_hmac('sha256', json_encode($payload), $this->webhook->secret);
            $headers['X-Webhook-Signature'] = $signature;
        }

        try {
            Http::withHeaders($headers)
                ->timeout(10)
                ->post($this->webhook->url, $payload)
                ->throw();
        } catch (\Exception $e) {
            Log::error("Webhook {$this->webhook->id} failed for Lead {$this->lead->id}: " . $e->getMessage());
            throw $e; // Trigger retry
        }
    }
}