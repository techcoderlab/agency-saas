<?php

namespace App\Observers;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Webhook;
use App\Jobs\DispatchWebhookJob;
use Illuminate\Support\Facades\RateLimiter; // Import this
use Illuminate\Support\Facades\Log;

class LeadObserver
{
    /**
     * Helper to dispatch webhooks with Circuit Breaker Protection
     */
    protected function triggerWebhooks(Lead $lead, string $event): void
    {
        // 1. Check Manual Suppression (Optional safety)
        if ($lead->suppress_webhooks) {
            return;
        }

        // 2. CIRCUIT BREAKER (The Real Safety Net)
        // We track attempts by a unique key per lead: "webhook:lead:{ID}"
        // Limit: 10 webhooks per 60 seconds.
        $key = "webhook:lead:{$lead->id}";

        if (RateLimiter::tooManyAttempts($key, 10)) {
            // Loop Detected! We stop here.
            // We only log it once per minute to avoid flooding logs too.
            if (RateLimiter::attempts($key) === 11) {
                Log::warning("⚠️ Infinite Loop Detected for Lead #{$lead->id}. Webhooks temporarily paused.");
                
                // Optional: Create a system note so the admin sees why it stopped
                LeadActivity::create([
                    'lead_id' => $lead->id,
                    'type' => 'system',
                    'content' => "Paused webhooks due to high activity (Loop Protection)."
                ]);
            }
            return;
        }

        // Count this attempt
        RateLimiter::hit($key, 60); // 60 seconds decay

        // --- PROCEED WITH NORMAL DISPATCH ---

        // 3. Fire Global Tenant Webhooks
        $webhooks = Webhook::where('tenant_id', $lead->tenant_id)
            ->where('is_active', true)
            ->get();

        foreach ($webhooks as $webhook) {
            // Check if this webhook subscribes to this event
            if (in_array($event, $webhook->events ?? [])) {
                DispatchWebhookJob::dispatch($lead, $webhook, $event);
            }
        }

        // // 4. Fire Legacy Form Webhook (Only on Creation)
        // if ($event === 'lead.created' && $lead->form && $lead->form->webhook_url) {
        //     $legacyWebhook = new Webhook([
        //         'url' => $lead->form->webhook_url,
        //         'secret' => $lead->form->webhook_secret,
        //         'is_active' => true
        //     ]);
        //     DispatchWebhookJob::dispatch($lead, $legacyWebhook, 'form.submission');
        // }
    }

    public function created(Lead $lead): void
    {
        LeadActivity::create([
            'lead_id' => $lead->id,
            'type' => 'system',
            'content' => "Lead created via {$lead->source}"
        ]);

        $this->triggerWebhooks($lead, 'lead.created');
    }

    public function updated(Lead $lead): void
    {
        $changes = [];
        $eventsToFire = [];

        if ($lead->isDirty('status')) {
            $changes[] = "Status changed from '{$lead->getOriginal('status')}' to '{$lead->status}'";
            $eventsToFire[] = 'lead.updated.status';
        }
        
        if ($lead->isDirty('temperature')) {
            $changes[] = "Temperature changed from '{$lead->getOriginal('temperature')}' to '{$lead->temperature}'";
            $eventsToFire[] = 'lead.updated.temperature';
        }

        if (!empty($changes)) {
            foreach ($changes as $change) {
                LeadActivity::create([
                    'lead_id' => $lead->id,
                    'type' => 'status_change',
                    'content' => $change
                ]);
            }
            
            $eventsToFire[] = 'lead.updated';

            // Fire events (unique)
            foreach (array_unique($eventsToFire) as $event) {
                $this->triggerWebhooks($lead, $event);
            }
        }
    }
}