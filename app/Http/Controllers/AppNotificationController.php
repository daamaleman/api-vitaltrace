<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAppNotificationRequest;
use App\Http\Requests\UpdateAppNotificationRequest;
use App\Http\Resources\AppNotificationResource;
use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * CRUD endpoints for notifications.
 *
 * Actual email delivery is handled asynchronously by jobs; this controller
 * manages the notification records.
 */
class AppNotificationController extends Controller
{
    /**
     * List paginated notifications with their recipient.
     */
    public function index(): AnonymousResourceCollection
    {
        $notifications = AppNotification::with('user')->latest('id')->paginate(15);

        return AppNotificationResource::collection($notifications);
    }

    /**
     * Create a new notification.
     */
    public function store(StoreAppNotificationRequest $request): JsonResponse
    {
        $notification = AppNotification::create($request->validated());

        return response()->json([
            'data' => new AppNotificationResource($notification->load('user')),
            'message' => 'Notificación creada correctamente.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }

    /**
     * Show a single notification.
     */
    public function show(AppNotification $notification): JsonResponse
    {
        return response()->json([
            'data' => new AppNotificationResource($notification->load('user')),
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Update an existing notification.
     */
    public function update(UpdateAppNotificationRequest $request, AppNotification $notification): JsonResponse
    {
        $notification->update($request->validated());

        return response()->json([
            'data' => new AppNotificationResource($notification->load('user')),
            'message' => 'Notificación actualizada correctamente.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Delete a notification.
     */
    public function destroy(AppNotification $notification): JsonResponse
    {
        $notification->delete();

        return response()->json([
            'data' => null,
            'message' => 'Notificación eliminada correctamente.',
            'errors' => null,
        ], Response::HTTP_OK);
    }
}
