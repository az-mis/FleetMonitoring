<?php

namespace Tests\Feature;

use App\Models\FleetNotification;
use App\Services\Notifications\NotificationFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

/** The bell keeps its notifications after the record is dealt with, and read/unread is kept per user */
class NotificationFeedTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    private function feed(): NotificationFeed
    {
        return app(NotificationFeed::class);
    }

    private function item(array $bell, string $title): array
    {
        $found = collect($bell['items'])->firstWhere('title', $title);
        $this->assertNotNull($found, "No \"{$title}\" notification in the bell");

        return $found;
    }

    public function test_a_completed_maintenance_stays_in_the_bell_and_opens_where_it_is(): void
    {
        $admin = $this->admin('Palawan');
        $job = $this->maintenance($this->vehicle('Palawan'), [
            'title' => 'Repair tire lugtype', 'status' => 'ongoing',
            'scheduled_date' => now()->subDays(5)->toDateString(), 'scheduled_date_end' => now()->subDays(2)->toDateString(),
        ]);

        // Any page load saves the alert
        $this->actingAs($admin)->get('/maintenance')->assertOk()->assertSee('Maintenance Due for Completion')->assertSee('Set Completed');
        $card = $this->item($this->feed()->forUser($admin, 'Palawan'), 'Maintenance Due for Completion');
        $this->assertFalse($card['read']);
        $this->assertStringContainsString('auto_edit=1', $this->feed()->targetUrl(FleetNotification::findOrFail($card['id'])));

        $job->update(['status' => 'completed', 'completed_date' => now()->toDateString()]);

        // Still there, now showing what happened
        $this->actingAs($admin)->get('/maintenance')->assertOk()->assertSee('Maintenance Due for Completion');
        $card = $this->item($this->feed()->forUser($admin, 'Palawan'), 'Maintenance Due for Completion');
        $this->assertSame('Completed', $card['badge']['text']);

        // Opening it marks it read and goes to the record, without opening the edit form
        $this->actingAs($admin)->get($card['open'])
            ->assertRedirect(route('maintenance.index', ['highlight_id' => $job->id]));
        $this->assertTrue($this->item($this->feed()->forUser($admin, 'Palawan'), 'Maintenance Due for Completion')['read']);
    }

    public function test_each_travel_request_gets_its_own_notification_that_follows_its_status(): void
    {
        $admin = $this->admin('Palawan');
        $request = $this->tripRequest($this->vehicle('Palawan'), ['requester_name' => 'Juan Dela Cruz', 'destination' => 'Calapan;;Naujan']);

        $this->feed()->sync('Palawan');
        $card = $this->item($this->feed()->forUser($admin, 'Palawan'), 'New Travel Request');
        $this->assertSame('Pending', $card['badge']['text']);
        $this->assertSame('Juan Dela Cruz → Calapan, Naujan', $card['lines'][0]);

        $request->update(['status' => 'approved']);
        $this->feed()->sync('Palawan');

        $card = $this->item($this->feed()->forUser($admin, 'Palawan'), 'New Travel Request');
        $this->assertSame('Approved', $card['badge']['text']);
        $this->actingAs($admin)->get($card['open'])
            ->assertRedirect(route('vehicle-requests.index', ['status' => 'approved', 'search' => $request->reference_code]));
        $this->assertSame(1, FleetNotification::count());
    }

    public function test_read_and_unread_are_kept_per_user_and_mark_all_read(): void
    {
        $first = $this->admin('Palawan');
        $second = $this->admin('Palawan');
        $vehicle = $this->vehicle('Palawan');
        $this->tripRequest($vehicle);
        $this->tripRequest($vehicle);
        $this->feed()->sync('Palawan');
        [$a, $b] = FleetNotification::orderBy('id')->get()->all();

        $this->actingAs($first)->postJson(route('notifications.read', $a))->assertOk();
        $this->assertSame(1, $this->feed()->forUser($first, 'Palawan')['unread']);
        $this->assertSame(2, $this->feed()->forUser($second, 'Palawan')['unread']);

        $this->actingAs($first)->deleteJson(route('notifications.unread', $a))->assertOk();
        $this->assertSame(2, $this->feed()->forUser($first, 'Palawan')['unread']);

        $this->actingAs($first)->postJson(route('notifications.read-all'))->assertOk()->assertJson(['marked' => 2]);
        $this->assertSame(0, $this->feed()->forUser($first, 'Palawan')['unread']);
        $this->assertSame(2, $this->feed()->forUser($second, 'Palawan')['unread']);
        $this->assertNotNull($b);
    }

    public function test_admins_only_see_and_open_their_own_areas_notifications(): void
    {
        $this->tripRequest($this->vehicle('Romblon'));
        $this->feed()->sync(null);
        $notification = FleetNotification::firstOrFail();

        $palawan = $this->admin('Palawan');
        $this->assertSame([], $this->feed()->forUser($palawan, 'Palawan')['items']);
        $this->actingAs($palawan)->get(route('notifications.open', $notification))->assertNotFound();
        $this->actingAs($palawan)->postJson(route('notifications.read', $notification))->assertNotFound();

        $this->assertCount(1, $this->feed()->forUser($this->superAdmin(), null)['items']);
        $this->actingAs($this->driver('Romblon'))->get(route('notifications.open', $notification))->assertForbidden();
    }

    public function test_an_oil_change_notification_shows_done_and_a_new_cycle_gets_a_new_one(): void
    {
        $admin = $this->admin('Palawan');
        $vehicle = $this->vehicle('Palawan', ['odometer' => 14000, 'last_change_oil_odometer' => 8000, 'change_oil_interval_km' => 5000]);

        $this->feed()->sync('Palawan');
        $this->assertSame('Overdue', $this->item($this->feed()->forUser($admin, 'Palawan'), 'Change Oil Due')['badge']['text']);

        $vehicle->update(['last_change_oil_odometer' => 14000]);
        $this->feed()->sync('Palawan');
        $card = $this->item($this->feed()->forUser($admin, 'Palawan'), 'Change Oil Due');
        $this->assertSame('Done', $card['badge']['text']);
        $this->assertSame('Oil changed at 14,000 km', $card['lines'][1]);

        $vehicle->update(['odometer' => 19500]);
        $this->feed()->sync('Palawan');
        $this->assertSame(2, FleetNotification::where('type', FleetNotification::OIL_CHANGE)->count());
    }

    public function test_old_notifications_are_cleaned_up_only_once_their_alert_is_over(): void
    {
        $vehicle = $this->vehicle('Palawan');
        $this->tripRequest($vehicle);
        $done = $this->tripRequest($vehicle);
        $this->feed()->sync('Palawan');
        $done->update(['status' => 'declined', 'decline_reason' => 'No vehicle']);
        FleetNotification::query()->update(['created_at' => now()->subDays(NotificationFeed::KEEP_DAYS + 5)]);
        Cache::flush(); // cleanup runs at most hourly

        $this->feed()->sync('Palawan');

        $this->assertSame(1, FleetNotification::count());
        $this->assertSame('travel:'.($done->id - 1), FleetNotification::first()->key);
    }

    public function test_read_marks_from_the_old_browser_storage_are_carried_over(): void
    {
        $admin = $this->admin('Palawan');
        $vehicle = $this->vehicle('Palawan', ['odometer' => 14000]);
        $due = $this->maintenance($vehicle, ['scheduled_date' => now()->subDay()->toDateString()]);
        $this->feed()->sync('Palawan');

        $this->actingAs($admin)->postJson(route('notifications.import-read'), ['ids' => ['maint_'.$due->id, 'oil_'.$vehicle->id, 'pending_travel']])
            ->assertOk()->assertJsonCount(2, 'read');
        $this->assertSame(0, $this->feed()->forUser($admin, 'Palawan')['unread']);
    }
}
