<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(protected ReportService $reportService) {}

    private function getDateRange(Request $request): array
    {
        $now = now();
        $startDate = $request->query('start_date')
            ? Carbon::parse($request->query('start_date'))->startOfDay()
            : $now->copy()->subDays(6)->startOfDay();

        $endDate = $request->query('end_date')
            ? Carbon::parse($request->query('end_date'))->startOfDay()
            : clone $now->startOfDay();

        // Ensure start is before end
        if ($startDate > $endDate) {
            $temp = $startDate;
            $startDate = $endDate;
            $endDate = $temp;
        }

        return [$startDate, $endDate];
    }

    private function getFilters(Request $request): array
    {
        return [
            'activity_id' => $request->query('activity_id') ? (string) $request->query('activity_id') : '',
            'user_id' => $request->query('user_id') ? (string) $request->query('user_id') : '',
            'status' => $request->query('status') ? (string) $request->query('status') : '',
        ];
    }

    public function index(Request $request): Response
    {
        [$startDate, $endDate] = $this->getDateRange($request);
        $filters = $this->getFilters($request);

        $data = $this->reportService->getSummaryData($startDate, $endDate, $filters);

        return Inertia::render('reports/index', [
            'summary' => $data['summary'],
            'chartData' => $data['chartData'],
            'recentUpdates' => $data['recentUpdates'],
            'activities' => Activity::query()
                ->select('id', 'title', 'category')
                ->orderBy('category')
                ->orderBy('title')
                ->get(),
            'users' => User::query()
                ->select('id', 'name', 'department', 'job_title')
                ->orderBy('name')
                ->get(),
            'filters' => [
                'activityId' => $filters['activity_id'],
                'userId' => $filters['user_id'],
                'status' => $filters['status'],
            ],
            'dateRange' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
        ]);
    }

    public function exportCsv(Request $request)
    {
        [$startDate, $endDate] = $this->getDateRange($request);
        $filters = $this->getFilters($request);

        return $this->reportService->generateCsv($startDate, $endDate, $filters);
    }

    public function exportPdf(Request $request)
    {
        [$startDate, $endDate] = $this->getDateRange($request);
        $filters = $this->getFilters($request);

        return $this->reportService->generatePdf($startDate, $endDate, $filters);
    }
}
