<?php
namespace App\Services;

use App\Helpers\Pipeline;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;

class UserService{

    function isUserNameExits(string $userName)  {
        $count = User::where("user_name", $userName)->count();
        return $count >= 1 ;
    }

    function isEmailExits(string $email){
        $count = User::where("email", $email)->count();
        return $count >= 1 ;
    }

    function createUser($name, /*$userName,*/ $country, $phone,  $password, $email,  $city, $gender) : Pipeline {

        // if($this->isUserNameExits($userName)){
        //     return Pipeline::error(message:Lang::get("auth.user_name_exits"));
        // }

        if($this->isEmailExits($email)){
            return Pipeline::error(message:Lang::get("auth.email_exits"));
        }

        $user = User::create(
            [
                "name"=>$name,
                // "user_name"=>$userName,
                "email"=>$email,
                "phone"=>$phone,
                "country"=>$country,
                "city"=>$city,
                "gender"=>$gender,
                "password"=>Hash::make($password),
                "join_date"=>Carbon::now()
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
            'token' => $token
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
