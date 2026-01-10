<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOfflineTimeEntryRequest;
use App\Http\Requests\UpdateOfflineTimeEntryRequest;
use App\Models\OfflineTimeEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class OfflineTimeEntryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = Auth::user();
        $query = OfflineTimeEntry::with(['user', 'team']);

        // Teams where user can manage members (Owner or Admin)
        $manageableTeamIds = \App\Models\Team::where('owner_id', $user->id)
            ->orWhereHas('members', function($q) use ($user) {
                $q->where('team_user.user_id', $user->id)->where('team_user.role', 'admin');
            })->pluck('id')->toArray();

        // Base restriction
        if (!$user->isAdmin()) {
            $query->where(function($q) use ($user, $manageableTeamIds) {
                $q->where('user_id', $user->id)
                  ->orWhereIn('team_id', $manageableTeamIds);
            });
        }

        // Apply filters
        if ($request->has('user_id') && ($user->isAdmin() || count($manageableTeamIds) > 0)) {
            // For team admins, only allow filtering users if they are in a manageable team
            // For simplicity, we just apply the user_id filter, the base restriction handles the rest
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('team_id')) {
            $query->where('team_id', $request->team_id);
        }

        if ($request->has('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        if ($request->has('month')) {
            $month = $request->month;
            [$year, $monthNum] = explode('-', $month);
            $query->whereYear('date', $year)->whereMonth('date', $monthNum);
        }

        // Clone query to calculate total duration
        $totalDurationMinutes = $query->clone()->sum('duration_minutes');

        $entries = $query->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Get users and teams for filters
        $users = [];
        if ($user->isAdmin()) {
            $users = User::orderBy('name')->get(['id', 'name']);
        } elseif (count($manageableTeamIds) > 0) {
            $users = User::whereHas('teams', function($q) use ($manageableTeamIds) {
                $q->whereIn('teams.id', $manageableTeamIds);
            })->orWhere('id', $user->id)->distinct()->orderBy('name')->get(['id', 'name']);
        }

        $myTeams = $user->teams()->get(['teams.id', 'teams.name']);
        $ownedTeams = $user->ownedTeams()->get(['id', 'name']);
        $allUserTeams = $myTeams->merge($ownedTeams)->unique('id')->values();

        return Inertia::render('OfflineTime/Index', [
            'entries' => $entries,
            'users' => $users,
            'teams' => $allUserTeams,
            'filters' => $request->only(['user_id', 'team_id', 'date_from', 'date_to', 'month']),
            'totalDuration' => (int)$totalDurationMinutes,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        /** @var User $user */
        $user = Auth::user();
        $myTeams = $user->teams()->get(['teams.id', 'teams.name']);
        $ownedTeams = $user->ownedTeams()->get(['id', 'name']);
        $allUserTeams = $myTeams->merge($ownedTeams)->unique('id')->values();

        return Inertia::render('OfflineTime/Create', [
            'teams' => $allUserTeams,
        ]);
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
        $validated['user_id'] = Auth::id();

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
        /** @var User $user */
        $user = Auth::user();
        if (!$user->isAdmin() && $offlineTimeEntry->user_id !== $user->id) {
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
        /** @var User $user */
        $user = Auth::user();
        if (!$user->isAdmin() && $offlineTimeEntry->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        $myTeams = $user->teams()->get(['teams.id', 'teams.name']);
        $ownedTeams = $user->ownedTeams()->get(['id', 'name']);
        $allUserTeams = $myTeams->merge($ownedTeams)->unique('id')->values();

        return Inertia::render('OfflineTime/Edit', [
            'entry' => $offlineTimeEntry,
            'teams' => $allUserTeams,
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
        /** @var User $user */
        $user = Auth::user();
        if (!$user->isAdmin() && $offlineTimeEntry->user_id !== $user->id) {
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
        /** @var User $user */
        $user = Auth::user();
        if (!$user->isAdmin()) {
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
        /** @var User $user */
        $user = Auth::user();
        $month = $request->input('month', now()->format('Y-m'));

        if ($user->isAdmin() && $request->has('user_id')) {
            $summary = $this->getUserMonthlySummary($request->user_id, $month);
        } elseif ($user->isAdmin()) {
            $summary = $this->getAllUsersMonthlySummary($month);
        } else {
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
