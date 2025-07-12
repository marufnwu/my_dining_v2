<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UploadAvatarRequest;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(
        protected ProfileService $profileService
    ) {}

    /**
     * Get current user profile
     */
    public function show(Request $request): JsonResponse
    {
        $pipeline = $this->profileService->getProfile();
        return $pipeline->toApiResponse();
    }

    /**
     * Update user profile information
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $data = $request->validated();
        $pipeline = $this->profileService->updateProfile($data);
        return $pipeline->toApiResponse();
    }

    /**
     * Upload user avatar/photo
     */
    public function uploadAvatar(UploadAvatarRequest $request): JsonResponse
    {
        $pipeline = $this->profileService->uploadAvatar($request->file('avatar'));
        return $pipeline->toApiResponse();
    }

    /**
     * Remove user avatar/photo
     */
    public function removeAvatar(Request $request): JsonResponse
    {
        $pipeline = $this->profileService->removeAvatar();
        return $pipeline->toApiResponse();
    }
}
