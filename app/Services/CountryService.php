<?php

namespace App\Services;

use App\Helpers\Pipeline;

class CountryService
{
    // Add your service methods here

    function countriers(?bool $active = null) : Pipeline {

        $countries = \App\Models\Country::query();
        return Pipeline::success(data: $countries->get());
    }
}
