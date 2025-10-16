<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CallController;
use App\Http\Controllers\Api\CdrController;
use App\Http\Controllers\Api\ExtensionController;
use App\Http\Controllers\Api\QueueController;
use App\Http\Controllers\Api\RecordingController;
use App\Http\Controllers\Api\IvrController;
use App\Http\Controllers\Api\TrunkController;
use App\Http\Controllers\Api\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// Public routes (if any)
Route::post('/auth/login', [App\Http\Controllers\Auth\LoginController::class, 'login']);

// Protected routes - require authentication
Route::middleware(['auth:sanctum'])->group(function () {
    
    // User info
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('/stats', [DashboardController::class, 'getStats']);
        Route::get('/recent-calls', [DashboardController::class, 'getRecentCalls']);
        Route::get('/system-health', [DashboardController::class, 'getSystemHealth']);
    });

    // Active Calls Management
    Route::prefix('calls')->group(function () {
        Route::get('/active', [CallController::class, 'getActiveCalls']);
        Route::get('/stats', [CallController::class, 'getCallStats']);
        Route::get('/system-status', [CallController::class, 'getSystemStatus']);
        Route::get('/{id}', [CallController::class, 'getCallDetails']);
        
        // Call control
        Route::post('/originate', [CallController::class, 'originateCall'])
            ->middleware('can:originate-calls');
        Route::post('/{channel}/hangup', [CallController::class, 'hangupCall'])
            ->middleware('can:hangup-calls');
        Route::post('/{channel}/transfer', [CallController::class, 'transferCall'])
            ->middleware('can:transfer-calls');
    });

    // Call History (CDR)
    Route::prefix('cdr')->group(function () {
        Route::get('/', [CdrController::class, 'index']);
        Route::get('/{id}', [CdrController::class, 'show']);
        Route::get('/export/csv', [CdrController::class, 'exportCsv']);
        Route::get('/export/pdf', [CdrController::class, 'exportPdf']);
        Route::get('/statistics', [CdrController::class, 'getStatistics']);
    });

    // Extensions Management
    Route::prefix('extensions')->group(function () {
        Route::get('/', [ExtensionController::class, 'index']);
        Route::get('/{id}', [ExtensionController::class, 'show']);
        Route::post('/', [ExtensionController::class, 'store'])
            ->middleware('can:manage-extensions');
        Route::put('/{id}', [ExtensionController::class, 'update'])
            ->middleware('can:manage-extensions');
        Route::delete('/{id}', [ExtensionController::class, 'destroy'])
            ->middleware('can:manage-extensions');
        Route::get('/{id}/status', [ExtensionController::class, 'getStatus']);
        Route::post('/{id}/toggle-dnd', [ExtensionController::class, 'toggleDnd']);
    });

    // Queue Management
    Route::prefix('queues')->group(function () {
        Route::get('/', [QueueController::class, 'index']);
        Route::get('/{id}', [QueueController::class, 'show']);
        Route::post('/', [QueueController::class, 'store'])
            ->middleware('can:manage-queues');
        Route::put('/{id}', [QueueController::class, 'update'])
            ->middleware('can:manage-queues');
        Route::delete('/{id}', [QueueController::class, 'destroy'])
            ->middleware('can:manage-queues');
        
        // Queue status and statistics
        Route::get('/{id}/status', [QueueController::class, 'getStatus']);
        Route::get('/{id}/statistics', [QueueController::class, 'getStatistics']);
        
        // Queue members (agents)
        Route::get('/{id}/members', [QueueController::class, 'getMembers']);
        Route::post('/{id}/members', [QueueController::class, 'addMember'])
            ->middleware('can:manage-queues');
        Route::delete('/{id}/members/{memberId}', [QueueController::class, 'removeMember'])
            ->middleware('can:manage-queues');
        Route::post('/{id}/members/{memberId}/pause', [QueueController::class, 'pauseMember'])
            ->middleware('can:manage-queues');
        Route::post('/{id}/members/{memberId}/unpause', [QueueController::class, 'unpauseMember'])
            ->middleware('can:manage-queues');
    });

    // Call Recordings
    Route::prefix('recordings')->group(function () {
        Route::get('/', [RecordingController::class, 'index']);
        Route::get('/{id}', [RecordingController::class, 'show']);
        Route::get('/{id}/download', [RecordingController::class, 'download']);
        Route::get('/{id}/stream', [RecordingController::class, 'stream']);
        Route::delete('/{id}', [RecordingController::class, 'destroy'])
            ->middleware('can:delete-recordings');
    });

    // IVR Management
    Route::prefix('ivrs')->group(function () {
        Route::get('/', [IvrController::class, 'index']);
        Route::get('/{id}', [IvrController::class, 'show']);
        Route::post('/', [IvrController::class, 'store'])
            ->middleware('can:manage-ivr');
        Route::put('/{id}', [IvrController::class, 'update'])
            ->middleware('can:manage-ivr');
        Route::delete('/{id}', [IvrController::class, 'destroy'])
            ->middleware('can:manage-ivr');
        Route::post('/audio/upload', [IvrController::class, 'uploadAudio'])
            ->middleware('can:manage-ivr');
    });

    // Trunk Management
    Route::prefix('trunks')->group(function () {
        Route::get('/', [TrunkController::class, 'index']);
        Route::get('/{id}', [TrunkController::class, 'show']);
        Route::post('/', [TrunkController::class, 'store'])
            ->middleware('can:manage-trunks');
        Route::put('/{id}', [TrunkController::class, 'update'])
            ->middleware('can:manage-trunks');
        Route::delete('/{id}', [TrunkController::class, 'destroy'])
            ->middleware('can:manage-trunks');
        Route::get('/{id}/status', [TrunkController::class, 'getStatus']);
    });

    // Admin Routes
    Route::middleware(['role:admin'])->prefix('admin')->group(function () {
        // User management
        Route::apiResource('users', App\Http\Controllers\Admin\UserController::class);
        
        // Role management
        Route::apiResource('roles', App\Http\Controllers\Admin\RoleController::class);
        
        // Audit logs
        Route::get('audit-logs', [App\Http\Controllers\Admin\AuditLogController::class, 'index']);
        
        // System settings
        Route::get('settings', [App\Http\Controllers\Admin\SettingController::class, 'index']);
        Route::post('settings', [App\Http\Controllers\Admin\SettingController::class, 'update']);
    });
});

