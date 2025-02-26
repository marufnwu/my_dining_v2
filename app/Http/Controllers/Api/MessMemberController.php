<?php

namespace App\Http\Controllers\Api;

use App\DTOs\UserDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateUserAccountRequest;
use App\Models\Country;
use App\Services\MessService;
use App\Services\MessUserService;

class MessMemberController extends Controller
{
    public function __construct(
        protected MessUserService $service
    ) {}

   public  function createUserAddMess(CreateUserAccountRequest $request) {


        $data = $request->validated();

        $country = Country::where("id", $data['country_id'])->first();

        $userDto = new UserDto(
            name: $data["name"],
            country: $country->id,
            phone: $data["phone"],
            password: $data["password"],
            email: $data["email"],
            city: $data["city"],
            gender: $data['gender'],
        );

        $pipeline = $this->service->createAndAddUser($userDto);

        return $pipeline->toApiResponse();
    }

    public function list() {
        return $this->service->messMembers()->toApiResponse();
    }
}
