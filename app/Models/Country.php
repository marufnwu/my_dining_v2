<?php

namespace App\Models;

use App\Traits\HasModelName;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasModelName;
    protected $fillable = [
        "name",
        "code",
        "dial_code"
    ];
}
