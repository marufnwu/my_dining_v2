<?php
namespace app\Service;

use App\Helpers\Pipeline;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Lang;

class UserService{

    function isUserNameExits(string $userName)  {
        $count = User::where("user_name", $userName)->count();
        return $count >= 1 ;
    }

    function createSuperUser($name, $userName, $cc, $phone, $messId, $password, $email, $country, $city, $gender) : Pipeline {

        if($this->isUserNameExits($userName)){
            return Pipeline::error(message:Lang::get("auth.user_name_exits"));
        }

        $user = User::create(
            [
                "mess_id"=>$messId,
                "name"=>$name,
                "user_name"=>$userName,
                "email"=>$email,
                "country_code"=>$cc,
                "phone"=>$phone,
                "country"=>$country,
                "city"=>$city,
                "gender"=>$gender,
                "password"=>$password,
                "join_date"=>Carbon::now()
            ]
        );

        return Pipeline::success($user);
    }

}
