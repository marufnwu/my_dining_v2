<?php
namespace Bootstrap;

use App\Models\Month;
use Illuminate\Foundation\Application;
class MyApplication extends Application
{
    protected ?Month $month = null;

    public function setMonth(Month $month) : self {
        $this->month = $month;
        return $this;
    }
    public function getMonth() : ?Month {
        return $this->month;
    }
}
