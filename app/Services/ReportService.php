<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityUpdate;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReportService
{
    private function applyFilters(Builder $query, Carbon $startDate, Carbon $endDate, array $filters): Builder
    {
        $query->whereBetween('activity_date', [$startDate->toDateString(), $endDate->toDateString()]);

        if (! empty($filters['activity_id'])) {
            $query->where('activity_id', $filters['activity_id']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }

    /**
            $query->whereBetween('activity_date', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()]);
     */
    public function getSummaryData(Carbon $startDate, Carbon $endDate, array $filters = []): array
    {
        $allUpdates = $this->applyFilters(
            ActivityUpdate::query()->with(['activity:id,title,category', 'user:id,name,department,job_title']),
            $startDate,
            $endDate,
            $filters,
        )->get();

        $activities = $allUpdates
            ->groupBy('activity_id')
            ->map(function (Collection $updatesForActivity) {
                $activity = $updatesForActivity->first()?->activity;

                return (object) [
                    'id' => $activity?->id ?? $updatesForActivity->first()?->activity_id,
                    'title' => $activity?->title ?? 'N/A',
                    'category' => $activity?->category,
                    'updates' => $updatesForActivity->values(),
                ];
            })
            ->values();

        $totalActivities = $activities->count();
        $totalUpdates = $allUpdates->count();

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
            return $item->activity_id . '-' . $item->activity_date;
        });

        $totalDone = $latestUniqueUpdates->where('status', 'done')->count();
        $totalInProgress = $latestUniqueUpdates->where('status', 'in_progress')->count();
        $totalPending = $latestUniqueUpdates->where('status', 'pending')->count();

        return [
            'summary' => [
                'totalActivities' => $totalActivities,
                'expectedUpdates' => $totalUpdates,
                'completionRate' => $totalUpdates > 0 ? round(($totalDone / $totalUpdates) * 100) : 0,
                'statusStats' => [
                    ['name' => 'Done', 'value' => $totalDone, 'color' => '#22c55e'],
                    ['name' => 'In Progress', 'value' => $totalInProgress, 'color' => '#eab308'],
                    ['name' => 'Pending', 'value' => $totalPending, 'color' => '#94a3b8'],
                ],
            ],
            'chartData' => $completionByDate,
            'recentUpdates' => $allUpdates->sortByDesc('created_at')->take(10)->values(),
        ];
    }

    /**
     * Generate CSV export
     */
    public function generateCsv(Carbon $startDate, Carbon $endDate, array $filters = [])
    {
        $updates = $this->applyFilters(
            ActivityUpdate::query()->with(['activity:id,title,category', 'user:id,name,department,job_title']),
            $startDate,
            $endDate,
            $filters,
        )
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
    public function generatePdf(Carbon $startDate, Carbon $endDate, array $filters = [])
    {
        $updates = $this->applyFilters(
            ActivityUpdate::query()->with(['activity:id,title,category', 'user:id,name,department,job_title']),
            $startDate,
            $endDate,
            $filters,
        )
            ->orderBy('activity_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $activities = $updates->groupBy('activity_id')->map(function (Collection $updatesForActivity) {
            $activity = $updatesForActivity->first()?->activity;

            return (object) [
                'title' => $activity?->title,
                'category' => $activity?->category,
                'updates' => $updatesForActivity->values(),
            ];
        })->values();

        $pdf = Pdf::loadView('reports.pdf', [
            'activities' => $activities,
            'startDate' => $startDate->format('M d, Y'),
            'endDate' => $endDate->format('M d, Y'),
            'generatedAt' => now()->format('M d, Y H:i:s'),
        ]);

        return $pdf->download("activity_report_{$startDate->toDateString()}_to_{$endDate->toDateString()}.pdf");
    }
}
