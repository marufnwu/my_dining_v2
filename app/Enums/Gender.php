<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum Gender : string
{
    use EnumToArray;

    case MALE = "Male";
    case FEMALE = "Female";
    case UNDEFINED = "Other";

}
