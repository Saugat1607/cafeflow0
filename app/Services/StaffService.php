<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StaffService
{
    /**
     * Get all staff members.
     */
    public function getAllStaff()
    {
        return Staff::with('user')
            ->latest()
            ->get();
    }

    /**
     * Create a new staff account.
     */
    public function createStaff(array $data): Staff
    {
        return DB::transaction(function () use ($data) {

            // Create login account in users table
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            // Generate staff code
            $staffCode = $this->generateStaffCode();

            // Create staff record
            $staff = Staff::create([
                'user_id' => $user->id,
                'staff_code' => $staffCode,
                'phone' => $data['phone'] ?? null,
                'position' => $data['position'] ?? null,
                'status' => $data['status'] ?? true,
            ]);

            return $staff->load('user');
        });
    }

    /**
     * Get a single staff member.
     */
    public function getStaff(int $id): Staff
    {
        return Staff::with('user')->findOrFail($id);
    }

    /**
     * Update staff information.
     */
    public function updateStaff(int $id, array $data): Staff
    {
        return DB::transaction(function () use ($id, $data) {

            $staff = Staff::with('user')->findOrFail($id);

            // Update user information
            $userData = [];

            if (isset($data['name'])) {
                $userData['name'] = $data['name'];
            }

            if (isset($data['email'])) {
                $userData['email'] = $data['email'];
            }

            if (!empty($data['password'])) {
                $userData['password'] = Hash::make($data['password']);
            }

            if (!empty($userData)) {
                $staff->user->update($userData);
            }

            // Update staff information
            $staffData = [];

            if (array_key_exists('phone', $data)) {
                $staffData['phone'] = $data['phone'];
            }

            if (array_key_exists('position', $data)) {
                $staffData['position'] = $data['position'];
            }

            if (array_key_exists('status', $data)) {
                $staffData['status'] = $data['status'];
            }

            if (!empty($staffData)) {
                $staff->update($staffData);
            }

            return $staff->fresh('user');
        });
    }

    /**
     * Delete a staff member.
     */
    public function deleteStaff(int $id): bool
    {
        return DB::transaction(function () use ($id) {

            $staff = Staff::with('user')->findOrFail($id);

            $user = $staff->user;

            // Delete staff record
            $staff->delete();

            // Delete associated login account
            if ($user) {
                $user->delete();
            }

            return true;
        });
    }

    /**
     * Generate unique staff code.
     *
     * Example:
     * CF-STF-001
     * CF-STF-002
     */
    private function generateStaffCode(): string
    {
        $lastStaff = Staff::orderBy('id', 'desc')->first();

        $nextNumber = $lastStaff
            ? $lastStaff->id + 1
            : 1;

        do {
            $staffCode = 'CF-STF-' . str_pad(
                $nextNumber,
                3,
                '0',
                STR_PAD_LEFT
            );

            $exists = Staff::where('staff_code', $staffCode)->exists();

            if ($exists) {
                $nextNumber++;
            }

        } while ($exists);

        return $staffCode;
    }
}
