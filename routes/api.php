<?php

use App\Http\Controllers\AiChatController;
use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BootstrapController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\PublicFormController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PlanController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware(['auth:sanctum', 'throttle:tenant_api', 'check.status', 'log.api', 'plan.expiry', 'check.tenant_access'])->group(function () {
    Route::get('/bootstrap', BootstrapController::class);
    // Route::post('/n8n/token', [AuthController::class, 'n8nToken']);

    // Super admin management
    Route::get('/tenants', [TenantController::class, 'index']);
    Route::post('/tenants', [TenantController::class, 'store']);
    Route::patch('/tenants/{tenant}', [TenantController::class, 'update']);
    Route::delete('/tenants/{tenant}', [TenantController::class, 'destroy']);
    Route::post('/tenants/crm-config', [TenantController::class, 'updateCrmConfig']);
    Route::get('/tenants/modules', [TenantController::class, 'getModulesForTenant']);


    Route::get('/plans-data', [PlanController::class, 'index']);
    Route::post('/plans', [PlanController::class, 'storePlan']);
    Route::put('/plans/{plan}', [PlanController::class, 'updatePlan']);
    Route::delete('/plans/{plan}', [PlanController::class, 'destroyPlan']);
    Route::post('/modules', [PlanController::class, 'storeModule']);
    // Assign Plan
    Route::post('/tenants/{tenant}/assign-plan', [PlanController::class, 'assignPlan']);


    // AI Chat Modules
    Route::middleware(['check.module:ai_chats'])->group(function () {
        Route::apiResource('ai-chats', AiChatController::class);
        Route::get('/ai-chats/{aiChat}/history', [\App\Http\Controllers\AiChatController::class, 'history']);
        Route::get('ai-chats/{aiChat}/status', [AiChatController::class, 'checkConnection']);
        Route::post('/ai-chats/{aiChat}/chat', [AiChatController::class, 'chat']);
    });

    // Agency forms CRUD (per-tenant via global scope)
    Route::middleware(['check.module:forms'])->group(function () {
        Route::get('/forms', [FormController::class, 'index']);
        Route::post('/forms', [FormController::class, 'store']);
        Route::put('/forms/{form}', [FormController::class, 'update']);
        Route::delete('/forms/{form}', [FormController::class, 'destroy']);
    });

    // Webhooks Management
    Route::middleware(['check.module:webhooks'])->group(function () {
        // Route::get('/webhooks', [WebhookController::class, 'index']);
        // Route::post('/webhooks', [WebhookController::class, 'store']);
        // Route::delete('/webhooks/{webhook}', [WebhookController::class, 'destroy']);
        Route::apiResource('webhooks', WebhookController::class);
    });


    // Leads Management
    Route::middleware(['check.module:leads'])->group(function () {
        Route::get('/leads', [LeadController::class, 'index']);
        Route::get('/leads/stats', [LeadController::class, 'stats']);
        Route::get('/leads/{lead}', [LeadController::class, 'show']);
        Route::get('/leads/{lead}/activities', [LeadController::class, 'activities']);
        Route::put('/leads/{lead}', [LeadController::class, 'update']);
        Route::post('/leads/{lead}/note', [LeadController::class, 'addNote']);
        Route::post('/leads/export', [LeadController::class, 'export']);

        // Creating a lead will fail if the count >= plan limit
        Route::post('/leads/batch', [LeadController::class, 'batchStore']);
        Route::post('/leads', [LeadController::class, 'store']);
        Route::post('/leads/import', [LeadController::class, 'import']);
    });


    // API Keys Management (New)
    Route::middleware(['check.module:api_keys'])->group(function () {
        Route::get('/api-keys', [ApiKeyController::class, 'index']);
        Route::post('/api-keys', [ApiKeyController::class, 'store']);
        Route::put('/api-keys/{id}', [ApiKeyController::class, 'update']);
        Route::delete('/api-keys/{id}', [ApiKeyController::class, 'destroy']);
        Route::post('/api-keys/{id}/rotate', [ApiKeyController::class, 'rotate']);
    });

    // // Public route to get settings based on domain or user context
    // Route::get('/settings', function (Request $request) {
    //     // Logic: If user is logged in, get their tenant's settings.
    //     // If not, try to resolve tenant by domain, or return Super Admin (null) settings.

    //     // For now, returning Super Admin (Global) settings as default
    //     $settings = \App\Models\TenantSetting::whereNull('tenant_id')->first();

    //     return response()->json([
    //         'theme' => $settings ? $settings->client_theme : null
    //     ]);
    // });
});

// Wrap public routes in the throttle middleware
Route::prefix('public')->middleware('throttle:60,1')->group(function () {
    Route::get('/form/{uuid}', [PublicFormController::class, 'show']);
    Route::post('/form/{uuid}/submit', [PublicFormController::class, 'submit']);
});
