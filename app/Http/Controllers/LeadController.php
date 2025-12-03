<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LeadController extends Controller
{
    public function index(Request $request)
    {

        $query = Lead::query();

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('temperature') && $request->temperature !== 'all') {
            $query->where('temperature', $request->temperature);
        }

        return $query->with('form:id,name')->orderByDesc('created_at')->paginate(20);
    }

    public function show(Lead $lead)
    {
        // Ensure tenant access via Global Scope or Policy (assumed handled by middleware/trait)
        return $lead->load(['activities', 'form']);
    }

    public function update(Request $request, Lead $lead)
    {
        $validated = $request->validate([
            'status' => 'required|string',
            'temperature' => 'required|string',
            'notes' => 'nullable|string'
        ]);

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

        return response()->json($activity);
    }

    // Add this method to the class
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
            'form_id' => 'nullable|exists:forms,id' // Optional: link imported leads to a form
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getPathname(), 'r');
        $header = fgetcsv($handle); // Assume first row is header
        
        $importedCount = 0;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $data = array_combine($header, $row);
                
                // Basic mapping logic (adjust keys based on your needs or make dynamic)
                // We assume the CSV *might* have 'email', 'name', etc.
                // We put EVERYTHING into payload/meta_data to be safe.
                
                Lead::create([
                    // Use current user's tenant (scope handles this automatically usually, 
                    // but explicit is safer if you use BelongsToTenant trait correctly)
                    'tenant_id' => $request->user()->tenant_id, 
                    'form_id' => $request->form_id ?? null,
                    'source' => 'csv',
                    'status' => 'new',
                    'temperature' => 'cold',
                    'payload' => $data, // Store full CSV row in payload
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


