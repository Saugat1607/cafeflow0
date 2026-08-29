<?php

namespace App\Services;

use App\Models\Expense;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ExpenseService
{
    /**
     * Get expenses with optional filters.
     */
    public function getExpenses(array $filters = []): LengthAwarePaginator
    {
        $query = Expense::query()
            ->with('creator')
            ->latest('expense_date')
            ->latest('id');

        if (!empty($filters['date'])) {
            $query->whereDate('expense_date', $filters['date']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('expense_date', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('expense_date', '<=', $filters['to_date']);
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        return $query->paginate(
            $filters['per_page'] ?? 15
        );
    }

    /**
     * Create an expense.
     */
    public function createExpense(array $data): Expense
    {
        return Expense::create($data);
    }

    /**
     * Get one expense.
     */
    public function getExpense(int $id): Expense
    {
        return Expense::with('creator')->findOrFail($id);
    }

    /**
     * Update an expense.
     */
    public function updateExpense(Expense $expense, array $data): Expense
    {
        $expense->update($data);

        return $expense->fresh()->load('creator');
    }

    /**
     * Delete an expense.
     */
    public function deleteExpense(Expense $expense): void
    {
        $expense->delete();
    }

    /**
     * Get total expenses for a date.
     */
    public function getDailyTotal(string $date): float
    {
        return (float) Expense::whereDate('expense_date', $date)
            ->sum('amount');
    }

    public function getCategoryTotals(
        ?string $fromDate = null,
        ?string $toDate = null
    ): Collection {
        $query = Expense::query()
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total');

        if ($fromDate) {
            $query->whereDate('expense_date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('expense_date', '<=', $toDate);
        }

        return $query->get();
    }
}
