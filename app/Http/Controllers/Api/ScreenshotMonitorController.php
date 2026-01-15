<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ScreenshotMonitorController extends Controller
{
    private const BASE_URL = 'https://screenshotmonitor.com/api/v2';

    /**
     * Get daily stats from ScreenshotMonitor API (with caching).
     */
    public function getDailyStats(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (empty($user->ssm_api_token)) {
            return response()->json([
                'configured' => false,
                'online_minutes' => 0,
                'cached' => false,
                'message' => 'ScreenshotMonitor not configured. Please add your API token in Settings.',
            ]);
        }

        $cacheKey = 'ssm_daily_' . $user->id;
        $forceRefresh = $request->boolean('refresh');

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        try {
            $data = Cache::remember($cacheKey, 600, function () use ($user) {
                return $this->fetchFromSSM($user->ssm_api_token);
            });

            return response()->json([
                'configured' => true,
                'online_minutes' => $data['online_minutes'] ?? 0,
                'cached' => !$forceRefresh && Cache::has($cacheKey),
                'error' => null,
            ]);

        } catch (\Exception $e) {
            Cache::forget($cacheKey);

            Log::error('SSM API Error', [
                'message' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'configured' => true,
                'online_minutes' => 0,
                'cached' => false,
                'error' => 'Could not sync: ' . $e->getMessage(),
            ], 200);
        }
    }

    /**
     * Fetch activities from ScreenshotMonitor API.
     * Step 1: GetCommonData to find employmentId
     * Step 2: GetActivities for that employmentId
     */
    protected function fetchFromSSM(string $apiToken): array
    {
        // Step 1: GetCommonData to discover employmentId
        $commonDataUrl = self::BASE_URL . '/GetCommonData';
        
        Log::info('SSM: Fetching GetCommonData', ['url' => $commonDataUrl]);
        
        $commonResponse = Http::withoutVerifying()->withHeaders([
            'X-SSM-Token' => $apiToken,
            'Accept' => 'application/json',
        ])->get($commonDataUrl);

        Log::info('SSM: GetCommonData response', [
            'status' => $commonResponse->status(),
            'body' => substr($commonResponse->body(), 0, 500),
        ]);

        if ($commonResponse->failed()) {
            // Try POST if GET fails
            Log::info('SSM: GET failed, trying POST');
            $commonResponse = Http::withoutVerifying()->withHeaders([
                'X-SSM-Token' => $apiToken,
                'Accept' => 'application/json',
            ])->post($commonDataUrl);
            
            Log::info('SSM: POST GetCommonData response', [
                'status' => $commonResponse->status(),
                'body' => substr($commonResponse->body(), 0, 500),
            ]);
        }

        if ($commonResponse->failed()) {
            throw new \Exception('GetCommonData failed. Status: ' . $commonResponse->status());
        }

        $commonData = $commonResponse->json();
        
        // FIRST: Try root-level employmentId (new structure)
        $employmentId = $commonData['employmentId'] ?? null;
        
        // FALLBACK: Try employments array if root-level not found
        if (!$employmentId) {
            $employments = $commonData['employments'] ?? [];
            
            if (empty($employments)) {
                if (isset($commonData['data']['employments'])) {
                    $employments = $commonData['data']['employments'];
                } elseif (isset($commonData['employment'])) {
                    $employments = [$commonData['employment']];
                }
            }
            
            if (!empty($employments)) {
                $employment = collect($employments)->first(function ($emp) {
                    return !($emp['isArchived'] ?? false);
                }) ?? $employments[0];
                
                $employmentId = $employment['id'] ?? $employment['employmentId'] ?? null;
            }
        }

        if (!$employmentId) {
            Log::error('SSM: No employmentId found', ['data' => $commonData]);
            throw new \Exception('Could not find employmentId.');
        }

        Log::info('SSM: Found employmentId', ['id' => $employmentId]);

        // Step 2: GetReport using POST method (API requires POST)
        $today = now()->format('Y-m-d');
        $reportUrl = self::BASE_URL . '/GetReport';
        
        $payload = [
            'employmentId' => $employmentId,
            'from' => $today,
            'to' => $today,
        ];
        
        Log::info('SSM: POSTing to GetReport', $payload);
        
        $reportResponse = Http::withoutVerifying()->withHeaders([
            'X-SSM-Token' => $apiToken,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post($reportUrl, $payload);

        Log::info('SSM: GetReport response', [
            'status' => $reportResponse->status(),
            'body' => substr($reportResponse->body(), 0, 2000),
        ]);

        if ($reportResponse->failed()) {
            throw new \Exception('GetReport failed. Status: ' . $reportResponse->status());
        }

        $responseData = $reportResponse->json();
        
        // Calculate total time from GetReport response
        // API returns PascalCase keys like "Duration" not "duration"
        $totalMinutes = 0;
        
        // PREFERRED: Get total from 'charts' -> 'employments' (aggregated summary)
        if (isset($responseData['charts']['employments']) && is_array($responseData['charts']['employments'])) {
            foreach ($responseData['charts']['employments'] as $record) {
                $totalMinutes += $record['Duration'] ?? 0;
            }
            Log::info('SSM: Parsed from charts.employments', ['totalMinutes' => $totalMinutes]);
        }
        // FALLBACK: Sum from 'body' details
        elseif (isset($responseData['body']) && is_array($responseData['body'])) {
            foreach ($responseData['body'] as $entry) {
                $totalMinutes += $entry['Duration'] ?? 0;
            }
            Log::info('SSM: Parsed from body', ['totalMinutes' => $totalMinutes]);
        }
        // FALLBACK: Check for root-level Duration
        elseif (isset($responseData['Duration'])) {
            $totalMinutes = intval($responseData['Duration']);
            Log::info('SSM: Parsed from root Duration', ['totalMinutes' => $totalMinutes]);
        }

        Log::info('SSM: Final calculated time', ['totalMinutes' => $totalMinutes]);

        return [
            'online_minutes' => (int) $totalMinutes,
            'employment_id' => $employmentId,
        ];
    }
}
