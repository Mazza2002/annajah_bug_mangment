<?php

namespace App\Services;

use App\Models\Bug;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function getOverview()
    {
        return [
            'total_bugs' => Bug::count(),
            'open_bugs' => Bug::where('status', 'Open')->count(),
            'in_progress' => Bug::where('status', 'In Progress')->count(),
            'fixed' => Bug::where('status', 'Fixed')->count(),
            'closed' => Bug::where('status', 'Closed')->count(),
            'severity_distribution' => Bug::select('severity', DB::raw('count(*) as count'))
                ->groupBy('severity')
                ->get(),
            'trends' => Bug::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
        ];
    }

    public function getDeveloperPerformance()
    {
        return User::whereHas('role', function($q) {
                $q->where('slug', 'developer');
            })
            ->withCount(['bugsAssigned', 'bugsAssigned as fixed_count' => function($q) {
                $q->where('status', 'Fixed');
            }])
            ->get()
            ->map(function($user) {
                return [
                    'name' => $user->name,
                    'assigned' => $user->bugs_assigned_count,
                    'fixed' => $user->fixed_count,
                    'efficiency' => $user->bugs_assigned_count > 0 
                        ? round(($user->fixed_count / $user->bugs_assigned_count) * 100, 2) 
                        : 0
                ];
            });
    }
}
