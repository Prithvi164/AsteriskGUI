<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AmiService;
use App\Services\CallService;
use App\Models\ActiveCall;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * API Controller for Call Management
 * 
 * Handles active call monitoring, call control, and call origination
 */
class CallController extends Controller
{
    protected AmiService $amiService;
    protected CallService $callService;

    public function __construct(AmiService $amiService, CallService $callService)
    {
        $this->amiService = $amiService;
        $this->callService = $callService;
    }

    /**
     * Get all active calls
     * 
     * GET /api/calls/active
     * 
     * @return JsonResponse
     */
    public function getActiveCalls(): JsonResponse
    {
        try {
            // Check user permission
            $this->authorize('view-active-calls');

            // Get active calls from cache/database (updated by AMI listener)
            $activeCalls = ActiveCall::with(['extension', 'user'])
                ->orderBy('started_at', 'desc')
                ->get()
                ->map(function ($call) {
                    return [
                        'id' => $call->id,
                        'channel' => $call->channel,
                        'caller_id' => $call->caller_id_num,
                        'caller_name' => $call->caller_id_name,
                        'destination' => $call->destination,
                        'extension' => $call->extension?->number,
                        'status' => $call->status,
                        'duration' => $call->duration,
                        'started_at' => $call->started_at->toIso8601String(),
                        'user' => $call->user ? [
                            'id' => $call->user->id,
                            'name' => $call->user->name,
                        ] : null,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $activeCalls,
                'count' => $activeCalls->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching active calls', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch active calls',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get call statistics
     * 
     * GET /api/calls/stats
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getCallStats(Request $request): JsonResponse
    {
        try {
            $this->authorize('view-statistics');

            $period = $request->input('period', 'today'); // today, week, month
            $stats = $this->callService->getCallStatistics($period);

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching call stats', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch call statistics',
            ], 500);
        }
    }

    /**
     * Originate a new call (Click-to-Call)
     * 
     * POST /api/calls/originate
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function originateCall(Request $request): JsonResponse
    {
        try {
            $this->authorize('originate-calls');

            $validated = $request->validate([
                'extension' => 'required|string|max:20',
                'destination' => 'required|string|max:50',
                'context' => 'sometimes|string|max:50',
                'timeout' => 'sometimes|integer|min:10|max:120',
            ]);

            $extension = $validated['extension'];
            $destination = $validated['destination'];
            $context = $validated['context'] ?? config('asterisk.system.default_context');
            $timeout = $validated['timeout'] ?? 30;

            // Verify user has access to this extension
            if (!$this->callService->canUseExtension($request->user(), $extension)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to use this extension',
                ], 403);
            }

            $result = $this->amiService->originateCall(
                $extension,
                $destination,
                $context,
                $timeout
            );

            if ($result) {
                // Log the action
                Log::info('Call originated', [
                    'user_id' => $request->user()->id,
                    'extension' => $extension,
                    'destination' => $destination,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Call initiated successfully',
                    'data' => [
                        'extension' => $extension,
                        'destination' => $destination,
                    ],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to initiate call',
                ], 500);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error originating call', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while initiating the call',
            ], 500);
        }
    }

    /**
     * Hangup a call
     * 
     * POST /api/calls/{channel}/hangup
     * 
     * @param string $channel
     * @param Request $request
     * @return JsonResponse
     */
    public function hangupCall(string $channel, Request $request): JsonResponse
    {
        try {
            $this->authorize('hangup-calls');

            // Find the active call
            $activeCall = ActiveCall::where('channel', $channel)->first();

            if (!$activeCall) {
                return response()->json([
                    'success' => false,
                    'message' => 'Call not found',
                ], 404);
            }

            // Check permission to hangup this call
            if (!$this->callService->canHangupCall($request->user(), $activeCall)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to hangup this call',
                ], 403);
            }

            $result = $this->amiService->hangupChannel($channel);

            if ($result) {
                Log::info('Call hung up', [
                    'user_id' => $request->user()->id,
                    'channel' => $channel,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Call terminated successfully',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to terminate call',
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error hanging up call', [
                'error' => $e->getMessage(),
                'channel' => $channel,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while terminating the call',
            ], 500);
        }
    }

    /**
     * Get call details
     * 
     * GET /api/calls/{id}
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function getCallDetails(int $id): JsonResponse
    {
        try {
            $this->authorize('view-call-details');

            $call = ActiveCall::with(['extension', 'user'])->find($id);

            if (!$call) {
                return response()->json([
                    'success' => false,
                    'message' => 'Call not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $call->id,
                    'channel' => $call->channel,
                    'caller_id' => $call->caller_id_num,
                    'caller_name' => $call->caller_id_name,
                    'destination' => $call->destination,
                    'extension' => $call->extension,
                    'status' => $call->status,
                    'duration' => $call->duration,
                    'started_at' => $call->started_at,
                    'user' => $call->user,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching call details', [
                'error' => $e->getMessage(),
                'call_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch call details',
            ], 500);
        }
    }

    /**
     * Get system status
     * 
     * GET /api/calls/system-status
     * 
     * @return JsonResponse
     */
    public function getSystemStatus(): JsonResponse
    {
        try {
            $this->authorize('view-system-status');

            $status = $this->amiService->getSystemStatus();

            return response()->json([
                'success' => true,
                'data' => $status,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching system status', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch system status',
            ], 500);
        }
    }
}

