<?php

namespace App\Http\Controllers\Api;

use App\Constants\MessPermission;
use App\Http\Controllers\Controller;
use App\Models\Mess;
use App\Models\MessRequest;
use App\Services\MessUserService;
use App\Facades\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessManagementController extends Controller
{
    protected MessUserService $messUserService;

    public function __construct()
    {
        $this->messUserService = new MessUserService();
    }

    /**
     * Get current user's mess information
     */
    public function getCurrentMess()
    {
        $pipeline = $this->messUserService->getCurrentMessInfo();
        return $pipeline->toApiResponse();
    }

    /**
     * Leave current mess
     */
    public function leaveMess()
    {
        $pipeline = $this->messUserService->leaveMess();
        return $pipeline->toApiResponse();
    }

    /**
     * Get list of available messes to join
     */
    public function getAvailableMesses()
    {
        $pipeline = $this->messUserService->getAvailableMesses();
        return $pipeline->toApiResponse();
    }

    /**
     * Send join request to a mess
     */
    public function sendJoinRequest(Request $request)
    {
        $validatedData = $request->validate([
            'mess_id' => 'required|integer|exists:messes,id'
        ]);

        $mess = Mess::findOrFail($validatedData['mess_id']);
        $pipeline = $this->messUserService->sendJoinRequest($mess);
        return $pipeline->toApiResponse();
    }

    /**
     * Get user's join request history
     */
    public function getUserJoinRequests()
    {
        $pipeline = $this->messUserService->getUserJoinRequests();
        return $pipeline->toApiResponse();
    }

    /**
     * Cancel a pending join request
     */
    public function cancelJoinRequest(MessRequest $messRequest)
    {
        $pipeline = $this->messUserService->cancelJoinRequest($messRequest);
        return $pipeline->toApiResponse();
    }

    /**
     * Get pending join requests for current mess (requires permission)
     */    public function getMessJoinRequests()
    {
        // Check permission
        if (!Permission::can(MessPermission::MESS_JOIN_REQUEST_MANAGE) && !Auth::user()->role?->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage join requests'
            ], 403);
        }

        $pipeline = $this->messUserService->getMessJoinRequests();
        return $pipeline->toApiResponse();
    }

    /**
     * Accept a join request (requires permission)
     */    public function acceptJoinRequest(MessRequest $messRequest)
    {
        // Check permission
        if (!Permission::can(MessPermission::MESS_JOIN_REQUEST_MANAGE) && !Auth::user()->role?->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage join requests'
            ], 403);
        }

        $pipeline = $this->messUserService->acceptJoinRequest($messRequest);
        return $pipeline->toApiResponse();
    }

    /**
     * Reject a join request (requires permission)
     */    public function rejectJoinRequest(Request $request, MessRequest $messRequest)
    {
        // Check permission
        if (!Permission::can(MessPermission::MESS_JOIN_REQUEST_MANAGE) && !Auth::user()->role?->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage join requests'
            ], 403);
        }

        $validatedData = $request->validate([
            'reason' => 'sometimes|string|max:255'
        ]);

        $pipeline = $this->messUserService->rejectJoinRequest($messRequest, $validatedData['reason'] ?? null);
        return $pipeline->toApiResponse();
    }

    /**
     * Close current mess (requires admin permission)
     */    public function closeMess()
    {
        // Check if user is admin
        if (!Auth::user()->role?->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can close a mess'
            ], 403);
        }

        $pipeline = $this->messUserService->closeMess();
        return $pipeline->toApiResponse();
    }
}
