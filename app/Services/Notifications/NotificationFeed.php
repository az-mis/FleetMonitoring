<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\FleetNotification;
use App\Models\FleetNotificationRead;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Maintenance\Models\VehicleMaintenance;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRequest\Models\VehicleRequest;

/**
 * The bell's notifications. An alert (maintenance due, maintenance due for completion, oil change due, a new travel
 * request) is saved the first time it is seen, so it stays in the bell after the record is dealt with; each card
 * then shows the record's current state. Notifications belong to an area; read/unread is kept per user.
 */
final class NotificationFeed
{
    /** Notifications older than this are removed once their alert is over */
    public const KEEP_DAYS = 90;

    /** How many notifications the bell lists */
    public const SHOW = 50;

    /** How often page loads look for new alerts, per area */
    public const SYNC_EVERY_SECONDS = 60;

    /** sync(), at most once a minute per area: the bell is on every admin page, the alerts change slowly */
    public function syncIfDue(?string $location): void
    {
        if (Cache::add('fleet-notifications-synced:'.($location ?? 'all'), true, self::SYNC_EVERY_SECONDS)) {
            $this->sync($location);
        }
    }

    /** Saves every alert that has appeared since the last look, for one area (null = all areas) */
    public function sync(?string $location): void
    {
        $alerts = $this->currentAlerts($location);

        $known = FleetNotification::query()->whereIn('key', array_keys($alerts))->pluck('key')->all();
        $now = now();
        $rows = [];
        foreach (array_diff_key($alerts, array_flip($known)) as $key => $alert) {
            $rows[] = ['key' => $key, ...$alert, 'data' => json_encode($alert['data']), 'created_at' => $now, 'updated_at' => $now];
        }
        foreach (array_chunk($rows, 200) as $chunk) {
            // insertOrIgnore: two admins loading pages at once must not create the same notification twice
            FleetNotification::query()->insertOrIgnore($chunk);
        }

        // Clean up at most hourly per area: old notifications whose alert is over
        if (Cache::add('fleet-notifications-pruned:'.($location ?? 'all'), true, 3600)) {
            FleetNotification::query()
                ->when($location, fn ($q) => $q->where('location', $location))
                ->where('created_at', '<', now()->subDays(self::KEEP_DAYS))
                ->whereNotIn('key', array_keys($alerts) ?: [''])
                ->delete();
        }
    }

    /**
     * The bell for one user: the newest notifications (unread first), each with its record's current state
     *
     * @return array{items: list<array<string, mixed>>, unread: int}
     */
    public function forUser(User $user, ?string $location): array
    {
        $notifications = $this->scoped($location)
            ->select('fleet_notifications.*')
            ->selectRaw('r.read_at as read_at')
            ->leftJoin('fleet_notification_reads as r', fn ($join) => $join
                ->on('r.fleet_notification_id', '=', 'fleet_notifications.id')
                ->where('r.user_id', $user->id))
            ->orderByRaw('CASE WHEN r.read_at IS NULL THEN 0 ELSE 1 END')
            ->orderByDesc('occurred_at')
            ->orderByDesc('fleet_notifications.id')
            ->limit(self::SHOW)
            ->get();

        $subjects = $this->subjects($notifications);

        return [
            'items' => $notifications->map(fn (FleetNotification $n) => $this->present($n, $subjects))->values()->all(),
            'unread' => $this->unreadQuery($user, $location)->count(),
        ];
    }

    /** Where clicking the notification goes, worked out from the record as it is now */
    public function targetUrl(FleetNotification $notification): string
    {
        return $this->present($notification, $this->subjects(collect([$notification])))['target'];
    }

    public function markRead(User $user, FleetNotification $notification): void
    {
        FleetNotificationRead::query()->firstOrCreate(
            ['fleet_notification_id' => $notification->id, 'user_id' => $user->id],
            ['read_at' => now()],
        );
    }

    public function markUnread(User $user, FleetNotification $notification): void
    {
        FleetNotificationRead::query()->where('fleet_notification_id', $notification->id)->where('user_id', $user->id)->delete();
    }

    /** @return int how many were marked */
    public function markAllRead(User $user, ?string $location): int
    {
        $ids = $this->unreadQuery($user, $location)->pluck('fleet_notifications.id');
        $now = now();
        FleetNotificationRead::query()->insertOrIgnore($ids->map(fn ($id) => [
            'fleet_notification_id' => $id, 'user_id' => $user->id, 'read_at' => $now,
        ])->all());

        return $ids->count();
    }

    /**
     * Carries over what this browser had marked read before notifications were saved on the server
     * (old ids such as "maint_12", "maint_ong_12", "oil_5")
     *
     * @param  list<string>  $oldIds
     * @return list<int> the notifications now marked read
     */
    public function importRead(User $user, ?string $location, array $oldIds): array
    {
        $keys = [];
        $oilVehicles = [];
        foreach ($oldIds as $old) {
            if (preg_match('/^maint_ong_(\d+)$/', $old, $m)) {
                $keys[] = FleetNotification::MAINTENANCE_COMPLETION.':'.$m[1];
            } elseif (preg_match('/^maint_(\d+)$/', $old, $m)) {
                $keys[] = FleetNotification::MAINTENANCE_DUE.':'.$m[1];
            } elseif (preg_match('/^oil_(\d+)$/', $old, $m)) {
                $oilVehicles[] = (int) $m[1];
            }
        }

        $ids = $this->scoped($location)
            ->where(fn ($q) => $q->whereIn('key', $keys ?: [''])
                ->orWhere(fn ($oil) => $oil->where('type', FleetNotification::OIL_CHANGE)->whereIn('subject_id', $oilVehicles ?: [0])))
            ->pluck('id');
        $now = now();
        FleetNotificationRead::query()->insertOrIgnore($ids->map(fn ($id) => [
            'fleet_notification_id' => $id, 'user_id' => $user->id, 'read_at' => $now,
        ])->all());

        return $ids->map(fn ($id) => (int) $id)->all();
    }

    /** Whether this notification belongs to the area (null = all areas) */
    public function inScope(FleetNotification $notification, ?string $location): bool
    {
        return $location === null || $notification->location === $location;
    }

    private function scoped(?string $location): Builder
    {
        return FleetNotification::query()->when($location, fn ($q) => $q->where('fleet_notifications.location', $location));
    }

    private function unreadQuery(User $user, ?string $location): Builder
    {
        return $this->scoped($location)->whereNotExists(fn ($q) => $q->selectRaw('1')
            ->from('fleet_notification_reads')
            ->whereColumn('fleet_notification_reads.fleet_notification_id', 'fleet_notifications.id')
            ->where('fleet_notification_reads.user_id', $user->id));
    }

    /**
     * The alerts that hold right now, keyed by notification key
     *
     * @return array<string, array<string, mixed>>
     */
    private function currentAlerts(?string $location): array
    {
        $alerts = [];
        $today = today();

        // Scheduled maintenance whose start date has come: prompt to set it Ongoing
        VehicleMaintenance::query()
            ->where('status', 'scheduled')
            ->whereDate('scheduled_date', '<=', $today->toDateString())
            ->when($location, fn ($q) => $q->where('location', $location))
            ->get()
            ->each(function (VehicleMaintenance $m) use (&$alerts) {
                $alerts[FleetNotification::MAINTENANCE_DUE.':'.$m->id] = $this->alert(
                    FleetNotification::MAINTENANCE_DUE, $m->id, $m->location,
                    $m->scheduled_date?->copy()->startOfDay() ?? now(),
                    $this->maintenanceSnapshot($m),
                );
            });

        // Ongoing maintenance past its end date (or past its start date when it has no end): prompt to set it Completed
        VehicleMaintenance::query()
            ->where('status', 'ongoing')
            ->where(fn ($q) => $q
                ->where(fn ($sq) => $sq->whereNotNull('scheduled_date_end')->whereDate('scheduled_date_end', '<', $today->toDateString()))
                ->orWhere(fn ($sq) => $sq->whereNull('scheduled_date_end')->whereDate('scheduled_date', '<', $today->toDateString())))
            ->when($location, fn ($q) => $q->where('location', $location))
            ->get()
            ->each(function (VehicleMaintenance $m) use (&$alerts) {
                $end = $m->scheduled_date_end ?? $m->scheduled_date;
                $alerts[FleetNotification::MAINTENANCE_COMPLETION.':'.$m->id] = $this->alert(
                    FleetNotification::MAINTENANCE_COMPLETION, $m->id, $m->location,
                    $end ? $end->copy()->addDay()->startOfDay() : now(),
                    $this->maintenanceSnapshot($m),
                );
            });

        // Oil change due soon or overdue: one notification per oil cycle (a new cycle after the next oil change)
        Vehicle::query()
            ->select(['id', 'plate_number', 'brand', 'model', 'location', 'odometer', 'last_change_oil_odometer', 'change_oil_interval_km'])
            ->when($location, fn ($q) => $q->where('location', $location))
            ->get()
            ->filter(fn (Vehicle $v) => in_array($v->change_oil_status['status'], ['overdue', 'due_soon'], true))
            ->each(function (Vehicle $v) use (&$alerts) {
                $cycle = (int) ($v->last_change_oil_odometer ?? 0);
                $alerts[FleetNotification::OIL_CHANGE.':'.$v->id.':'.$cycle] = $this->alert(
                    FleetNotification::OIL_CHANGE, $v->id, $v->location, now(),
                    ['plate' => $v->plate_number, 'vehicle' => trim($v->brand.' '.$v->model), 'cycle' => $cycle],
                );
            });

        // Each travel request waiting for approval
        VehicleRequest::query()
            ->where('status', 'pending')
            ->when($location, fn ($q) => $q->where('location', $location))
            ->get()
            ->each(function (VehicleRequest $r) use (&$alerts) {
                $alerts[FleetNotification::TRAVEL_REQUEST.':'.$r->id] = $this->alert(
                    FleetNotification::TRAVEL_REQUEST, $r->id, $r->location, $r->created_at ?? now(),
                    [
                        'reference' => $r->reference_code,
                        'requester' => $r->requester_name,
                        'destination' => $this->destination($r->destination),
                        'travel_date' => $r->travel_date?->toDateString(),
                    ],
                );
            });

        return $alerts;
    }

    private function alert(string $type, int $subjectId, ?string $location, CarbonInterface $occurredAt, array $data): array
    {
        return [
            'type' => $type,
            'location' => $location,
            'subject_id' => $subjectId,
            'data' => $data,
            'occurred_at' => $occurredAt,
        ];
    }

    private function maintenanceSnapshot(VehicleMaintenance $m): array
    {
        return [
            'vehicle' => $m->vehicle_label ?: $m->plate_number,
            'title' => $m->title,
            'scheduled' => $m->scheduled_date?->toDateString(),
            'scheduled_end' => $m->scheduled_date_end?->toDateString(),
        ];
    }

    /**
     * The records the notifications are about, as they are now
     *
     * @return array{maintenance: Collection, vehicles: Collection, requests: Collection, activeMaintenance: Collection}
     */
    private function subjects(Collection $notifications): array
    {
        $ids = fn (string ...$types) => $notifications->whereIn('type', $types)->pluck('subject_id')->unique()->values();
        $vehicleIds = $ids(FleetNotification::OIL_CHANGE);

        return [
            'maintenance' => VehicleMaintenance::query()->whereIn('id', $ids(FleetNotification::MAINTENANCE_DUE, FleetNotification::MAINTENANCE_COMPLETION))->get()->keyBy('id'),
            'vehicles' => Vehicle::query()->whereIn('id', $vehicleIds)->get()->keyBy('id'),
            'requests' => VehicleRequest::query()->whereIn('id', $ids(FleetNotification::TRAVEL_REQUEST))->get()->keyBy('id'),
            // The scheduled or ongoing maintenance of each oil-due vehicle, if one was created
            'activeMaintenance' => VehicleMaintenance::query()->whereIn('vehicle_id', $vehicleIds)
                ->whereIn('status', ['scheduled', 'ongoing'])->latest('scheduled_date')->get()->unique('vehicle_id')->keyBy('vehicle_id'),
        ];
    }

    /** One bell card: title, current-state badge, lines, time and where it leads */
    private function present(FleetNotification $n, array $subjects): array
    {
        $data = $n->data ?? [];
        $card = match ($n->type) {
            FleetNotification::MAINTENANCE_DUE, FleetNotification::MAINTENANCE_COMPLETION => $this->maintenanceCard($n, $subjects['maintenance']->get($n->subject_id), $data),
            FleetNotification::OIL_CHANGE => $this->oilCard($n, $subjects['vehicles']->get($n->subject_id), $subjects['activeMaintenance']->get($n->subject_id), $data),
            default => $this->travelCard($subjects['requests']->get($n->subject_id), $data),
        };

        return $card + [
            'id' => $n->id,
            'read' => $n->getAttribute('read_at') !== null,
            'ago' => $n->occurred_at->diffForHumans(['short' => true]),
            'open' => route('notifications.open', $n),
        ];
    }

    private function maintenanceCard(FleetNotification $n, ?VehicleMaintenance $m, array $data): array
    {
        $isCompletion = $n->type === FleetNotification::MAINTENANCE_COMPLETION;
        $status = $m?->status;
        $dueToday = ! $isCompletion && $status === 'scheduled' && $m->scheduled_date?->isToday();
        // Still waiting for the step the notification asked for: open the edit form straight away
        $awaiting = $isCompletion ? $status === 'ongoing' : $status === 'scheduled';

        $badge = match (true) {
            $m === null => ['Deleted', 'slate'],
            $status === 'completed' => ['Completed', 'emerald'],
            $status === 'cancelled' => ['Cancelled', 'slate'],
            $isCompletion && $status === 'ongoing' => ['Set Completed', 'emerald'],
            $status === 'ongoing' => ['Ongoing', 'amber'],
            $dueToday => ['Set Ongoing', 'blue'],
            default => ['Scheduled', 'blue'],
        };
        $dateLabel = $isCompletion ? 'Scheduled end' : 'Scheduled';
        $date = $isCompletion
            ? ($m?->scheduled_date_end ?? $m?->scheduled_date ?? $this->date($data['scheduled_end'] ?? $data['scheduled'] ?? null))
            : ($m?->scheduled_date ?? $this->date($data['scheduled'] ?? null));

        return [
            'icon' => $isCompletion ? 'check' : 'wrench',
            'accent' => $isCompletion ? 'emerald' : 'blue',
            'title' => $isCompletion ? 'Maintenance Due for Completion' : ($dueToday ? 'Maintenance Due Today' : 'Maintenance Scheduled'),
            'badge' => ['text' => $badge[0], 'tone' => $badge[1]],
            'lines' => [
                ($m ? ($m->vehicle_label ?: $m->plate_number) : ($data['vehicle'] ?? '')).' — '.($m?->title ?? $data['title'] ?? ''),
                $date ? $dateLabel.': '.$date->format('M d, Y') : null,
            ],
            'target' => $m
                ? route('maintenance.index', array_filter(['highlight_id' => $m->id, 'auto_edit' => $awaiting && ($isCompletion || $dueToday) ? 1 : null]))
                : route('maintenance.index', ['status' => 'all']),
        ];
    }

    private function oilCard(FleetNotification $n, ?Vehicle $v, ?VehicleMaintenance $active, array $data): array
    {
        $cycle = (int) ($data['cycle'] ?? 0);
        $state = $v?->change_oil_status;
        $stillDue = $v && (int) ($v->last_change_oil_odometer ?? 0) === $cycle && in_array($state['status'], ['overdue', 'due_soon'], true);

        $badge = match (true) {
            $v === null => ['Deleted', 'slate'],
            ! $stillDue => ['Done', 'emerald'],
            $active !== null => ['Scheduled', 'emerald'],
            $state['status'] === 'overdue' => ['Overdue', 'red'],
            default => ['Due Soon', 'amber'],
        };
        $plate = $v?->plate_number ?? ($data['plate'] ?? '');

        return [
            'icon' => 'oil',
            'accent' => 'amber',
            'title' => 'Change Oil Due',
            'badge' => ['text' => $badge[0], 'tone' => $badge[1]],
            'lines' => [
                trim($plate.' — '.($v ? trim($v->brand.' '.$v->model) : ($data['vehicle'] ?? '')), ' —'),
                $v === null ? null : ($stillDue
                    ? ($active ? 'Maintenance created — view it in the list' : $state['label'])
                    : 'Oil changed at '.number_format((int) $v->last_change_oil_odometer).' km'),
            ],
            'target' => match (true) {
                $v === null => route('maintenance.index'),
                $stillDue && $active !== null => route('maintenance.index', ['highlight_id' => $active->id]),
                $stillDue => route('maintenance.index', ['schedule_vehicle_id' => $v->id, 'type' => 'Oil Change']),
                default => route('maintenance.index', ['status' => 'all', 'search' => $v->plate_number]),
            },
        ];
    }

    private function travelCard(?VehicleRequest $r, array $data): array
    {
        $status = $r?->status;
        $badge = match ($status) {
            null => ['Deleted', 'slate'],
            'pending' => ['Pending', 'amber'],
            'approved' => ['Approved', 'emerald'],
            'declined' => ['Declined', 'red'],
            default => [ucfirst((string) $status), 'slate'],
        };
        $date = $r?->travel_date ?? $this->date($data['travel_date'] ?? null);
        $reference = $r?->reference_code ?? ($data['reference'] ?? null);

        return [
            'icon' => 'travel',
            'accent' => 'amber',
            'title' => 'New Travel Request',
            'badge' => ['text' => $badge[0], 'tone' => $badge[1]],
            'lines' => [
                trim(($r?->requester_name ?? $data['requester'] ?? '').' → '.($r ? $this->destination($r->destination) : ($data['destination'] ?? '')), ' →'),
                $date ? 'Travel: '.$date->format('M d, Y') : null,
            ],
            // The request's own status tab, searched by its reference code, finds exactly this request
            'target' => route('vehicle-requests.index', array_filter([
                'status' => in_array($status, ['pending', 'approved', 'declined'], true) ? $status : 'all',
                'search' => $reference,
            ])),
        ];
    }

    private function date(?string $value): ?CarbonInterface
    {
        return $value ? Carbon::parse($value) : null;
    }

    /** Multi-stop destinations are stored "A;;B" */
    private function destination(?string $destination): string
    {
        return implode(', ', array_filter(array_map('trim', explode(';;', (string) $destination))));
    }
}
