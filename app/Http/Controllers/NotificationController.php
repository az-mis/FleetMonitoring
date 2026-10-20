<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesFleetAccess;
use App\Models\FleetNotification;
use App\Models\User;
use App\Services\Notifications\NotificationFeed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** The bell: open a notification, mark it read or unread, mark all read */
class NotificationController extends Controller
{
    use AuthorizesFleetAccess;

    public function __construct(private readonly NotificationFeed $feed) {}

    /** Marks the notification read and opens the record it is about, wherever it is now */
    public function open(FleetNotification $notification): RedirectResponse
    {
        $user = $this->authorizeNotification($notification);
        $this->feed->markRead($user, $notification);

        return redirect()->to($this->feed->targetUrl($notification));
    }

    public function read(FleetNotification $notification): JsonResponse
    {
        $this->feed->markRead($this->authorizeNotification($notification), $notification);

        return response()->json(['ok' => true]);
    }

    public function unread(FleetNotification $notification): JsonResponse
    {
        $this->feed->markUnread($this->authorizeNotification($notification), $notification);

        return response()->json(['ok' => true]);
    }

    public function readAll(): JsonResponse
    {
        $user = $this->authorizeFleetAdmin();

        return response()->json(['marked' => $this->feed->markAllRead($user, $this->fleetLocationScope($user))]);
    }

    /** What this browser marked read before read/unread was kept on the server */
    public function importRead(Request $request): JsonResponse
    {
        $user = $this->authorizeFleetAdmin();
        $data = $request->validate(['ids' => ['present', 'array', 'max:1000'], 'ids.*' => ['string', 'max:60']]);

        return response()->json(['read' => $this->feed->importRead($user, $this->fleetLocationScope($user), $data['ids'])]);
    }

    private function authorizeNotification(FleetNotification $notification): User
    {
        $user = $this->authorizeFleetAdmin();
        abort_unless($this->feed->inScope($notification, $this->fleetLocationScope($user)), 404);

        return $user;
    }
}
