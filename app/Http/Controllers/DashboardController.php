<?php

namespace App\Http\Controllers;

use App\Models\OfflineTimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = auth()->user();
        
        // Base query for stats
        $query = OfflineTimeEntry::query();
        
        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        // 1. Today's Stats
        $todayMinutes = (clone $query)
            ->whereDate('date', Carbon::today())
            ->sum('duration_minutes');
            
        // 2. This Month's Stats
        $monthMinutes = (clone $query)
            ->whereMonth('date', Carbon::now()->month)
            ->whereYear('date', Carbon::now()->year)
            ->sum('duration_minutes');

        // 3. Recent Entries (Last 5)
        $recentEntries = (clone $query)
            ->with(['user:id,name'])
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->limit(5)
            ->get();

        // 4. Activity Chart Data (Last 7 Days)
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            
            $minutes = (clone $query)
                ->whereDate('date', $date)
                ->sum('duration_minutes');
                
            $chartData[] = [
                'date' => $date->format('M d'),
                'full_date' => $date->format('Y-m-d'),
                'minutes' => (int) $minutes,
                'hours' => round($minutes / 60, 1),
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
            ];
        }

        return Inertia::render('Dashboard', [
            'stats' => [
                'today_minutes' => $todayMinutes,
                'today_formatted' => $this->formatDuration($todayMinutes),
                'month_minutes' => $monthMinutes,
                'month_formatted' => $this->formatDuration($monthMinutes),
            ],
            'recentEntries' => $recentEntries,
            'chartData' => $chartData,
            'adminStats' => $adminStats,
            'isAdmin' => $user->isAdmin(),
        ]);
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
