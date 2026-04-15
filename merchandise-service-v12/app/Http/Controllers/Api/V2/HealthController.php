<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class HealthController extends Controller
{
    public function check(): JsonResponse
    {
        return response()->json(['status' => 'ok', 'service' => 'merchandise-service']);
    }
}
