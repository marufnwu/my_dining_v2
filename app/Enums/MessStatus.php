<?php

namespace App\Enums;

enum MessStatus : string
{
    case ACTIVE = "active";
    case DEACTIVATED = "deactivated";
    case DELETED = "deleted";

}
