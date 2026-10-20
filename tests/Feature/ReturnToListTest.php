<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

/**
 * After an action on a list (delete, edit, add), people go back to the page, search and filters they were on,
 * and a page left empty by a delete becomes the last page that still has records.
 */
class ReturnToListTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    public function test_an_action_returns_to_the_same_page_and_filters(): void
    {
        $super = $this->superAdmin();
        $vehicles = collect(range(1, 12))->map(fn ($i) => $this->vehicle('Palawan', ['plate_number' => sprintf('PAL %04d', $i)]));
        $list = '/vehicles?page=2&per_page=5&location=Palawan';

        $this->actingAs($super)->from($list)->delete("/vehicles/{$vehicles[6]->id}")->assertRedirect($list);
        $this->actingAs($super)->from($list)->delete("/maintenance/999999")->assertNotFound();

        // An action sent from another page goes to the list itself, never to an outside address
        $this->actingAs($super)->from('https://example.com/vehicles?page=9')->delete("/vehicles/{$vehicles[7]->id}")->assertRedirect('/vehicles');
        $this->actingAs($super)->from('/dashboard')->delete("/vehicles/{$vehicles[8]->id}")->assertRedirect('/vehicles');
    }

    public function test_a_page_left_empty_goes_to_the_last_page_with_records(): void
    {
        $super = $this->superAdmin();
        collect(range(1, 6))->each(fn ($i) => $this->vehicle('Palawan', ['plate_number' => sprintf('PAL %04d', $i)]));

        // Six vehicles at five per page: page 3 does not exist, page 2 holds the last one
        $this->actingAs($super)->get('/vehicles?page=3&per_page=5')->assertRedirect('/vehicles?page=2&per_page=5');
        $this->actingAs($super)->get('/vehicles?page=2&per_page=5')->assertOk();
        $this->actingAs($super)->get('/vehicles?page=4&per_page=10')->assertRedirect('/vehicles?per_page=10');

        $admin = $this->admin('Palawan');
        $this->actingAs($admin)->get('/maintenance?page=5')->assertRedirect('/maintenance');
        $this->actingAs($admin)->get('/vehicle-requests?page=7')->assertRedirect('/vehicle-requests');
    }
}
