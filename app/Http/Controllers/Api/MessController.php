<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MessService;
use Illuminate\Support\Facades\Request;

class MessController extends Controller
{
    public function __construct(
        protected MessService $service
    ) {}

    function createMess(Request $request) : Returntype {
        
    }
}
