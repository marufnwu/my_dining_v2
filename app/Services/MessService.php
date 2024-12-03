<?php

namespace App\Services;

use App\Enums\MessStatus;
use App\Helpers\Pipeline;
use App\Models\Mess;
use app\Service\UserService;
use Illuminate\Support\Facades\Lang;

class MessService
{
    function create($messName, $name, $userName, $cc, $phone, $password, $country, $city, $gender) : Pipeline {

        $userService = new UserService();
        if($userService->isUserNameExits($userName)){
            return Pipeline::error(message:Lang::get("auth.user_name_exits"));
        }


        $mess = Mess::create([
            "name"=>$messName,
            "status"=> MessStatus::ACTIVE->value
        ]);


        if($mess){
            
        }
    }
}
