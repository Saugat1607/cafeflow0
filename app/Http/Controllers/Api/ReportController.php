<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {
    }

    /**
     * Get today's report.
     */
    public function today(): JsonResponse
    {
        $date = now()->toDateString();

        // Generate/update today's report
        $report = $this->reportService->generateDailyReport($date);

        return response()->json([
            'success' => true,
            'message' => 'Today report retrieved successfully.',
            'data' => $report,
        ]);
    }

    /**
     * Get report for a specific date.
     */
    public function show(string $date): JsonResponse
    {
        try {
            $date = Carbon::parse($date)->toDateString();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid date format.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Generate if the report does not exist
        |--------------------------------------------------------------------------
        */
        $report = $this->reportService->getDailyReport($date);

        if (!$report) {
            $report = $this->reportService->generateDailyReport($date);
        }

        return response()->json([
            'success' => true,
            'message' => 'Daily report retrieved successfully.',
            'data' => $report,
        ]);
    }

    /**
     * Get report history between two dates.
     */
    public function history(Request $request): JsonResponse
    {
        $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
        ]);

        $fromDate = $request->from_date
            ?? now()->subDays(30)->toDateString();

        $toDate = $request->to_date
            ?? now()->toDateString();

        $reports = $this->reportService->getReports(
            $fromDate,
            $toDate
        );

        return response()->json([
            'success' => true,
            'data' => $reports,
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);
    }

    /**
     * Manually generate/update a report.
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'date' => ['required', 'date'],
        ]);

        $report = $this->reportService->generateDailyReport(
            $request->date
        );

        return response()->json([
            'success' => true,
            'message' => 'Daily report generated successfully.',
            'data' => $report,
        ]);
    }

    /**
     * Generate reports for a date range.
     */
    public function generateRange(Request $request): JsonResponse
    {
        $request->validate([
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
        ]);

        $reports = $this->reportService->generateReportsForRange(
            $request->from_date,
            $request->to_date
        );

        return response()->json([
            'success' => true,
            'message' => 'Reports generated successfully.',
            'data' => $reports,
        ]);
    }
}
