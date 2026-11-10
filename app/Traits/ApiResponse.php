<?php

namespace App\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

/**
 * اتفاقيات الـ REST API (المراجع: SPEC 0.4).
 *
 * - النجاح:      { "data": ..., "message": "..." }
 * - الخطأ 422:   { "message": "...", "errors": { "field": ["رسالة بالعربي"] } }
 * - Pagination:  { "data": [...], "meta": { current_page, per_page, total, last_page }, "message": "..." }
 * - التوكن:      Authorization: Bearer {token}
 */
trait ApiResponse
{
    /** نجاح عام مع بيانات. */
    protected function apiSuccess(mixed $data = null, string $message = 'تم', int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'message' => $message,
        ], $status);
    }

    /** نجاح بإنشاء عنصر جديد. */
    protected function apiCreated(mixed $data = null, string $message = 'تم الإنشاء بنجاح'): JsonResponse
    {
        return $this->apiSuccess($data, $message, 201);
    }

    /** نجاح بدون محتوى (204). */
    protected function apiNoContent(string $message = 'تم'): JsonResponse
    {
        return response()->json(['data' => null, 'message' => $message], 204);
    }

    /** خطأ عام (401/403/404/409/...). */
    protected function apiError(string $message, int $status = 400, ?array $errors = null, ?string $code = null): JsonResponse
    {
        $payload = ['message' => $message];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        if ($code !== null) {
            $payload['code'] = $code;
        }

        return response()->json($payload, $status);
    }

    /** رد مُجزّأ (paginated) بالشكل الموحد. */
    protected function apiPaginated(LengthAwarePaginator $page, string $message = 'تم'): JsonResponse
    {
        return response()->json([
            'data' => $page->items(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'last_page' => $page->lastPage(),
            ],
            'message' => $message,
        ]);
    }

    /**
     * قراءة per_page من الطلب: ?per_page=12 (الافتراضي 12، والحد الأقصى 100).
     */
    protected function perPage(int $default = 12, int $max = 100): int
    {
        $value = (int) request()->input('per_page', $default);

        return max(1, min($value, $max));
    }
}
