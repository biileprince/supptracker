<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityUpdate;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $today = now()->toDateString();
        $weekStart = now()->subDays(6)->toDateString();

        $totalActivities = Activity::count();

        // Today's status breakdown
        $todayUpdates = ActivityUpdate::whereDate('activity_date', $today)->get();
        $todayUniqueUpdates = $todayUpdates->sortByDesc('created_at')->unique('activity_id');

        $todayDone = $todayUniqueUpdates->where('status', 'done')->count();
        $todayInProgress = $todayUniqueUpdates->where('status', 'in_progress')->count();
        $todayPending = max(0, $totalActivities - $todayDone - $todayInProgress);

        // Weekly completion trend
        $weeklyData = [];
        $currentDate = Carbon::parse($weekStart);
        $endDate = now();

        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->toDateString();
            $dayUpdates = ActivityUpdate::whereDate('activity_date', $dateStr)->get();
            $uniqueDayUpdates = $dayUpdates->sortByDesc('created_at')->unique('activity_id');

            $weeklyData[] = [
                'date' => $currentDate->format('D'),
                'done' => $uniqueDayUpdates->where('status', 'done')->count(),
                'total' => $totalActivities,
            ];

            $currentDate->addDay();
        }

        // Recent updates feed (last 8)
        $recentUpdates = ActivityUpdate::with(['user:id,name', 'activity:id,title'])
            ->latest()
            ->take(8)
            ->get()
            ->map(fn ($update) => [
                'id' => $update->id,
                'status' => $update->status,
                'remark' => $update->remark,
                'user_name' => $update->user?->name ?? 'Unknown',
                'activity_title' => $update->activity?->title ?? 'Unknown',
                'created_at' => $update->created_at->diffForHumans(),
            ]);

        return Inertia::render('dashboard', [
            'stats' => [
                'totalActivities' => $totalActivities,
                'todayDone' => $todayDone,
                'todayInProgress' => $todayInProgress,
                'todayPending' => $todayPending,
                'completionRate' => $totalActivities > 0 ? round(($todayDone / $totalActivities) * 100) : 0,
            ],
            'weeklyData' => $weeklyData,
            'recentUpdates' => $recentUpdates,
        ]);
    }
}
