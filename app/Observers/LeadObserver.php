<?php

namespace App\Observers;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Jobs\DispatchWebhookJob;

class LeadObserver
{
    public function created(Lead $lead): void
    {
        LeadActivity::create([
            'lead_id' => $lead->id,
            'type' => 'system',
            'content' => "Lead created via {$lead->source}"
        ]);

        // Fire webhook on creation
        DispatchWebhookJob::dispatch($lead);
    }

    public function updated(Lead $lead): void
    {
        $changes = [];

        if ($lead->isDirty('status')) {
            $changes[] = "Status changed from '{$lead->getOriginal('status')}' to '{$lead->status}'";
        }
        
        if ($lead->isDirty('temperature')) {
            $changes[] = "Temperature changed from '{$lead->getOriginal('temperature')}' to '{$lead->temperature}'";
        }

        if (!empty($changes)) {
            foreach ($changes as $change) {
                LeadActivity::create([
                    'lead_id' => $lead->id,
                    'type' => 'status_change',
                    'content' => $change
                ]);
            }
            
            // Fire webhook on important updates
            DispatchWebhookJob::dispatch($lead);
        }
    }
}