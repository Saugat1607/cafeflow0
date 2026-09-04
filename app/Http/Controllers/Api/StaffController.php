<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StaffService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    protected StaffService $staffService;

    public function __construct(StaffService $staffService)
    {
        $this->staffService = $staffService;
    }

    /**
     * Get all staff.
     */
    public function index(): JsonResponse
    {
        $staff = $this->staffService->getAllStaff();

        return response()->json([
            'success' => true,
            'message' => 'Staff retrieved successfully.',
            'data' => $staff,
        ]);
    }

    /**
     * Create staff account.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:20',
            ],

            'position' => [
                'nullable',
                'string',
                'max:100',
            ],

            'status' => [
                'nullable',
                'boolean',
            ],
        ]);

        $staff = $this->staffService->createStaff($validated);

        return response()->json([
            'success' => true,
            'message' => 'Staff account created successfully.',
            'data' => $staff,
        ], 201);
    }

    /**
     * Get one staff member.
     */
    public function show(int $id): JsonResponse
    {
        $staff = $this->staffService->getStaff($id);

        return response()->json([
            'success' => true,
            'message' => 'Staff retrieved successfully.',
            'data' => $staff,
        ]);
    }

    /**
     * Update staff.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $staff = $this->staffService->getStaff($id);

        $validated = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($staff->user_id),
            ],

            'password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:20',
            ],

            'position' => [
                'nullable',
                'string',
                'max:100',
            ],

            'status' => [
                'nullable',
                'boolean',
            ],
        ]);

        $staff = $this->staffService->updateStaff(
            $id,
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => 'Staff updated successfully.',
            'data' => $staff,
        ]);
    }

    /**
     * Delete staff.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->staffService->deleteStaff($id);

        return response()->json([
            'success' => true,
            'message' => 'Staff deleted successfully.',
        ]);
    }
}
