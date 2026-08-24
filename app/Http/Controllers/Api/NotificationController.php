<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::forUser($request->user()->id)
            ->when($request->unread_only, fn($q) => $q->unread())
            ->when($request->type, fn($q, $type) => $q->byType($type))
            ->recent()
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'data' => $notifications->map(fn($n) => [
                'id' => $n->id,
                'title' => $n->title,
                'message' => $n->message,
                'type' => $n->type,
                'is_read' => $n->is_read,
                'action' => $n->action,
                'created_at' => $n->created_at->format('Y-m-d H:i:s'),
                'read_at' => $n->read_at?->format('Y-m-d H:i:s'),
            ]),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'total' => $notifications->total(),
                'unread_count' => Notification::forUser($request->user()->id)->unread()->count(),
            ],
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = Notification::forUser($request->user()->id)->unread()->count();

        return response()->json([
            'unread_count' => $count,
        ]);
    }

    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        $this->authorize('view', $notification);

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notifikasi ditandai sebagai dibaca',
            'data' => [
                'id' => $notification->id,
                'is_read' => $notification->is_read,
                'read_at' => $notification->read_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    public function markAsUnread(Request $request, Notification $notification): JsonResponse
    {
        $this->authorize('view', $notification);

        $notification->markAsUnread();

        return response()->json([
            'message' => 'Notifikasi ditandai sebagai belum dibaca',
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        Notification::forUser($request->user()->id)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'message' => 'Semua notifikasi ditandai sebagai dibaca',
        ]);
    }

    public function delete(Request $request, Notification $notification): JsonResponse
    {
        $this->authorize('delete', $notification);

        $notification->delete();

        return response()->json([
            'message' => 'Notifikasi dihapus',
        ]);
    }

    public function deleteAll(Request $request): JsonResponse
    {
        Notification::forUser($request->user()->id)->delete();

        return response()->json([
            'message' => 'Semua notifikasi dihapus',
        ]);
    }
}
