<?php

namespace App\Http\Controllers\Api;

use App\Constants\MessPermission;
use App\Enums\ErrorCode;
use App\Enums\MealRequestStatus;
use App\Facades\Permission;
use App\Helpers\Pipeline;
use App\Http\Controllers\Controller;
use App\Models\MealRequest;
use App\Rules\MessUserExistsInCurrentMess;
use App\Rules\UserInitiatedInCurrentMonth;
use App\Services\MealRequestService;
use Illuminate\Http\Request;

class MealRequestController extends Controller
{
    private MealRequestService $mealRequestService;

    public function __construct(MealRequestService $mealRequestService)
    {
        $this->mealRequestService = $mealRequestService;
    }

    /**
     * Create a new meal request
     */
    public function create(Request $request)
    {
        // Validate the request data
        $validatedData = $request->validate([
            "mess_user_id" => [
                "required",
                "numeric",
                new MessUserExistsInCurrentMess(),
                new UserInitiatedInCurrentMonth(),
            ],
            "date" => "required|date",
            "breakfast" => "nullable|numeric|min:0",
            "lunch" => "nullable|numeric|min:0",
            "dinner" => "nullable|numeric|min:0",
            "comment" => "sometimes|string|nullable",
        ]);

        // Ensure at least one meal is requested
        if (empty($validatedData['breakfast']) && empty($validatedData['lunch']) && empty($validatedData['dinner'])) {
            return Pipeline::error(message: "At least one meal (breakfast, lunch, or dinner) must be requested")->toApiResponse();
        }

        // Set default values
        $validatedData['breakfast'] = $validatedData['breakfast'] ?? 0;
        $validatedData['lunch'] = $validatedData['lunch'] ?? 0;
        $validatedData['dinner'] = $validatedData['dinner'] ?? 0;

        // Add additional data
        $validatedData['mess_id'] = app()->getMess()->id;
        $validatedData['month_id'] = app()->getMonth()->id;
        $validatedData['status'] = MealRequestStatus::PENDING->value; // Default status (pending)

        // Call the service to create the meal request
        $pipeline = $this->mealRequestService->createMealRequest($validatedData);

        // Return the API response
        return $pipeline->toApiResponse();
    }

    /**
     * Update a meal request (only for pending requests by the owner)
     */
    public function update(Request $request, MealRequest $mealRequest)
    {
        $data = $request->validate([
            "date" => "sometimes|date",
            "breakfast" => "sometimes|numeric|min:0",
            "lunch" => "sometimes|numeric|min:0",
            "dinner" => "sometimes|numeric|min:0",
            "comment" => "sometimes|string|nullable",
        ]);


        $pipeline = $this->mealRequestService->updateMealRequest($mealRequest, $data);

        return $pipeline->toApiResponse();
    }

    /**
     * Delete a meal request (only for pending requests by the owner)
     */
    public function delete(MealRequest $mealRequest)
    {
        $pipeline = $this->mealRequestService->deleteMealRequest($mealRequest);

        return $pipeline->toApiResponse();
    }

    /**
     * Cancel a meal request (only for pending requests by the owner)
     */
    public function cancel(MealRequest $mealRequest)
    {
        $pipeline = $this->mealRequestService->cancelMealRequest($mealRequest);

        return $pipeline->toApiResponse();
    }

    /**
     * Approve a meal request (requires permission)
     */
    public function approve(Request $request, MealRequest $mealRequest)
    {
        if (!Permission::canAny([
            MessPermission::MEAL_REQUEST_MANAGEMENT,
            MessPermission::MEAL_REQUEST_APPROVE,
            MessPermission::MEAL_MANAGEMENT
        ])) {
            return Pipeline::error(
                message: "You don't have permission to approve meal requests",
                errorCode: ErrorCode::PERMISSION_DENIED->value
            )->toApiResponse();
        }

        $data = $request->validate([
            "comment" => "sometimes|string|nullable",
        ]);

        $pipeline = $this->mealRequestService->approveMealRequest(
            $mealRequest,
            $data['comment'] ?? null
        );

        return $pipeline->toApiResponse();
    }

    /**
     * Reject a meal request (requires permission)
     */
    public function reject(Request $request, MealRequest $mealRequest)
    {
        if (!Permission::canAny([
            MessPermission::MEAL_REQUEST_MANAGEMENT,
            MessPermission::MEAL_REQUEST_REJECT,
            MessPermission::MEAL_MANAGEMENT
        ])) {
            return Pipeline::error(
                message: "You don't have permission to reject meal requests",
                errorCode: ErrorCode::PERMISSION_DENIED->value
            )->toApiResponse();
        }

        $data = $request->validate([
            "rejected_reason" => "sometimes|string|nullable",
        ]);

        $pipeline = $this->mealRequestService->rejectMealRequest(
            $mealRequest,
            $data['rejected_reason'] ?? null
        );

        return $pipeline->toApiResponse();
    }

    /**
     * List meal requests with filters
     */
    public function list(Request $request)
    {
        $filters = $request->validate([
            'status' => ['sometimes', new \Illuminate\Validation\Rules\Enum(MealRequestStatus::class)],
            'date' => 'sometimes|date',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date',
            'mess_user_id' => 'sometimes|integer',
        ]);

        // Check if user has meal request management permission
        $hasManagementPermission = Permission::canAny([
            MessPermission::MEAL_REQUEST_MANAGEMENT,
            MessPermission::MEAL_MANAGEMENT
        ]);

        // If user doesn't have management permission, only show their own requests
        if (!$hasManagementPermission) {
            $filters['mess_user_id'] = app()->getMessUser()->id;
        }

        $pipeline = $this->mealRequestService->listMealRequests(app()->getMonth(), $filters);

        return $pipeline->toApiResponse();
    }

    /**
     * Get user's own meal requests
     */
    public function myRequests(Request $request)
    {
        $filters = $request->validate([
            'status' => ['sometimes', new \Illuminate\Validation\Rules\Enum(MealRequestStatus::class)],
            'date' => 'sometimes|date',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date',
        ]);

        $pipeline = $this->mealRequestService->getUserMealRequests(app()->getMonth(), $filters);

        return $pipeline->toApiResponse();
    }

    /**
     * Get pending meal requests for management
     */
    public function pending()
    {
        if (!Permission::canAny([
            MessPermission::MEAL_REQUEST_MANAGEMENT,
            MessPermission::MEAL_MANAGEMENT
        ])) {
            return Pipeline::error(
                message: "You don't have permission to view pending meal requests",
                errorCode: ErrorCode::PERMISSION_DENIED->value
            )->toApiResponse();
        }

        $pipeline = $this->mealRequestService->getPendingMealRequests(app()->getMonth());

        return $pipeline->toApiResponse();
    }

    /**
     * Show a single meal request
     */
    public function show(MealRequest $mealRequest)
    {
        // Check if user can view this meal request
        $hasManagementPermission = Permission::canAny([
            MessPermission::MEAL_REQUEST_MANAGEMENT,
            MessPermission::MEAL_MANAGEMENT
        ]);

        $isOwner = $mealRequest->mess_user_id === app()->getMessUser()->id;

        if (!$hasManagementPermission && !$isOwner) {
            return Pipeline::error(
                message: "You don't have permission to view this meal request",
                errorCode: ErrorCode::PERMISSION_DENIED->value
            )->toApiResponse();
        }

        $pipeline = $this->mealRequestService->getMealRequest($mealRequest);

        return $pipeline->toApiResponse();
    }
}
