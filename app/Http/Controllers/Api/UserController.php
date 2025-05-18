<?php

namespace App\Http\Controllers\Api;

use App\DTOs\UserDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateUserAccountRequest;
use App\Http\Requests\UserLoginRequest;
use App\Models\Country;
use App\Services\UserService;

class UserController extends Controller
{

    public function __construct(
        protected UserService $userService
    ) {}

    public function createAccount(CreateUserAccountRequest $request)
    {
        $data = $request->validated();

        $country = Country::where("id", $data['country_id'] ?? "" )->orWhere("dial_code", $data['country_code'] ?? "")->first();

        $userDto = new UserDto(
            name: $data["name"],
            country: $country->id,
            phone: $data["phone"],
            password: $data["password"],
            email: $data["email"],
            city: $data["city"],
            gender: $data['gender'],
        );

        $pipeline = $this->userService->createUser($userDto);

        return $pipeline->toApiResponse();
    }

    function login(UserLoginRequest $request)
    {
        $data = $request->validated();

        $pipeline = $this->userService->login($data["email"], $data["password"]);

        return $pipeline->toApiResponse();
    }

    function checkLogin()
    {
        $pipeline = $this->userService->checkLogin();
        return $pipeline->toApiResponse();
    }
}
