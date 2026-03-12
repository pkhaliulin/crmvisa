<?php

namespace App\Modules\Notification\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * GET /notifications — список уведомлений пользователя (пагинированный).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->orderByDesc('created_at')
            ->paginate(20);

        $items = $notifications->map(fn ($n) => [
            'id'         => $n->id,
            'type'       => $n->data['type'] ?? 'info',
            'data'       => $n->data,
            'read_at'    => $n->read_at?->toISOString(),
            'created_at' => $n->created_at->toISOString(),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $items,
            'meta'    => [
                'current_page' => $notifications->currentPage(),
                'last_page'    => $notifications->lastPage(),
                'per_page'     => $notifications->perPage(),
                'total'        => $notifications->total(),
            ],
        ]);
    }

    /**
     * GET /notifications/unread-count — количество непрочитанных.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $request->user()->unreadNotifications()->count();

        return ApiResponse::success(['count' => $count]);
    }

    /**
     * POST /notifications/{id}/read — отметить как прочитанное.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if (! $notification) {
            return ApiResponse::notFound('Уведомление не найдено.');
        }

        $notification->markAsRead();

        return ApiResponse::success(null, 'Marked as read.');
    }

    /**
     * POST /notifications/read-all — отметить все как прочитанные.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return ApiResponse::success(null, 'All marked as read.');
    }
}
