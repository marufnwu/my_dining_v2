<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateUserAccountRequest;
use App\Models\Country;
use App\Services\UserService;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    public function createAccount(CreateUserAccountRequest $request){
        $data = $request->validated();

        $country = Country::where("id", $data['country_id'])->first();

        $pipeline = $this->userService->createUser(
            $data["name"],
            $data['user_name'],
            $country->id,
            $data["phone"],
            $data["password"],
            $data["email"],
            $data["city"],
            $data["gender"]
        );

        return $pipeline->toApiResponse();
    }
}
