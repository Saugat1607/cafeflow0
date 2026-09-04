<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Expense;
use App\Models\Order;
use App\Models\DailyReport;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Generate and save the daily report for a specific date.
     */
    public function generateDailyReport($date = null): DailyReport
    {
        $date = $date
            ? Carbon::parse($date)->toDateString()
            : now()->toDateString();

        /*
        |--------------------------------------------------------------------------
        | SALES
        |--------------------------------------------------------------------------
        | Bills are the source of truth for sales.
        */
        $bills = Bill::whereDate('bill_date', $date)
            ->where('payment_status', 'paid')
            ->get();

        $totalSales = $bills->sum('total');

        $totalBills = $bills->count();

        /*
        |--------------------------------------------------------------------------
        | PAYMENT BREAKDOWN
        |--------------------------------------------------------------------------
        */
        $cashSales = $bills
            ->where('payment_method', 'cash')
            ->sum('total');

        $cardSales = $bills
            ->where('payment_method', 'card')
            ->sum('total');

        $esewaSales = $bills
            ->where('payment_method', 'esewa')
            ->sum('total');

        $otherSales = $bills
            ->reject(function ($bill) {
                return in_array(
                    strtolower($bill->payment_method),
                    ['cash', 'card', 'esewa']
                );
            })
            ->sum('total');

        /*
        |--------------------------------------------------------------------------
        | ORDERS
        |--------------------------------------------------------------------------
        */
        $totalOrders = Order::whereDate('paid_at', $date)
            ->where('status', 'paid')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | EXPENSES
        |--------------------------------------------------------------------------
        */
        $totalExpenses = Expense::whereDate('expense_date', $date)
            ->sum('amount');

        /*
        |--------------------------------------------------------------------------
        | PROFIT
        |--------------------------------------------------------------------------
        */
        $netProfit = $totalSales - $totalExpenses;

        /*
        |--------------------------------------------------------------------------
        | SAVE / UPDATE DAILY REPORT
        |--------------------------------------------------------------------------
        | updateOrCreate prevents duplicate reports for the same date.
        */
        return DailyReport::updateOrCreate(
            [
                'report_date' => $date,
            ],
            [
                'total_sales' => $totalSales,
                'total_expenses' => $totalExpenses,
                'net_profit' => $netProfit,

                'total_bills' => $totalBills,
                'total_orders' => $totalOrders,

                'cash_sales' => $cashSales,
                'card_sales' => $cardSales,
                'esewa_sales' => $esewaSales,
                'other_sales' => $otherSales,
            ]
        );
    }

    /**
     * Get a saved daily report.
     */
    public function getDailyReport($date): ?DailyReport
    {
        return DailyReport::whereDate(
            'report_date',
            Carbon::parse($date)->toDateString()
        )->first();
    }

    /**
     * Get reports between two dates.
     */
    public function getReports($fromDate, $toDate)
    {
        return DailyReport::whereBetween('report_date', [
            Carbon::parse($fromDate)->toDateString(),
            Carbon::parse($toDate)->toDateString(),
        ])
            ->orderBy('report_date', 'desc')
            ->get();
    }

    /**
     * Generate reports for a date range.
     *
     * Useful when you already have old sales/expense data
     * and want to create historical daily reports.
     */
    public function generateReportsForRange($fromDate, $toDate)
    {
        $start = Carbon::parse($fromDate);
        $end = Carbon::parse($toDate);

        $reports = [];

        while ($start->lte($end)) {
            $reports[] = $this->generateDailyReport(
                $start->toDateString()
            );

            $start->addDay();
        }

        return collect($reports);
    }
}
