<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\PublicFormController;
use App\Http\Controllers\TenantController;
use App\Http\Middleware\CheckTenantStatus;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware(['auth:sanctum', CheckTenantStatus::class])->group(function () {
    Route::post('/n8n/token', [AuthController::class, 'n8nToken']);

    // Super admin tenant management
    Route::get('/tenants', [TenantController::class, 'index']);
    Route::post('/tenants', [TenantController::class, 'store']);
    Route::patch('/tenants/{tenant}', [TenantController::class, 'update']);
    Route::delete('/tenants/{tenant}', [TenantController::class, 'destroy']);

    // Agency forms CRUD (per-tenant via global scope)
    Route::get('/forms', [FormController::class, 'index']);
    Route::post('/forms', [FormController::class, 'store']);
    Route::put('/forms/{form}', [FormController::class, 'update']);
    Route::delete('/forms/{form}', [FormController::class, 'destroy']);

    // Agency leads listing (per-tenant via global scope)
    Route::get('/leads', [LeadController::class, 'index']);
});

Route::prefix('public')->group(function () {
    Route::get('/form/{uuid}', [PublicFormController::class, 'show']);
    Route::post('/form/{uuid}/submit', [PublicFormController::class, 'submit']);
});

