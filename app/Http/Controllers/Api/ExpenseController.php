<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Services\ExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function __construct (
        protected ExpenseService $expenseService
    ) {

    }

    public function index(Request $request)
    {
        $filters = [
            'date' => $request->input('date'),
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'category' => $request->input('category'),
            'search' => $request->input('search'),
            'payment_method' => $request->input('payment_method'),
            'per_page' => $request->input('per_page', 10),
        ];

        $expenses = $this->expenseService->getExpenses($filters);
        return ExpenseResource::collection($expenses);
    }

    public function store (ExpenseRequest $request): ExpenseResource
    {
        $data = $request->validated();

        if($request->user()){
            $data['created_by'] = $request->user()->id;
        }

        $expense = $this->expenseService->createExpense($data);
        return new ExpenseResource($expense->load('creator '));
    }

    public function show (int $id): ExpenseResource
    {
        $expense = $this->expenseService->getExpense($id);
        return new ExpenseResource($expense);
    }

    public function update (ExpenseRequest $request, int $id): ExpenseResource
    {
       $expense = $this->expenseService->getExpense($id);
        $expense = $this->expenseService->updateExpense($expense, $request->validated() );
        return new ExpenseResource($expense);
    }

    public function destroy (int $id):JsonResponse
    {
        $expense = $this->expenseService->getExpense($id);
        $this->expenseService->deleteExpense($expense);
        return response()->json(['message' => 'Expense deleted successfully']);
    }

    public function dailyTotal(Request $request): JsonResponse
    {
        $date = $request->input('date', now()->toDateString());
        $total = $this->expenseService->getDailyTotal($date);

        return response()->json(['date' => $date, 'total_expenses' => $total]);
    }

    public function categoryTotal(Request $request): JsonResponse
    {
        $totals = $this->expenseService-> getCategoryTotals(
            $request->input('from_date'),
            $request->input('to_date')
        );

        return response()->json(['date' => $totals]);
    }

}
