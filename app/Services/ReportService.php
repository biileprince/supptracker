<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityUpdate;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReportService
{
    /**
     * Get summary data for a date range
     */
    public function getSummaryData(Carbon $startDate, Carbon $endDate): array
    {
        $activities = Activity::with(['updates' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('activity_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->with('user:id,name');
        }])->get();

        $totalActivities = $activities->count();
        $daysInRange = $startDate->diffInDays($endDate) + 1;
        $expectedTotalUpdates = $activities->where('is_recurring', true)->count() * $daysInRange
                              + $activities->where('is_recurring', false)->count();

        $updatesQuery = ActivityUpdate::whereBetween('activity_date', [$startDate->toDateString(), $endDate->toDateString()]);

        // Group updates by date and get latest status per activity per day
        $allUpdates = $updatesQuery->get();

        $completionByDate = [];
        $currentDate = clone $startDate;

        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->toDateString();
            $updatesOnDate = $allUpdates->where('activity_date', $dateStr);

            // Get latest update per activity
            $uniqueUpdates = $updatesOnDate->sortByDesc('created_at')->unique('activity_id');

            $done = $uniqueUpdates->where('status', 'done')->count();
            $inProgress = $uniqueUpdates->where('status', 'in_progress')->count();
            $pending = $uniqueUpdates->where('status', 'pending')->count();

            $completionByDate[] = [
                'date' => $currentDate->format('M d'),
                'done' => $done,
                'inProgress' => $inProgress,
                'pending' => $pending,
            ];

            $currentDate->addDay();
        }

        $latestUniqueUpdates = $allUpdates->sortByDesc('created_at')->unique(function ($item) {
            return $item->activity_id.'-'.$item->activity_date;
        });

        $totalDone = $latestUniqueUpdates->where('status', 'done')->count();
        $totalInProgress = $latestUniqueUpdates->where('status', 'in_progress')->count();
        $totalPending = $latestUniqueUpdates->where('status', 'pending')->count();
        $missing = max(0, $expectedTotalUpdates - ($totalDone + $totalInProgress + $totalPending));

        return [
            'summary' => [
                'totalActivities' => $totalActivities,
                'expectedUpdates' => $expectedTotalUpdates,
                'completionRate' => $expectedTotalUpdates > 0 ? round(($totalDone / $expectedTotalUpdates) * 100) : 0,
                'statusStats' => [
                    ['name' => 'Done', 'value' => $totalDone, 'color' => '#22c55e'],
                    ['name' => 'In Progress', 'value' => $totalInProgress, 'color' => '#eab308'],
                    ['name' => 'Pending/Missing', 'value' => $totalPending + $missing, 'color' => '#94a3b8'],
                ],
            ],
            'chartData' => $completionByDate,
            'recentUpdates' => $allUpdates->sortByDesc('created_at')->take(10)->load('user:id,name', 'activity:id,title')->values(),
        ];
    }

    /**
     * Generate CSV export
     */
    public function generateCsv(Carbon $startDate, Carbon $endDate)
    {
        $updates = ActivityUpdate::whereBetween('activity_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->with(['activity', 'user'])
            ->orderBy('activity_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=activity_report_{$startDate->toDateString()}_to_{$endDate->toDateString()}.csv",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = ['Date', 'Activity', 'Category', 'User', 'Status', 'Remark', 'Time Updated'];

        $callback = function () use ($updates, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($updates as $update) {
                fputcsv($file, [
                    $update->activity_date,
                    $update->activity->title ?? 'N/A',
                    $update->activity->category ?? 'N/A',
                    $update->user->name ?? 'N/A',
                    ucfirst(str_replace('_', ' ', $update->status)),
                    $update->remark,
                    $update->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Generate PDF export
     */
    public function generatePdf(Carbon $startDate, Carbon $endDate)
    {
        $activities = Activity::with(['updates' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('activity_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->with('user:id,name,job_title')
                ->orderBy('created_at', 'desc');
        }])->orderBy('category')->orderBy('title')->get();

        $pdf = Pdf::loadView('reports.pdf', [
            'activities' => $activities,
            'startDate' => $startDate->format('M d, Y'),
            'endDate' => $endDate->format('M d, Y'),
            'generatedAt' => now()->format('M d, Y H:i:s'),
        ]);

        return $pdf->download("activity_report_{$startDate->toDateString()}_to_{$endDate->toDateString()}.pdf");
    }
}
