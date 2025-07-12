<?php

namespace App\Services;

use App\DTOs\UserDto;
use App\Enums\MessUserStatus;
use App\Helpers\Pipeline;
use App\Models\Country;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

class UserService
{

    public static function currentUser(): ?User
    {
        return Auth::user() ?? null;
    }

    function isUserNameExits(string $userName)
    {
        $count = User::where("user_name", $userName)->count();
        return $count >= 1;
    }

    function isEmailExits(string $email)
    {
        $count = User::where("email", $email)->count();
        return $count >= 1;
    }

    function createUser(UserDto $userDto): Pipeline
    {


        // if($this->isUserNameExits($userName)){
        //     return Pipeline::error(message:Lang::get("auth.user_name_exits"));
        // }

        if ($this->isEmailExits($userDto->email)) {
            return Pipeline::error(message: Lang::get("auth.email_exits"));
        }

        $country = Country::where("id", $userDto->country)->first();



        $user = User::create(
            [
                "name" => $userDto->name,
                "email" => $userDto->email,
                "phone" => "{$country->dial_code}-{$userDto->phone}",
                "country_id" => $userDto->country,
                "city" => $userDto->city,
                "gender" => $userDto->gender,
                "password" => Hash::make($userDto->password),
                "join_date" => Carbon::now(),
                "status" => MessUserStatus::Active->value,
            ]
        );

        return Pipeline::success($user);
    }

    function login($userNameOrEmail, $password)
    {
        $user = User::where("user_name", $userNameOrEmail)->orWhere("email", $userNameOrEmail)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return Pipeline::error("Username or email not matched with password");
        }

        $token = $user->createToken('Personal Access Token')->plainTextToken;

        return $this->checkLogin($token);
    }

    function checkLogin($token = null): Pipeline
    {
        if ($token) {
            // Manually find the user using Sanctum's token
            $accessToken = PersonalAccessToken::findToken($token);

            if (!$accessToken) {
                return Pipeline::error("Invalid token");
            }

            $user = $accessToken->tokenable; // Get the user associated with the token

            // Log in the user manually for this request
            Auth::setUser($user);
        } else {
            // Use default Sanctum authentication (from request headers)
            $user = Auth::user();
        }

        if (!$user) {
            return Pipeline::error("User not found");
        }
        $user->withoutRelations();

        // Fetch the messUser relationship separately
        $messUser = $user->messUser()->with(["user", "mess", "role.permissions"])->first();

        $data = [
            "user" => $user->load("country"), // Only the user model
            "mess_user" => $messUser, // Fetched separately
            "token" => $token ?? request()->bearerToken() ?? null,
        ];

        return Pipeline::success($data);
    }

    function addEmailOtp(User $user) {}

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
    public function uploadAvatar($file): Pipeline
    {
        $user = Auth::user();

        if (!$user) {
            return Pipeline::error('User not authenticated');
        }

        // Validate file
        if (!$file || !$file->isValid()) {
            return Pipeline::error('Invalid file upload');
        }

        $allowedTypes = ['jpeg', 'png', 'jpg', 'gif'];
        $extension = $file->getClientOriginalExtension();

        if (!in_array(strtolower($extension), $allowedTypes)) {
            return Pipeline::error('Invalid file type. Only JPEG, PNG, JPG, and GIF are allowed');
        }

        if ($file->getSize() > 2048 * 1024) { // 2MB limit
            return Pipeline::error('File size too large. Maximum 2MB allowed');
        }

        try {
            // Delete old avatar if exists
            if ($user->photo_url) {
                $this->deleteAvatarFile($user->photo_url);
            }

            // Generate unique filename
            $filename = 'avatars/' . $user->id . '_' . time() . '.' . $extension;

            // Store file
            $path = $file->storeAs('public', $filename);

            if (!$path) {
                return Pipeline::error('Failed to upload avatar');
            }

            // Update user photo_url
            $user->update(['photo_url' => '/storage/' . $filename]);

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
            // Extract filename from URL path
            $path = str_replace('/storage/', '', $photoUrl);
            $fullPath = storage_path('app/public/' . $path);

            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        } catch (\Exception $e) {
            // Log error but don't fail the operation
            Log::warning('Failed to delete avatar file: ' . $e->getMessage());
        }
    }
}
