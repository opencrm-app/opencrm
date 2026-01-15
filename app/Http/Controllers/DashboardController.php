<?php

namespace App\Http\Controllers;

use App\Models\OfflineTimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = auth()->user();
        
        // 1. Personal Query (For Progress Bar & Pace)
        // ALWAYS filtered by the current user to track their own 8h goal
        $personalQuery = OfflineTimeEntry::query()->where('user_id', $user->id);

        // 2. Stats Query (For Cards - Logged Today, This Month)
        // Admin = Global (All Users), User = Personal
        $statsQuery = OfflineTimeEntry::query();
        if (!$user->isAdmin()) {
            $statsQuery->where('user_id', $user->id);
        }

        // --- CALCULATION ---

        // Personal Today (For Progress Bar)
        $personalTodayQuery = (clone $personalQuery)->whereDate('date', Carbon::today());
        $personalTodayMinutes = $personalTodayQuery->sum('duration_minutes');
        $personalOfflineStart = $personalTodayQuery->orderBy('start_time', 'asc')->value('start_time');

        // Stats Today (For Card)
        $statsTodayMinutes = (clone $statsQuery)
            ->whereDate('date', Carbon::today())
            ->sum('duration_minutes');
            
        // Personal Month Offline (For Pace)
        $personalMonthOffline = (clone $personalQuery)
            ->whereMonth('date', Carbon::now()->month)
            ->whereYear('date', Carbon::now()->year)
            ->sum('duration_minutes');

        // Stats Month (For Card)
        $statsMonthMinutes = (clone $statsQuery)
            ->whereMonth('date', Carbon::now()->month)
            ->whereYear('date', Carbon::now()->year)
            ->sum('duration_minutes');

        // 3. Recent Entries (User Specific usually, or Admin sees all?)
        // Usually recent entries are personal log, but let's keep previous behavior:
        // Previous behavior: If Admin, query was global. Let's keep that for the "Recent Entries" list.
        $recentEntries = (clone $statsQuery)
            ->with(['user:id,name'])
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->limit(5)
            ->get();

        // 4. Activity Chart Data (Stats Query - Contextual)
        $ssmWeekData = $this->getSSMWeekData($user);
        $chartData = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dateKey = $date->format('Y-m-d'); // Key format from SSM parsing
            
            $offlineMinutes = (clone $statsQuery)
                ->whereDate('date', $date)
                ->sum('duration_minutes');
                
            $ssmMinutes = $ssmWeekData[$dateKey] ?? 0;
            $totalMinutes = $offlineMinutes + $ssmMinutes;
                
            $chartData[] = [
                'date' => $date->format('M d'),
                'full_date' => $date->format('Y-m-d'),
                'minutes' => (int) $totalMinutes,
                'hours' => round($totalMinutes / 60, 1),
            ];
        }

        // 5. Admin Specific Stats
        $adminStats = [];
        if ($user->isAdmin()) {
            $adminStats = [
                'total_users' => User::count(),
                'active_users_today' => OfflineTimeEntry::whereDate('date', Carbon::today())
                    ->distinct('user_id')
                    ->count(),
                // Note: We are now passing the consolidated stats via the 'stats' prop too,
                // but keeping these specific counters here.
            ];
        }

        // 6. Monthly Pace Calculation (ALWAYS Personal)
        // The pacing widget is for the individual user's target.
        $monthlyPace = $this->calculateMonthlyPace($user, (int) $personalMonthOffline);

        return Inertia::render('Dashboard', [
            'stats' => [
                'personal_today_minutes' => (int) $personalTodayMinutes, // For Progress Bar
                'personal_offline_start' => $personalOfflineStart,       // For Progress Bar Start Time fallback
                'today_minutes' => (int) $statsTodayMinutes,             // For Card
                'today_formatted' => $this->formatDuration((int) $statsTodayMinutes),
                'month_minutes' => (int) $statsMonthMinutes,             // For Card
                'month_formatted' => $this->formatDuration((int) $statsMonthMinutes),
            ],
            'recentEntries' => $recentEntries,
            'chartData' => $chartData,
            'adminStats' => $adminStats,
            'isAdmin' => $user->isAdmin(),
            'monthlyPace' => $monthlyPace,
        ]);
    }

    /**
     * Calculate monthly pace - how much work per day to meet target.
     */
    private function calculateMonthlyPace($user, int $offlineMinutes): array
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        
        // 1. Calculate total working days in month (excluding Fridays)
        $totalDaysInMonth = $endOfMonth->day;
        $fridaysInMonth = $this->countFridaysInRange($startOfMonth, $endOfMonth);
        $totalWorkingDays = $totalDaysInMonth - $fridaysInMonth;
        
        // Monthly target: 8 hours per working day
        $monthlyTargetMinutes = $totalWorkingDays * 8 * 60;
        
        // 2. Get SSM online minutes for month (cached for 30 mins)
        $ssmMonthMinutes = $this->getSSMMonthlyMinutes($user);
        
        // 3. Total worked this month
        $totalWorkedMinutes = $offlineMinutes + $ssmMonthMinutes;
        
        // 4. Calculate remaining
        $remainingMinutes = max(0, $monthlyTargetMinutes - $totalWorkedMinutes);
        
        // 5. Calculate remaining working days (from tomorrow, excluding Fridays)
        $tomorrow = $now->copy()->addDay()->startOfDay();
        $remainingWorkingDays = 0;
        
        if ($tomorrow->lte($endOfMonth)) {
            // Iterative approach to be absolutely safe and accurate
            $current = $tomorrow->copy();
            while ($current->lte($endOfMonth)) {
                if (!$current->isFriday()) {
                    $remainingWorkingDays++;
                }
                $current->addDay();
            }
        }
        
        // 6. Required daily pace
        $requiredDailyMinutes = $remainingWorkingDays > 0 
            ? (int) ceil($remainingMinutes / $remainingWorkingDays) 
            : 0; // If 0 days left, required is 0 (handled by status)
        
        // 7. Determine status
        $status = 'on_track';
        if ($remainingMinutes <= 0) {
            $status = 'completed';
        } elseif ($remainingWorkingDays === 0 && $remainingMinutes > 0) {
            $status = 'missed';
        } elseif ($requiredDailyMinutes > 10 * 60) { // More than 10h/day needed
            $status = 'behind';
        }
        
        return [
            'monthly_target_minutes' => $monthlyTargetMinutes,
            'monthly_target_formatted' => $this->formatDuration($monthlyTargetMinutes),
            'total_worked_minutes' => $totalWorkedMinutes,
            'total_worked_formatted' => $this->formatDuration($totalWorkedMinutes),
            'offline_minutes' => $offlineMinutes,
            'online_minutes' => $ssmMonthMinutes,
            'remaining_minutes' => $remainingMinutes,
            'remaining_formatted' => $this->formatDuration($remainingMinutes),
            'remaining_working_days' => $remainingWorkingDays,
            'total_working_days' => $totalWorkingDays,
            'required_daily_minutes' => $requiredDailyMinutes,
            'required_daily_formatted' => $this->formatDuration($requiredDailyMinutes),
            'status' => $status,
            'ssm_configured' => !empty($user->ssm_api_token),
        ];
    }

    /**
     * Count Fridays in a date range.
     */
    private function countFridaysInRange(Carbon $start, Carbon $end): int
    {
        $count = 0;
        $current = $start->copy();
        
        while ($current->lte($end)) {
            if ($current->isFriday()) {
                $count++;
            }
            $current->addDay();
        }
        
        return $count;
    }

    /**
     * Get SSM monthly minutes with 30-minute cache.
     */
    private function getSSMMonthlyMinutes($user): int
    {
        if (empty($user->ssm_api_token)) {
            return 0;
        }

        $cacheKey = 'ssm_monthly_' . $user->id . '_' . Carbon::now()->format('Y-m');
        
        return Cache::remember($cacheKey, 1800, function () use ($user) {
            try {
                return $this->fetchSSMMonthlyReport($user->ssm_api_token);
            } catch (\Exception $e) {
                Log::error('SSM Monthly fetch failed', ['error' => $e->getMessage()]);
                return 0;
            }
        });
    }

    /**
     * Fetch monthly report from SSM API.
     */
    private function fetchSSMMonthlyReport(string $apiToken): int
    {
        $now = Carbon::now();
        $from = $now->copy()->startOfMonth()->format('Y-m-d');
        $to = $now->format('Y-m-d');

        // First get employmentId
        $commonResponse = Http::withoutVerifying()->withHeaders([
            'X-SSM-Token' => $apiToken,
            'Accept' => 'application/json',
        ])->get('https://screenshotmonitor.com/api/v2/GetCommonData');

        if ($commonResponse->failed()) {
            throw new \Exception('GetCommonData failed');
        }

        $employmentId = $commonResponse->json('employmentId');
        
        if (!$employmentId) {
            throw new \Exception('No employmentId found');
        }

        // Fetch monthly report
        $reportResponse = Http::withoutVerifying()->withHeaders([
            'X-SSM-Token' => $apiToken,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post('https://screenshotmonitor.com/api/v2/GetReport', [
            'employmentId' => $employmentId,
            'from' => $from,
            'to' => $to,
        ]);

        if ($reportResponse->failed()) {
            throw new \Exception('GetReport failed');
        }

        $data = $reportResponse->json();
        $totalMinutes = 0;
        
        // Parse from charts.employments (PascalCase Duration)
        if (isset($data['charts']['employments']) && is_array($data['charts']['employments'])) {
            foreach ($data['charts']['employments'] as $record) {
                $totalMinutes += $record['Duration'] ?? 0;
            }
        }
        
        Log::info('SSM Monthly Report', ['from' => $from, 'to' => $to, 'totalMinutes' => $totalMinutes]);
        
        return $totalMinutes;
    }

    /**
     * Get SSM week data (daily breakdown) with 60-minute cache.
     * Returns ['Y-m-d' => minutes]
     */
    private function getSSMWeekData($user): array
    {
        if (empty($user->ssm_api_token)) {
            return [];
        }

        // Cache for 1 hour (less critical than monthly pace)
        $cacheKey = 'ssm_week_chart_' . $user->id . '_' . Carbon::today()->format('Y-m-d');
        
        return Cache::remember($cacheKey, 3600, function () use ($user) {
            try {
                return $this->fetchSSMWeekReport($user->ssm_api_token);
            } catch (\Exception $e) {
                Log::error('SSM Week Chart fetch failed', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * Fetch week report from SSM API and parse daily timeline.
     */
    private function fetchSSMWeekReport(string $apiToken): array
    {
        $to = Carbon::today()->format('Y-m-d');
        $from = Carbon::today()->subDays(6)->format('Y-m-d');

        // First get employmentId
        $commonResponse = Http::withoutVerifying()->withHeaders([
            'X-SSM-Token' => $apiToken,
            'Accept' => 'application/json',
        ])->get('https://screenshotmonitor.com/api/v2/GetCommonData');

        if ($commonResponse->failed()) {
            throw new \Exception('GetCommonData failed');
        }

        $employmentId = $commonResponse->json('employmentId');
        
        // Fallback common logic can be extracted but keeping inline for safety
        if (!$employmentId) {
             // Basic fallback matching getDailyStats logic
             $commonData = $commonResponse->json();
             $employments = $commonData['employments'] ?? [];
             if (!empty($employments)) {
                 $employmentId = $employments[0]['id'] ?? null;
             }
        }

        if (!$employmentId) {
            throw new \Exception('No employmentId found');
        }

        // Fetch report
        $reportResponse = Http::withoutVerifying()->withHeaders([
            'X-SSM-Token' => $apiToken,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post('https://screenshotmonitor.com/api/v2/GetReport', [
            'employmentId' => $employmentId,
            'from' => $from,
            'to' => $to,
        ]);

        if ($reportResponse->failed()) {
            throw new \Exception('GetReport failed');
        }

        $data = $reportResponse->json();
        $dailyData = [];

        // Parse from charts.timeline: [{"Date": "1/15/2026", "Duration": 326}]
        if (isset($data['charts']['timeline']) && is_array($data['charts']['timeline'])) {
            foreach ($data['charts']['timeline'] as $record) {
                $rawDate = $record['Date'] ?? null;
                $duration = $record['Duration'] ?? 0;
                
                if ($rawDate) {
                    try {
                        // Carbon::parse handles various formats intelligently
                        $dateKey = Carbon::parse($rawDate)->format('Y-m-d');
                        $dailyData[$dateKey] = (int) $duration;
                    } catch (\Exception $e) {
                        Log::warning('SSM Chart: Date parse failed', ['date' => $rawDate]);
                    }
                }
            }
        }
        
        return $dailyData;
    }

    private function formatDuration(int $minutes): string
    {
        $h = floor($minutes / 60);
        $m = $minutes % 60;
        
        if ($h > 0 && $m > 0) return "{$h}h {$m}m";
        if ($h > 0) return "{$h}h";
        return "{$m}m";
    }
}
