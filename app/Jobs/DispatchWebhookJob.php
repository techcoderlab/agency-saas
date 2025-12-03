<?php

namespace App\Jobs;

use App\Models\Lead;
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

    public function __construct(public Lead $lead)
    {}

    public function handle(): void
    {
        $form = $this->lead->form;

        if (!$form || !$form->webhook_url) {
            return;
        }

        $payload = $this->lead->toArray();
        
        // Security: Sign the payload if a secret exists
        $headers = ['Content-Type' => 'application/json'];
        if ($form->webhook_secret) {
            $signature = hash_hmac('sha256', json_encode($payload), $form->webhook_secret);
            $headers['X-Webhook-Signature'] = $signature;
        }

        try {
            Http::withHeaders($headers)
                ->timeout(10)
                ->post($form->webhook_url, $payload)
                ->throw();
        } catch (\Exception $e) {
            Log::error("Webhook failed for Lead {$this->lead->id}: " . $e->getMessage());
            throw $e; // Trigger retry
        }
    }
}