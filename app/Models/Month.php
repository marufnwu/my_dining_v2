<?php

namespace App\Models;

use App\Enums\MonthType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Month extends Model
{
    protected $fillable = [
        'mess_id',
        'name',
        'type',
        'start_at',
        'end_at',
        "is_active"
    ];

    protected $appends = [
        "is_active"
    ];

    public function getIsActiveAttribute() : bool {
        if($this->type == MonthType::MANUAL->value){
            if ($this->end_at) {
                return false;
            }
        }


        if($this->type == MonthType::AUTOMATIC->value){
            if ($this->end_at < Carbon::now()) {
                return false;
            }
        }

        return true;
    }
}
