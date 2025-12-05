<?php

namespace App\Http\Controllers;

use App\Services\FrenetService;
use Illuminate\Http\Request;

class FrenetController extends Controller
{
    protected $frenetService;

    public function __construct(FrenetService $frenetService)
    {
        $this->frenetService = $frenetService;
    }

    public function calculateShipping(Request $request)
    {
        return $this->frenetService->shippingData($request->all());
    }
}
