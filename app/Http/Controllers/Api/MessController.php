<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateMessRequest;
use App\Services\MessService;
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
}
