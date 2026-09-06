<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DailyBalanceController extends Controller
{
    /**
     * Get daily balance.
     *
     * GET /api/daily-balance
     * GET /api/daily-balance?date=2026-09-06
     */
    public function index(Request $request)
    {
        $date = $request->input(
            'date',
            now()->toDateString()
        );

        // -----------------------------
        // SALES
        // -----------------------------

        $salesQuery = Bill::query()
            ->whereDate('bill_date', $date);

        $totalSales = (float) $salesQuery->sum('total');

        $totalBills = $salesQuery->count();


        // -----------------------------
        // SALES BY PAYMENT METHOD
        // -----------------------------

        $salesByPaymentMethod = Bill::query()
            ->whereDate('bill_date', $date)
            ->select(
                'payment_method',
                DB::raw('SUM(total) as amount'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('payment_method')
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    $item->payment_method => [
                        'amount' => (float) $item->amount,
                        'count' => (int) $item->count,
                    ],
                ];
            });


        // -----------------------------
        // EXPENSES
        // -----------------------------

        $expenseQuery = Expense::query()
            ->whereDate('expense_date', $date);

        $totalExpenses = (float) $expenseQuery->sum('amount');

        $totalExpensesCount = $expenseQuery->count();


        // -----------------------------
        // EXPENSES BY CATEGORY
        // -----------------------------

        $expensesByCategory = Expense::query()
            ->whereDate('expense_date', $date)
            ->select(
                'category',
                DB::raw('SUM(amount) as amount'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('category')
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    $item->category => [
                        'amount' => (float) $item->amount,
                        'count' => (int) $item->count,
                    ],
                ];
            });


        // -----------------------------
        // NET BALANCE
        // -----------------------------

        $netBalance = $totalSales - $totalExpenses;


        // -----------------------------
        // RESPONSE
        // -----------------------------

        return response()->json([
            'success' => true,

            'data' => [
                'date' => $date,

                'sales' => [
                    'total' => $totalSales,
                    'bill_count' => $totalBills,
                    'by_payment_method' => $salesByPaymentMethod,
                ],

                'expenses' => [
                    'total' => $totalExpenses,
                    'expense_count' => $totalExpensesCount,
                    'by_category' => $expensesByCategory,
                ],

                'balance' => [
                    'total_sales' => $totalSales,
                    'total_expenses' => $totalExpenses,
                    'net_balance' => $netBalance,
                ],
            ],
        ]);
    }
}
