<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

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

    public function index(Request $request)
    {
        [$startDate, $endDate] = $this->getDateRange($request);

        $data = $this->reportService->getSummaryData($startDate, $endDate);

        return Inertia::render('reports/index', [
            'summary' => $data['summary'],
            'chartData' => $data['chartData'],
            'recentUpdates' => $data['recentUpdates'],
            'dateRange' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
        ]);
    }

    public function exportCsv(Request $request)
    {
        [$startDate, $endDate] = $this->getDateRange($request);

        return $this->reportService->generateCsv($startDate, $endDate);
    }

    public function exportPdf(Request $request)
    {
        [$startDate, $endDate] = $this->getDateRange($request);

        return $this->reportService->generatePdf($startDate, $endDate);
    }
}
