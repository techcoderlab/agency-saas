<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\TenantSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests; // Ensure trait exists
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{

    use AuthorizesRequests; // Laravel 11 may put this in base Controller

    private function getCrmConfig($tenantId)
    {
        // 3-Layer Check via Policy
        $this->authorize('viewAny', Lead::class);

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

        // 3-Layer Check via Policy
        $this->authorize('viewAny', Lead::class);
        $tenantId = $request->user()->tenant_id;

       // CACHING STRATEGY:
        // We cache dashboard stats for 5 minutes. 
        // This protects your Shared Hosting CPU from calculating these numbers 
        // every time the client refreshes the page.
        $cacheKey = "dashboard_stats_{$tenantId}";

        $data = Cache::remember($cacheKey, 300, function () use ($tenantId) {
            
            $now = now();
            $startOfMonth = $now->copy()->startOfMonth();
            $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
            $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

            // 1. MAIN AGGREGATE (The Heavy Lifter)
            // Calculates totals, "Action Needed" items, and monthly growth.
            $aggregates = Lead::where('tenant_id', $tenantId)
                ->selectRaw('count(*) as total')
                ->selectRaw("count(case when status = 'new' then 1 end) as new_leads")
                ->selectRaw("count(case when status = 'closed' then 1 end) as closed_leads")
                ->selectRaw("count(case when temperature = 'hot' then 1 end) as hot_leads")
                // Actionable: Leads older than 24h that are still 'new' (Ignored Leads)
                ->selectRaw("count(case when status = 'new' and created_at < ? then 1 end) as stale_leads", [$now->subDay()])
                // Growth: Leads this month
                ->selectRaw("count(case when created_at >= ? then 1 end) as this_month_leads", [$startOfMonth])
                // Growth: Leads last month (for comparison)
                ->selectRaw("count(case when created_at >= ? and created_at <= ? then 1 end) as last_month_leads", [$startOfLastMonth, $endOfLastMonth])
                ->first();

            // 2. TREND DATA (For a "Last 7 Days" Sparkline Chart)
            // Clients love seeing a visual line graph.
            $dailyTrend = Lead::where('tenant_id', $tenantId)
                ->where('created_at', '>=', $now->copy()->subDays(6)->startOfDay())
                ->selectRaw('DATE(created_at) as date, count(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->mapWithKeys(fn ($item) => [$item->date => $item->count]);

            // Fill in missing days with 0 (for a smooth chart)
            $chartData = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = $now->copy()->subDays($i)->format('Y-m-d');
                $chartData[$date] = $dailyTrend[$date] ?? 0;
            }

            // 3. TOP SOURCES (Where is money coming from?)
            $topSources = Lead::where('tenant_id', $tenantId)
                ->select('source', DB::raw('count(*) as count'))
                ->groupBy('source')
                ->orderByDesc('count')
                ->limit(4)
                ->get();

            // 4. CALCULATIONS
            $conversionRate = $aggregates->total > 0 
                ? round(($aggregates->closed_leads / $aggregates->total) * 100, 1) 
                : 0;

            // Calculate Growth Percentage
            $growth = 0;
            if ($aggregates->last_month_leads > 0) {
                $growth = (($aggregates->this_month_leads - $aggregates->last_month_leads) / $aggregates->last_month_leads) * 100;
            } elseif ($aggregates->this_month_leads > 0) {
                $growth = 100; // 0 to something is 100% growth
            }

            return [
                'overview' => [
                    'total_leads' => $aggregates->total,
                    'new_leads' => $aggregates->new_leads,
                    'hot_leads' => $aggregates->hot_leads,
                    'conversion_rate' => $conversionRate,
                    'stale_leads' => $aggregates->stale_leads // "Needs Attention"
                ],
                'growth' => [
                    'this_month' => $aggregates->this_month_leads,
                    'last_month' => $aggregates->last_month_leads,
                    'percentage' => round($growth, 1)
                ],
                'chart_data' => $chartData, // Array for Chart.js or ApexCharts
                'top_sources' => $topSources
            ];
        });

        return response()->json([
            'stats' => $data,
            'config' => $this->getCrmConfig($tenantId)
        ]);
    }

    public function activities(Lead $lead)
    {
        // Ensure the lead belongs to the user's tenant
        $this->authorize('view', $lead);

        $activities = $lead->activities()
            ->latest()
            ->limit(20) // Only show the most recent 20
            ->get(['id', 'type', 'content', 'created_at']);

        return response()->json($activities);
    }

    public function index(Request $request)
    {
        // 1. Authorization
        $this->authorize('viewAny', Lead::class);
    
        // 2. Use a "Thin" Query (Select only what you need)
        // Avoid selecting massive JSON payloads if the frontend doesn't need them in the list view.
        $query = Lead::query()->where('tenant_id', $request->user()->tenant_id);
    
        // --- Standard Filters (Optimized with Indexed Columns) ---
        foreach (['status', 'temperature', 'source'] as $filter) {
            if ($request->filled($filter) && $request->$filter !== 'all') {
                $query->where($filter, $request->$filter);
            }
        }
    
        // --- Date Range Filters (Using whereBetween is slightly faster) ---
        if ($request->filled(['date_from', 'date_to'])) {
            $query->whereBetween('created_at', [$request->date_from, $request->date_to]);
        } elseif ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        } elseif ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }
    
        // --- Advanced Smart Search (Shared Hosting Safety) ---
        if ($request->filled('search')) {
            $term = $request->search;
            
            $query->where(function($q) use ($term) {
                // Priority 1: Exact ID (Very Fast)
                if (is_numeric($term)) {
                    $q->where('id', $term);
                }
    
                // Priority 2: Text Search
                // We use simple 'like' which is case-insensitive in MySQL (Hostinger default)
                // to avoid the overhead of LOWER() functions on every row.
                $q->orWhere('source', 'like', "%{$term}%")
                  ->orWhere('notes', 'like', "%{$term}%");
    
                // Priority 3: JSON Search (The CPU Killer)
                // On Shared Hosting, avoid searching the entire JSON blob if possible.
                // If you know the key (e.g., email), use ->where('payload->email', 'like', ...)
                $q->orWhere('payload', 'like', "%{$term}%");
            });
        }
    
        // --- Pagination & Performance ---
        $perPage = (int) $request->input('per_page', 20);
        $perPage = min(max($perPage, 5), 100);
    
        // Eager loading specific columns to save memory
        return $query->with('form:id,name')
            ->orderByDesc('id') // Sorting by ID is faster than created_at if ID is auto-increment
            ->paginate($perPage)
            ->withQueryString(); // Keeps filters active during page switches
    }

    public function show(Lead $lead)
    {

        // 3-Layer Check via Policy
        $this->authorize('view', $lead);

        $lead->load(['activities', 'form']);
        
        // Inject CRM Config for dynamic frontend rendering
        // This attaches the config to the lead object response seamlessly
        $lead->setAttribute('crm_config', $this->getCrmConfig($lead->tenant_id));
        
        return $lead;
    }


    public function update(Request $request, Lead $lead)
    {
        // 3-Layer Check via Policy
        $this->authorize('update', $lead);

        $validated = $request->validate([
            'status' => 'sometimes|nullable|string|max:50',
            'temperature' => ['sometimes','nullable','string', Rule::in(['cold', 'warm', 'hot'])],
            'notes' => ['sometimes','nullable','string', Rule::in(['system_added_note', 'external_system_added_note'])]
        ]);

        // Fix for webhook looping:
        // External systems updating the lead can pass 'suppress_webhooks=true'
        // to avoid triggering the webhook they just reacted to.
        if ($request->boolean('suppress_webhooks')) {
            $lead->suppress_webhooks = true;
        }

        $lead->update($validated);

        return response()->json(['message' => 'Lead updated successfully', 'id' => $lead->id], 201);
    }

    public function addNote(Request $request, Lead $lead)
    {
        // 3-Layer Check via Policy
        $this->authorize('update', $lead);

        $request->validate([
            'content' => 'required|string|max:300', 
            'type' => [
                'nullable',
                'string',
                Rule::in(['system_added_note', 'external_system_added_note'])
            ]
        ]);

        $lead->activities()->create([
            'type' => $request->type ?? 'system_added_note',
            'content' => $request->content
        ]);

        // Optional: Trigger note event
        // $this->triggerWebhooks($lead, 'lead.note_added'); 
        // (Accessing the observer helper here is hard, better to let Observer handle LeadActivity creation if needed)

        return response()->json(['message' => 'Note added successfully', 'id' => $lead->id], 201);
    }

    /**
     * 1. Store a Single Lead
     * Optimized for individual triggers (e.g., manual add or single webhook).
     */
    public function store(Request $request)
    {
        $this->authorize('create', Lead::class);

        $validated = $request->validate([
            'payload' => 'required|array',
            'form_id' => 'nullable|exists:forms,id',
            'source'  => 'nullable|string|max:50',
            'temperature' => ['nullable','string', Rule::in(['cold', 'warm', 'hot'])],
        ]);

        // Use create() for single leads to trigger Eloquent Events/Webhooks
        $lead = new Lead([
            'tenant_id'   => $request->user()->tenant_id,
            'form_id'     => $validated['form_id'] ?? null,
            'source'      => $validated['source'] ?? 'undefined',
            'payload'     => $validated['payload'],
            'status'      => 'new',
            'insert_method' => 'single',
            'temperature' => $validated['temperature'] ?? 'cold',
        ]);

        if ($request->boolean('suppress_webhooks')) {
            $lead->suppress_webhooks = true;
        }

        $lead->save();

        return response()->json(['message' => 'Lead created successfully', 'id' => $lead->id], 201);
    }

    /**
     * Shared Bulk Insert Helper
     * Handles Leads and their corresponding Activity Logs in one process.
     */
    private function performBulkInsert(array $leads, $status, $insertMethod, $temperature, $tenantId, $formId, $source, $activityType = "system_inserted")
    {
        $batchSize = 100; 
        $chunks = array_chunk($leads, $batchSize);
        $total = 0;
        $now = now();

        foreach ($chunks as $chunk) {
            $leadsToInsert = [];
            
            // 1. Prepare Lead Data
            foreach ($chunk as $leadPayload) {
                $leadsToInsert[] = [
                    'tenant_id'   => $tenantId,
                    'form_id'     => $formId,
                    'insert_method'      => $insertMethod ?? 'bulk',
                    'source'      => empty($leadPayload['source']) ? $source : $leadPayload['source'],
                    'status'      => empty($leadPayload['status']) ? $status : $leadPayload['status'],
                    'temperature' => empty($leadPayload['temperature']) ? $temperature : $leadPayload['temperature'],
                    'payload'     => json_encode($leadPayload),
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
            }

            DB::beginTransaction();
            try {
                // 2. Perform Bulk Insert for Leads
                DB::table('leads')->insert($leadsToInsert);
                
                // 3. Retrieve the IDs of the leads we just inserted.
                // We filter by tenant and the exact timestamp to get the correct IDs.
                $insertedLeadIds = DB::table('leads')
                    ->where('tenant_id', $tenantId)
                    ->where('created_at', $now)
                    ->orderBy('id', 'desc')
                    ->limit(count($chunk))
                    ->pluck('id');
                    
                // 4. Prepare Activity Data
                // $currentTokenString = request()->user()->loggedInFromString();
                $activityNote = ucfirst(str_replace('_', ' ', $activityType)) ." a lead, using ".strtoupper($insertMethod)." upload.";
                $activitiesToInsert = [];
                foreach ($insertedLeadIds as $leadId) {
                    $activitiesToInsert[] = [
                        'lead_id'      => $leadId,
                        'type' => $activityType,
                        'content' => $activityNote,
                        // 'content' => "Lead created via {$source}, using the {$currentTokenString}.",
                        'created_at'   => $now,
                        'updated_at'   => $now,
                    ];
                }

                // 5. Bulk Insert Activities
                if (!empty($activitiesToInsert)) {
                    DB::table('lead_activities')->insert($activitiesToInsert);
                }

                DB::commit();
                $total += count($leadsToInsert);
                
            } catch (\Exception $e) {
                DB::rollBack();
                // Log the error but continue or rethrow based on your agency's policy
                Log::error("Batch Insert Failed: " . $e->getMessage());
                throw $e; 
            }
        }

        return $total;
    }

    /**
     * 2. Batch Store Leads (from n8n or API)
     * This handles an array of leads sent in one request to save Entry Processes.
     */
    public function batchStore(Request $request)
    {
        $this->authorize('create', Lead::class);

        $request->validate([
            'leads'   => 'required|array|min:1',
            'leads.*' => 'required|array', // Each item in the array must be the lead data
            'form_id' => 'nullable|exists:forms,id',
            'from' => [
                'nullable',
                'string',
                Rule::in(['system_inserted', 'external_system_inserted'])
            ]
        ]);

        $leadsData = $request->input('leads');
        $tenantId  = $request->user()->tenant_id;
        $formId    = $request->form_id ?? null;
        $source    = $request->input('source', 'undefined');
        $temperature    = $request->input('temperature', 'cold');
        $status    = $request->input('status', 'new');
        $activityType    = $request->input('from', 'system_inserted');

        // We use the helper method to keep things efficient
        $count = $this->performBulkInsert($leadsData, $status, "bulk", $temperature, $tenantId, $formId, $source, $activityType);

        return response()->json(['message' => "Successfully processed {$count} leads."]);
    }

    /**
     * 4. Optimized CSV Import
     * Now reuses performBulkInsert for better maintenance.
     */
    public function import(Request $request)
    {
        $this->authorize('create', Lead::class);

        $request->validate(['file' => 'required|file|mimes:csv,txt',
            'from' => [
                        'nullable',
                        'string',
                        Rule::in(['system_inserted', 'external_system_inserted'])
                    ]]);

        $file   = $request->file('file');
        $handle = fopen($file->getPathname(), 'r');
        $header = fgetcsv($handle); // Consume and skip header row
        
        if (!$header) {
            fclose($handle);
            return response()->json(['message' => 'Empty file'], 400);
        }

        $allLeads = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (empty(array_filter($row)) || count($row) !== count($header)) continue;
            $allLeads[] = array_combine($header, $row);
        }
        fclose($handle);

        $count = $this->performBulkInsert(
            $allLeads,
            $request->status ?? 'new',
            'csv',
            $request->temperature ?? 'cold',
            $request->user()->tenant_id, 
            $request->form_id ?? null,
            $request->source ?? 'undefined',
            $request->input('from', 'system_inserted')

        );

        return response()->json(['message' => "Imported {$count} leads."]);
    }

    /**
     * RAM-Efficient CSV Export
     * Uses StreamedResponse to handle millions of rows on 1.5GB RAM.
     */

     public function export(Request $request)
     {
        $this->authorize('viewAny', Lead::class);

        $request->validate([
            'ids' => 'sometimes|nullable|array|min:1',
            'ids.*' => 'required|integer'
        ]);

         $tenantId = $request->user()->tenant_id;
         $selectedIds = $request->input('ids', []);
     
         // 1. DISCOVERY PHASE: Find all unique payload keys
         // We use a separate query to get only the payloads to save RAM.
         $query = Lead::where('tenant_id', $tenantId);
         if (!empty($selectedIds)) {
             $query->whereIn('id', $selectedIds);
         }
     
         $allKeys = [];
         // We use cursor to avoid loading thousands of objects into RAM
         foreach ($query->select('payload')->cursor() as $lead) {
             $keys = array_keys($lead->payload ?? []);
             foreach ($keys as $key) {
                 $allKeys[$key] = true; // Use keys as array keys to auto-handle duplicates
             }
         }
         $dynamicHeaders = array_keys($allKeys);
     
         // 2. STREAMING PHASE
         $fileName = 'leads_export_' . now()->format('Y-m-d_H-i') . '.csv';
         $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];
     
         return response()->stream(function() use ($tenantId, $selectedIds, $dynamicHeaders) {
             $file = fopen('php://output', 'w');
     
             // Write the full header row
             $mainHeaders = array_merge(['ID', 'Status', 'Temperature', 'Source'], $dynamicHeaders, ['Created At']);
             fputcsv($file, $mainHeaders);
     
             // Fetch data again for the actual export
             $exportQuery = Lead::where('tenant_id', $tenantId);
             if (!empty($selectedIds)) {
                 $exportQuery->whereIn('id', $selectedIds);
             }
     
             foreach ($exportQuery->cursor() as $lead) {
                 $row = [
                     $lead->id,
                     $lead->status,
                     $lead->temperature,
                     $lead->source,
                 ];
     
                 // Map dynamic payload data to the correct column
                 foreach ($dynamicHeaders as $header) {
                     $row[] = $lead->payload[$header] ?? ''; // Leave empty if key doesn't exist for this lead
                 }
     
                 $row[] = $lead->created_at->toDateTimeString();
                 fputcsv($file, $row);
             }
     
             fclose($file);
         }, 200, $headers);
     }

    // public function import(Request $request)
    // {
    //     // 3-Layer Check via Policy
    //     $this->authorize('create', Lead::class);

    //     $request->validate([
    //         'file' => 'required|file|mimes:csv,txt|max:2048',
    //         'form_id' => 'sometimes|nullable|exists:forms,id'
    //     ]);

    //     $file = $request->file('file');
    //     $handle = fopen($file->getPathname(), 'r');
    //     $header = fgetcsv($handle);
        
    //     $importedCount = 0;

    //     DB::beginTransaction();
    //     try {
    //         while (($row = fgetcsv($handle)) !== false) {
    //             // Check for empty rows
    //             if (empty(array_filter($row))) continue;

    //             // Ensure row matches header length
    //             if (count($row) !== count($header)) continue;

    //             $data = array_combine($header, $row);
                
                

    //             $lead = new Lead([
    //                 'tenant_id' => $request->user()->tenant_id, 
    //                 'form_id' => $request->form_id ?? null,
    //                 'source' => 'csv',
    //                 'status' => 'new',
    //                 'temperature' => 'cold',
    //                 'payload' => $data,
    //                 'meta_data' => [
    //                     'import_file' => $file->getClientOriginalName(),
    //                     'imported_at' => now()->toIso8601String()
    //                 ]
    //             ]);

    //             // Fix for webhook looping:
    //             // External systems updating the lead can pass 'suppress_webhooks=true'
    //             // to avoid triggering the webhook they just reacted to.
    //             if ($request->boolean('suppress_webhooks')) {
    //                 $lead->suppress_webhooks = true;
    //             }

    //             $lead->save();
                
    //             $importedCount++;
    //         }
            
    //         DB::commit();
    //         fclose($handle);
            
    //         return response()->json(['message' => "Successfully imported {$importedCount} leads."]);

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json(['message' => 'Import failed: ' . $e->getMessage()], 500);
    //     }
    // }
}