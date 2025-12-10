<?php

namespace App\Http\Controllers;

use App\Http\Requests\Frenet\CalculateShippingRequest;
use App\Services\FrenetService;

class FrenetController extends Controller
{
    protected $frenetService;

    public function __construct(FrenetService $frenetService)
    {
        $this->frenetService = $frenetService;
    }

    public function calculateShipping(CalculateShippingRequest $request)
    {
        return $this->frenetService->shippingData($request->validated());
    }
}
