<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\TenantSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LeadController extends Controller
{

    private function getCrmConfig($tenantId)
    {
        $settings = TenantSetting::where('tenant_id', $tenantId)->first();
        
        return $settings->crm_config ?? [
            'entity_name_singular' => 'Lead',
            'entity_name_plural' => 'Leads',
            'statuses' => [
                ['slug' => 'new', 'label' => 'New', 'color' => 'blue'],
                ['slug' => 'contacted', 'label' => 'Contacted', 'color' => 'yellow'],
                ['slug' => 'closed', 'label' => 'Closed', 'color' => 'green'],
            ]
        ];
    }

    /**
     * Get key metrics for the dashboard.
     * Uses the Tenant Scope automatically via the Model.
     */
    public function stats(Request $request)
    {
        // Use a single aggregate query for performance (1 DB call instead of 4)
        $statsQuery = Lead::query()
            ->selectRaw('count(*) as total')
            ->selectRaw("count(case when created_at >= ? then 1 end) as new_today", [now()->startOfDay()])
            ->selectRaw("count(case when temperature = 'hot' then 1 end) as hot_leads")
            ->selectRaw("count(case when status = 'closed' then 1 end) as closed_leads")
            ->first();

        $conversion = $statsQuery->total > 0 
            ? round(($statsQuery->closed_leads / $statsQuery->total) * 100, 1) 
            : 0;

        return response()->json([
            'stats' => [
                'total' => $statsQuery->total,
                'new_today' => $statsQuery->new_today,
                'hot_leads' => $statsQuery->hot_leads,
                'conversion_rate' => $conversion . '%'
            ],
            // Inject Config here so frontend adapts immediately
            'config' => $this->getCrmConfig($request->user()->tenant_id)
        ]);
    }

    public function index(Request $request)
    {
        $query = Lead::query();

        // --- Standard Filters ---
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('temperature') && $request->temperature !== 'all') {
            $query->where('temperature', $request->temperature);
        }

        if ($request->filled('source') && $request->source !== 'all') {
            $query->where('source', $request->source);
        }

        // --- Date Range Filters ---
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // --- Advanced Smart Search ---
        if ($request->filled('search')) {
            $term = strtolower($request->search);
            
            $query->where(function($q) use ($term) {
                $q->where('id', 'like', "%{$term}%")
                  ->orWhereRaw('LOWER(source) LIKE ?', ["%{$term}%"])
                  ->orWhereRaw('LOWER(notes) LIKE ?', ["%{$term}%"]);

                // Search inside the JSON payload (Email, Name, Phone, etc.)
                // Note: This syntax works for MySQL/MariaDB/SQLite. 
                // For PostgreSQL use: orWhereRaw('LOWER(payload::text) LIKE ?', ...)
                $q->orWhereRaw('LOWER(payload) LIKE ?', ["%{$term}%"]);
            });
        }

        // --- PRODUCTION IMPROVEMENT: Dynamic Pagination ---
        // Kanban view might need more density. 
        // We cap it at 100 to prevent abuse.
        $perPage = $request->input('per_page', 20);
        $perPage = min(max($perPage, 5), 100);

        return $query->with('form:id,name')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function show(Lead $lead)
    {
        return $lead->load(['activities', 'form']);
    }

    public function update(Request $request, Lead $lead)
    {
        $validated = $request->validate([
            'status' => 'required|string',
            'temperature' => 'required|string',
            'notes' => 'nullable|string'
        ]);

        // Fix for webhook looping:
        // External systems updating the lead can pass 'suppress_webhooks=true'
        // to avoid triggering the webhook they just reacted to.
        if ($request->boolean('suppress_webhooks')) {
            $lead->suppress_webhooks = true;
        }

        $lead->update($validated);

        return response()->json($lead);
    }

    public function addNote(Request $request, Lead $lead)
    {
        $request->validate(['content' => 'required|string']);

        $activity = $lead->activities()->create([
            'type' => 'note',
            'content' => $request->content
        ]);

        // Optional: Trigger note event
        // $this->triggerWebhooks($lead, 'lead.note_added'); 
        // (Accessing the observer helper here is hard, better to let Observer handle LeadActivity creation if needed)

        return response()->json($activity);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
            'form_id' => 'nullable|exists:forms,id'
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getPathname(), 'r');
        $header = fgetcsv($handle);
        
        $importedCount = 0;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                // Check for empty rows
                if (empty(array_filter($row))) continue;

                // Ensure row matches header length
                if (count($row) !== count($header)) continue;

                $data = array_combine($header, $row);
                
                Lead::create([
                    'tenant_id' => $request->user()->tenant_id, 
                    'form_id' => $request->form_id ?? null,
                    'source' => 'csv',
                    'status' => 'new',
                    'temperature' => 'cold',
                    'payload' => $data,
                    'meta_data' => [
                        'import_file' => $file->getClientOriginalName(),
                        'imported_at' => now()->toIso8601String()
                    ]
                ]);
                
                $importedCount++;
            }
            
            DB::commit();
            fclose($handle);
            
            return response()->json(['message' => "Successfully imported {$importedCount} leads."]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Import failed: ' . $e->getMessage()], 500);
        }
    }
}