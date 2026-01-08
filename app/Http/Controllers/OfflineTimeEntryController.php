<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOfflineTimeEntryRequest;
use App\Http\Requests\UpdateOfflineTimeEntryRequest;
use App\Models\OfflineTimeEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OfflineTimeEntryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $user = auth()->user();
        $query = OfflineTimeEntry::with('user');

        // If user is not admin, only show their own entries
        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        // Apply filters
        if ($request->has('user_id') && $user->isAdmin()) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        if ($request->has('month')) {
            $month = $request->month; // Format: YYYY-MM
            [$year, $monthNum] = explode('-', $month);
            $query->whereYear('date', $year)->whereMonth('date', $monthNum);
        }

        // Clone query to calculate total duration for the current filter
        $totalDurationMinutes = $query->clone()->sum('duration_minutes');

        $entries = $query->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate(15);

        // Get all users for admin filter dropdown
        $users = $user->isAdmin() ? User::orderBy('name')->get(['id', 'name']) : [];

        return Inertia::render('OfflineTime/Index', [
            'entries' => $entries,
            'users' => $users,
            'filters' => $request->only(['user_id', 'date_from', 'date_to', 'month']),
            'totalDuration' => $totalDurationMinutes,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('OfflineTime/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOfflineTimeEntryRequest $request)
    {
        $validated = $request->validated();
        
        // Calculate duration
        $validated['duration_minutes'] = OfflineTimeEntry::calculateDuration(
            $validated['start_time'],
            $validated['end_time']
        );
        
        // Add user_id
        $validated['user_id'] = auth()->id();

        $entry = OfflineTimeEntry::create($validated);

        return redirect()->route('offline-time.index')
            ->with('success', 'Offline time entry created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(OfflineTimeEntry $offlineTimeEntry): Response
    {
        // Authorization check
        if (!auth()->user()->isAdmin() && $offlineTimeEntry->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $offlineTimeEntry->load('user');

        return Inertia::render('OfflineTime/Show', [
            'entry' => $offlineTimeEntry,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OfflineTimeEntry $offlineTimeEntry): Response
    {
        // Authorization check
        if (!auth()->user()->isAdmin() && $offlineTimeEntry->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        return Inertia::render('OfflineTime/Edit', [
            'entry' => $offlineTimeEntry,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOfflineTimeEntryRequest $request, OfflineTimeEntry $offlineTimeEntry)
    {
        $validated = $request->validated();
        
        // Calculate duration
        $validated['duration_minutes'] = OfflineTimeEntry::calculateDuration(
            $validated['start_time'],
            $validated['end_time']
        );

        $offlineTimeEntry->update($validated);

        return redirect()->route('offline-time.index')
            ->with('success', 'Offline time entry updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OfflineTimeEntry $offlineTimeEntry)
    {
        // Authorization check
        if (!auth()->user()->isAdmin() && $offlineTimeEntry->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $offlineTimeEntry->delete();

        return redirect()->route('offline-time.index')
            ->with('success', 'Offline time entry deleted successfully.');
    }

    /**
     * Display the monthly report page (Admin only).
     */
    public function report(Request $request): Response
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $month = $request->input('month', now()->format('Y-m'));
        $data = $this->getAllUsersMonthlySummary($month);

        return Inertia::render('OfflineTime/Report', [
            'month' => $month,
            'summary' => $data['summaries'],
        ]);
    }

    /**
     * Get monthly summary for the authenticated user or all users (admin only).
     */
    public function monthlySummary(Request $request)
    {
        $user = auth()->user();
        $month = $request->input('month', now()->format('Y-m'));

        if ($user->isAdmin() && $request->has('user_id')) {
            // Admin viewing specific user's summary
            $summary = $this->getUserMonthlySummary($request->user_id, $month);
        } elseif ($user->isAdmin()) {
            // Admin viewing all users' summary
            $summary = $this->getAllUsersMonthlySummary($month);
        } else {
            // Regular user viewing their own summary
            $summary = $this->getUserMonthlySummary($user->id, $month);
        }

        return response()->json($summary);
    }

    /**
     * Get monthly summary for a specific user.
     */
    private function getUserMonthlySummary(int $userId, string $month): array
    {
        [$year, $monthNum] = explode('-', $month);
        $totalMinutes = OfflineTimeEntry::where('user_id', $userId)
            ->whereYear('date', $year)
            ->whereMonth('date', $monthNum)
            ->sum('duration_minutes');

        $user = User::find($userId);

        return [
            'user' => $user?->only(['id', 'name']),
            'month' => $month,
            'total_minutes' => $totalMinutes,
            'total_hours' => round($totalMinutes / 60, 2),
            'formatted' => $this->formatMinutes($totalMinutes),
        ];
    }

    /**
     * Get monthly summary for all users.
     */
    private function getAllUsersMonthlySummary(string $month): array
    {
        [$year, $monthNum] = explode('-', $month);
        $summaries = User::withSum([
            'offlineTimeEntries' => fn($query) => 
                $query->whereYear('date', $year)->whereMonth('date', $monthNum)
        ], 'duration_minutes')
            ->get()
            ->map(function ($user) {
                $totalMinutes = $user->offline_time_entries_sum_duration_minutes ?? 0;
                return [
                    'user' => $user->only(['id', 'name']),
                    'total_minutes' => $totalMinutes,
                    'total_hours' => round($totalMinutes / 60, 2),
                    'formatted' => $this->formatMinutes($totalMinutes),
                ];
            });

        return [
            'month' => $month,
            'summaries' => $summaries,
        ];
    }

    /**
     * Format minutes to human-readable format.
     */
    private function formatMinutes(int $minutes): string
    {
        $minutes = abs($minutes);
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;
        
        if ($hours > 0 && $mins > 0) {
            return "{$hours}h {$mins}m";
        } elseif ($hours > 0) {
            return "{$hours}h";
        } else {
            return "{$mins}m";
        }
    }
}
