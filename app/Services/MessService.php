<?php

namespace App\Services;

use App\Enums\MessStatus;
use App\Helpers\Pipeline;
use App\Models\Mess;
use app\Service\UserService;
use Illuminate\Support\Facades\Lang;

class MessService
{
    function create($messName, $name, $userName, $email, $cc, $phone, $password, $country, $city, $gender) : Pipeline {

        $userService = new UserService();



        $mess = Mess::create([
            "name"=>$messName,
            "status"=> MessStatus::ACTIVE->value
        ]);


        if($mess){
            $userPipeline = $userService->createSuperUser($name, $userName, $cc, $phone, $mess->id, $password, $email, $country, $city, $gender);
        }
    }
}
