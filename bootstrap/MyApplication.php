<?php
namespace Bootstrap;

use App\Models\Mess;
use App\Models\MessUser;
use App\Models\Month;
use Illuminate\Foundation\Application;
class MyApplication extends Application
{
    protected ?Month $month = null;
    protected ?Mess $mess = null;
    protected ?MessUser $messUser = null;

    public function setMonth(Month $month) : self {
        $this->month = $month;
        return $this;
    }
    public function  getMonth() : ?Month {
        return $this->month;
    }

    public function setMess(Mess $mess) : self {
        $this->mess = $mess;
        return $this;
    }
    public function getMess() : ?Mess {
        return $this->mess;
    }

    public function setMessUser(MessUser $messUser) : self {
        $this->messUser = $messUser;
        return $this;
    }
    public function getMessUser() : ?MessUser {
        return $this->messUser;
    }
}
