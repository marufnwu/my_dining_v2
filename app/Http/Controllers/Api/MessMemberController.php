<?php

namespace App\Http\Controllers\Api;

use App\DTOs\UserDto;
use App\Helpers\ApiResponse;
use App\Helpers\Pipeline;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateUserAccountRequest;
use App\Models\Country;
use App\Models\MessUser;
use App\Models\Month;
use App\Models\User;
use App\Services\MessService;
use App\Services\MessUserService;
use App\Utils\ContainerData;

class MessMemberController extends Controller
{
    public function __construct(
        protected MessUserService $service
    ) {}

    public  function createUserAddMess(CreateUserAccountRequest $request)
    {


        $data = $request->validated();

        if(array_key_exists("country_id", $data)){
            $country = Country::where("id", $data['country_id'])->first();
        }else{
            $country = Country::where("dial_code", $data['country_code'])->first();
        }


        $userDto = new UserDto(
            name: $data["name"],
            country: $country->id ?? null,
            phone: $data["phone"],
            password: $data["password"],
            email: $data["email"],
            city: $data["city"],
            gender: $data['gender'],
        );

        $pipeline = $this->service->createAndAddUser($userDto);

        return $pipeline->toApiResponse();
    }

    public function list()
    {
        return $this->service->messMembers()->toApiResponse();
    }

    function inititatedUser( $status) {
        $status = filter_var($status, FILTER_VALIDATE_BOOLEAN);
        return $this->service->initiated(app()->getMonth(), $status)->toApiResponse();
    }

    function initiateUser(MessUser $messUser)
    {
        return $this->service->initiateUser($messUser)->toApiResponse();
    }

    function initiateAll()
    {
        return $this->service->initiateAll($messUser)->toApiResponse();
    }
}
