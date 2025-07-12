<?php

namespace App\Services;

use App\Helpers\Pipeline;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProfileService
{
    /**
     * Get current user profile with relationships
     */
    public function getProfile(): Pipeline
    {
        $user = Auth::user();

        if (!$user) {
            return Pipeline::error('User not authenticated');
        }

        // Load user with relationships
        $userWithRelations = $user->load([
            'country',
            'messUser.mess',
            'messUser.role'
        ]);

        $profileData = [
            'user' => $userWithRelations,
            'profile_completion' => $this->calculateProfileCompletion($user),
            'last_updated' => $user->updated_at,
        ];

        return Pipeline::success($profileData);
    }

    /**
     * Update user profile information
     */
    public function updateProfile(array $data): Pipeline
    {
        $user = Auth::user();

        if (!$user) {
            return Pipeline::error('User not authenticated');
        }

        // Validate the update data
        $validationResult = $this->validateProfileUpdate($data, $user);
        if (!$validationResult['valid']) {
            return Pipeline::error($validationResult['message']);
        }

        try {
            // Update user fields
            $user->update($this->sanitizeUpdateData($data));

            // Reload user with relationships
            $updatedUser = $user->fresh(['country', 'messUser.mess', 'messUser.role']);

            return Pipeline::success([
                'user' => $updatedUser,
                'message' => 'Profile updated successfully'
            ]);

        } catch (\Exception $e) {
            return Pipeline::error('Failed to update profile: ' . $e->getMessage());
        }
    }

    /**
     * Upload user avatar
     */
    public function uploadAvatar(UploadedFile $file): Pipeline
    {
        $user = Auth::user();

        if (!$user) {
            return Pipeline::error('User not authenticated');
        }

        // Validate file
        $validator = Validator::make(['avatar' => $file], [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return Pipeline::error('Invalid file: ' . $validator->errors()->first());
        }

        try {
            // Delete old avatar if exists
            if ($user->photo_url) {
                $this->deleteAvatarFile($user->photo_url);
            }

            // Generate unique filename
            $filename = 'avatars/' . $user->id . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();

            // Store file
            $path = Storage::disk('public')->put($filename, file_get_contents($file));

            if (!$path) {
                return Pipeline::error('Failed to upload avatar');
            }

            // Update user photo_url
            $user->update(['photo_url' => Storage::url($filename)]);

            return Pipeline::success([
                'photo_url' => $user->photo_url,
                'message' => 'Avatar uploaded successfully'
            ]);

        } catch (\Exception $e) {
            return Pipeline::error('Failed to upload avatar: ' . $e->getMessage());
        }
    }

    /**
     * Remove user avatar
     */
    public function removeAvatar(): Pipeline
    {
        $user = Auth::user();

        if (!$user) {
            return Pipeline::error('User not authenticated');
        }

        try {
            // Delete avatar file if exists
            if ($user->photo_url) {
                $this->deleteAvatarFile($user->photo_url);

                // Update user photo_url to null
                $user->update(['photo_url' => null]);
            }

            return Pipeline::success([
                'message' => 'Avatar removed successfully'
            ]);

        } catch (\Exception $e) {
            return Pipeline::error('Failed to remove avatar: ' . $e->getMessage());
        }
    }

    /**
     * Calculate profile completion percentage
     */
    private function calculateProfileCompletion(User $user): int
    {
        $fields = [
            'name' => !empty($user->name),
            'email' => !empty($user->email),
            'phone' => !empty($user->phone),
            'country_id' => !empty($user->country_id),
            'city' => !empty($user->city),
            'gender' => !empty($user->gender),
            'photo_url' => !empty($user->photo_url),
        ];

        $completed = array_sum($fields);
        $total = count($fields);

        return round(($completed / $total) * 100);
    }

    /**
     * Validate profile update data
     */
    private function validateProfileUpdate(array $data, User $user): array
    {
        // Check if email is being changed and if it already exists
        if (isset($data['email']) && $data['email'] !== $user->email) {
            if (User::where('email', $data['email'])->exists()) {
                return ['valid' => false, 'message' => 'Email already exists'];
            }
        }

        // Check if phone is being changed and if it already exists
        if (isset($data['phone']) && $data['phone'] !== $user->phone) {
            if (User::where('phone', $data['phone'])->exists()) {
                return ['valid' => false, 'message' => 'Phone number already exists'];
            }
        }

        return ['valid' => true, 'message' => 'Valid'];
    }

    /**
     * Sanitize update data to only allow specific fields
     */
    private function sanitizeUpdateData(array $data): array
    {
        $allowedFields = ['name', 'city', 'gender'];

        return array_intersect_key($data, array_flip($allowedFields));
    }

    /**
     * Delete avatar file from storage
     */
    private function deleteAvatarFile(string $photoUrl): void
    {
        try {
            // Extract filename from URL
            $filename = basename(parse_url($photoUrl, PHP_URL_PATH));
            $path = 'avatars/' . $filename;

            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        } catch (\Exception $e) {
            // Log error but don't fail the operation
            \Log::warning('Failed to delete avatar file: ' . $e->getMessage());
        }
    }
}
