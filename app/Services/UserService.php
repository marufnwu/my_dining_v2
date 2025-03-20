<?php

namespace App\Services;

use App\DTOs\UserDto;
use App\Enums\MessUserStatus;
use App\Helpers\Pipeline;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
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

        $user = User::create(
            [
                "name" => $userDto->name,
                "email" => $userDto->email,
                "phone" => $userDto->phone,
                "country" => $userDto->country,
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
            $user = auth()->user();
        }

        if (!$user) {
            return Pipeline::error("User not found");
        }
        $user->withoutRelations();

        // Fetch the messUser relationship separately
        $messUser = $user->messUser()->with(["user", "mess", "role.permissions"])->first();

        $data = [
            "user" => $user, // Only the user model
            "mess_user" => $messUser, // Fetched separately
            "token" => $token ?? request()->bearerToken() ?? null,
        ];

        return Pipeline::success($data);
    }

    function addEmailOtp(User $user) {}
}
