<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    /**
     * فحص سريع إن الـ API شغّال والـ envelope موحّد.
     * GET /api/v1/ping
     */
    public function ping(): JsonResponse
    {
        return $this->apiSuccess([
            'app' => config('app.name'),
            'api_version' => 'v1',
            'time' => now()->toIso8601String(),
        ], 'الـ API شغّال');
    }
}
