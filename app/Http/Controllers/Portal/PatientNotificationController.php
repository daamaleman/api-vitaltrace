<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListPatientNotificationsRequest;
use App\Http\Resources\PatientNotificationResource;
use App\Models\AppNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PatientNotificationController extends Controller
{
    public function index(ListPatientNotificationsRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();
        $notifications = $this->internalNotifications($request->user()->id)
            ->when(($filters['read'] ?? 'all') === 'read', fn ($query) => $query->whereNotNull('read_at'))
            ->when(($filters['read'] ?? 'all') === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->when(isset($filters['type']), fn ($query) => $query->where('type', $filters['type']))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return PatientNotificationResource::collection($notifications);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'data' => ['unread_count' => $this->internalNotifications($request->user()->id)->whereNull('read_at')->count()],
            'message' => null,
            'errors' => null,
        ]);
    }

    public function markAsRead(Request $request, int $notification): JsonResponse
    {
        $item = $this->internalNotifications($request->user()->id)->find($notification);

        if ($item === null) {
            return response()->json(['data' => null, 'message' => 'Notification not found.', 'errors' => null], Response::HTTP_NOT_FOUND);
        }

        $item->markAsRead();

        return response()->json([
            'data' => new PatientNotificationResource($item->refresh()),
            'message' => 'Notification marked as read.',
            'errors' => null,
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $updatedCount = $this->internalNotifications($request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'data' => ['updated_count' => $updatedCount, 'unread_count' => 0],
            'message' => 'Notifications marked as read.',
            'errors' => null,
        ]);
    }

    private function internalNotifications(int $userId): Builder
    {
        return AppNotification::query()->where('user_id', $userId)->where('channel', 'INTERNAL');
    }
}
