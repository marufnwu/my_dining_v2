<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CountryService;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    public function __construct(protected CountryService $service) { }

    function countries() {
        return $this->service->countriers()->toApiResponse();
    }
}
