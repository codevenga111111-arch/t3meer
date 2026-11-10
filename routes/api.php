<?php

use App\Http\Controllers\Api\V1\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| T3meer REST API — Version 1
|--------------------------------------------------------------------------
| Base URL: /api/v1
| Auth:     Authorization: Bearer {token}  (Laravel Sanctum)
| Envelope: { "data": ..., "message": "..." }
| Errors:   422 → { "message": "...", "errors": { "field": ["رسالة بالعربي"] } }
| Paging:   ?page=1&per_page=12 → { data, meta:{current_page, per_page, total, last_page} }
|
| ملاحظة: كل endpoints القسم 6 (فهرس الـ Endpoints) هتتسجّل هنا بالترتيب
| في الأيام التالية (Auth = يوم 4، Onboarding = يوم 5-6، ...).
*/

Route::get('/ping', [HealthController::class, 'ping']);
