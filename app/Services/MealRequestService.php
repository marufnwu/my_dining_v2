<?php

namespace App\Services;

use App\Enums\MealRequestStatus;
use App\Helpers\Pipeline;
use App\Models\MealRequest;
use App\Models\Month;
use App\Models\Meal;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MealRequestService
{
    /**
     * Create a new meal request.
     *
     * @param array $data
     * @return Pipeline
     */
    public function createMealRequest(array $data): Pipeline
    {
        $mealRequest = MealRequest::updateOrCreate(["date"=> $data["date"]], $data);
        return Pipeline::success(data: $mealRequest, message: "Meal request created successfully");
    }

    /**
     * Update a meal request (only if pending and belongs to user).
     *
     * @param MealRequest $mealRequest
     * @param array $data
     * @return Pipeline
     */
    public function updateMealRequest(MealRequest $mealRequest, array $data): Pipeline
    {
        // Check if the meal request is still pending
        if ($mealRequest->status !== MealRequestStatus::PENDING) {
            return Pipeline::error(message: "Cannot update meal request that is not pending");
        }

        // Check if the meal request belongs to the authenticated user
        $authMessUser = app()->getMessUser();
        if ($mealRequest->mess_user_id !== $authMessUser->id) {
            return Pipeline::error(message: "You can only update your own meal requests");
        }

        $mealRequest->update($data);
        return Pipeline::success(data: $mealRequest->fresh(), message: "Meal request updated successfully");
    }

    /**
     * Delete a meal request (only if pending and belongs to user).
     *
     * @param MealRequest $mealRequest
     * @return Pipeline
     */
    public function deleteMealRequest(MealRequest $mealRequest): Pipeline
    {
        // Check if the meal request is still pending
        if ($mealRequest->status !== MealRequestStatus::PENDING) {
            return Pipeline::error(message: "Cannot delete meal request that is not pending");
        }

        // Check if the meal request belongs to the authenticated user
        $authMessUser = app()->getMessUser();
        if ($mealRequest->mess_user_id !== $authMessUser->id) {
            return Pipeline::error(message: "You can only delete your own meal requests");
        }

        $mealRequest->delete();
        return Pipeline::success(message: "Meal request deleted successfully");
    }

    /**
     * Cancel a meal request (only if pending and belongs to user).
     *
     * @param MealRequest $mealRequest
     * @return Pipeline
     */
    public function cancelMealRequest(MealRequest $mealRequest): Pipeline
    {
        // Check if the meal request is still pending
        if ($mealRequest->status !== MealRequestStatus::PENDING) {
            return Pipeline::error(message: "Cannot cancel meal request that is not pending");
        }

        // Check if the meal request belongs to the authenticated user
        $authMessUser = app()->getMessUser();
        if ($mealRequest->mess_user_id !== $authMessUser->id) {
            return Pipeline::error(message: "You can only cancel your own meal requests");
        }

        $mealRequest->update(['status' => MealRequestStatus::CANCELLED]);
        return Pipeline::success(data: $mealRequest->fresh(), message: "Meal request cancelled successfully");
    }

    /**
     * Approve a meal request and create a meal.
     *
     * @param MealRequest $mealRequest
     * @param string|null $comment
     * @return Pipeline
     */
    public function approveMealRequest(MealRequest $mealRequest, ?string $comment = null): Pipeline
    {
        // Check if the meal request is still pending
        if ($mealRequest->status !== MealRequestStatus::PENDING) {
            return Pipeline::error(message: "Meal request is not pending");
        }

        DB::beginTransaction();
        try {
            // Create the meal from the meal request
            $meal = Meal::create([
                'mess_user_id' => $mealRequest->mess_user_id,
                'mess_id' => $mealRequest->mess_id,
                'month_id' => $mealRequest->month_id,
                'date' => $mealRequest->date,
                'breakfast' => $mealRequest->breakfast,
                'lunch' => $mealRequest->lunch,
                'dinner' => $mealRequest->dinner,
            ]);

            // Update the meal request status
            $mealRequest->update([
                'status' => MealRequestStatus::APPROVED,
                'comment' => $comment,
                'approved_by' => app()->getMessUser()->id,
                'approved_at' => Carbon::now(),
            ]);

            DB::commit();
            return Pipeline::success(
                data: ['meal' => $meal, 'meal_request' => $mealRequest->fresh()],
                message: "Meal request approved and meal created successfully"
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return Pipeline::error(message: "Failed to approve meal request: " . $e->getMessage());
        }
    }

    /**
     * Reject a meal request.
     *
     * @param MealRequest $mealRequest
     * @param string|null $reason
     * @return Pipeline
     */
    public function rejectMealRequest(MealRequest $mealRequest, ?string $reason = null): Pipeline
    {
        // Check if the meal request is still pending
        if ($mealRequest->status !== MealRequestStatus::PENDING) {
            return Pipeline::error(message: "Meal request is not pending");
        }

        $mealRequest->update([
            'status' => MealRequestStatus::REJECTED,
            'rejected_reason' => $reason,
            'approved_by' => app()->getMessUser()->id,
            'approved_at' => Carbon::now(),
        ]);

        return Pipeline::success(data: $mealRequest->fresh(), message: "Meal request rejected successfully");
    }

    /**
     * List meal requests with filters.
     *
     * @param Month $month
     * @param array $filters
     * @return Pipeline
     */
    public function listMealRequests(Month $month, array $filters = []): Pipeline
    {
        $query = MealRequest::where('month_id', $month->id)
            ->with(['messUser.user', 'approvedBy.user']);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['mess_user_id'])) {
            $query->where('mess_user_id', $filters['mess_user_id']);
        }

        if (isset($filters['date'])) {
            $query->where('date', $filters['date']);
        }

        if (isset($filters['date_from'])) {
            $query->where('date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('date', '<=', $filters['date_to']);
        }

        $mealRequests = $query->orderBy('created_at', 'desc')->get();

        return Pipeline::success(data: $mealRequests, message: "Meal requests retrieved successfully");
    }

    /**
     * Get a single meal request.
     *
     * @param MealRequest $mealRequest
     * @return Pipeline
     */
    public function getMealRequest(MealRequest $mealRequest): Pipeline
    {
        $mealRequest->load(['messUser.user', 'approvedBy.user']);
        return Pipeline::success(data: $mealRequest, message: "Meal request retrieved successfully");
    }

    /**
     * Get meal requests for the authenticated user.
     *
     * @param Month $month
     * @param array $filters
     * @return Pipeline
     */
    public function getUserMealRequests(Month $month, array $filters = []): Pipeline
    {
        $authMessUser = app()->getMessUser();
        $filters['mess_user_id'] = $authMessUser->id;

        return $this->listMealRequests($month, $filters);
    }

    /**
     * Get pending meal requests for management.
     *
     * @param Month $month
     * @return Pipeline
     */
    public function getPendingMealRequests(Month $month): Pipeline
    {
        return $this->listMealRequests($month, ['status' => MealRequestStatus::PENDING]);
    }
}
