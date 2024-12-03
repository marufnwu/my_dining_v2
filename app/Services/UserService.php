<?php
namespace app\Service;

use App\Models\User;

class UserService{

    function isUserNameExits(string $userName)  {
        $count = User::where("user_name", $userName)->count();
        return $count >= 1 ;
    }

}
