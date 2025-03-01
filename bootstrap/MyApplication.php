<?php
namespace Bootstrap;

use App\Models\Mess;
use App\Models\Month;
use Illuminate\Foundation\Application;
class MyApplication extends Application
{
    protected ?Month $month = null;
    protected ?Mess $mess = null;

    public function setMonth(Month $month) : self {
        $this->month = $month;
        return $this;
    }
    public function getMonth() : ?Month {
        return $this->month;
    }

    public function setMess(Mess $mess) : self {
        $this->mess = $mess;
        return $this;
    }
    public function getMess() : ?Mess {
        return $this->mess;
    }
}
