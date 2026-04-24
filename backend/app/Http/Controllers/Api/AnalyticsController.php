<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bug;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function overview(Request $request)
    {
        $total = Bug::count();
        $open = Bug::where('status', 'Open')->count();
        $inProgress = Bug::where('status', 'In Progress')->count();
        $critical = Bug::where('severity', 'Critical')->count();

        $activities = ActivityLog::with('user')
            ->latest()
            ->take(10)
            ->get();

        return response()->json([
            'stats' => [
                'total' => $total,
                'open' => $open,
                'inProgress' => $inProgress,
                'critical' => $critical,
            ],
            'activities' => $activities
        ]);
    }

    public function developerPerformance(Request $request)
    {
        // Simple mock for chart data
        return response()->json([
            'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
            'resolved' => [2, 5, 3, 8, 4]
        ]);
    }
}
