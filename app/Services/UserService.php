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

class UserService{

    public static function currentUser() : ?User {
        return Auth::user() ?? null;
    }

    function isUserNameExits(string $userName)  {
        $count = User::where("user_name", $userName)->count();
        return $count >= 1 ;
    }

    function isEmailExits(string $email){
        $count = User::where("email", $email)->count();
        return $count >= 1 ;
    }

    function createUser(UserDto $userDto) : Pipeline {


        // if($this->isUserNameExits($userName)){
        //     return Pipeline::error(message:Lang::get("auth.user_name_exits"));
        // }

        if($this->isEmailExits($userDto->email)){
            return Pipeline::error(message:Lang::get("auth.email_exits"));
        }

        $user = User::create(
            [
                "name"=>$userDto->name,
                "email"=>$userDto->email,
                "phone"=>$userDto->phone,
                "country"=>$userDto->country,
                "city"=>$userDto->city,
                "gender"=>$userDto->gender,
                "password"=>Hash::make($userDto->password),
                "join_date"=>Carbon::now(),
                "status"=>MessUserStatus::Active->value,
            ]
        );

        return Pipeline::success($user);
    }

    function login($userNameOrEmail, $password){
        $user = User::where("user_name", $userNameOrEmail)->orWhere("email", $userNameOrEmail)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return Pipeline::error("Username or email not matched with password");
        }

        $token = $user->createToken('Personal Access Token')->plainTextToken;

        return Pipeline::success([
            'user' => $user,
            'token' => $token,
            "user_id"=>$user->id,
        ]);
    }

    function checkLogin() : Pipeline {
        $user = auth()->user();

        if(!$user){
            return Pipeline::error("User not found");
        }

        return Pipeline::success($user);

    }

    function addEmailOtp(User $user){

    }

}
