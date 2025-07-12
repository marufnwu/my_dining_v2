<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum Gender : string
{
    use EnumToArray;

    case MALE = "male";
    case FEMALE = "female";
    case UNDEFINED = "other";

}
