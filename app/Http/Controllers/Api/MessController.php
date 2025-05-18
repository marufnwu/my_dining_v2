<?php

namespace App\Http\Controllers\Api;

use App\DTOs\UserDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateMessRequest;
use App\Http\Requests\CreateUserAccountRequest;
use App\Models\Country;
use App\Models\User;
use App\Services\MessService;
use App\Services\MessUserService;
use Illuminate\Support\Facades\Request;

class MessController extends Controller
{
    public function __construct(
        protected MessService $service
    ) {}

    function createMess(CreateMessRequest $request) {
        $data = $request->validated();
        $mess = $this->service->create($data['mess_name']);
        return $mess->toApiResponse();
    }

    function messUser(?User $user = null){
        $user = $user ?? auth()->user();
        $mus = new MessUserService(app()->getMess());

        return $mus->getMessUser($user)->toApiResponse();
    }


}
