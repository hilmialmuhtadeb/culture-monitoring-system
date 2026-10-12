<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'message' => 'connected.',
            'data' => [
                'application' => config('app.name'),
                'status' => 'ok',
            ],
        ]);
    }
}
