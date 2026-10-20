<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

/** A plate searched with or without its space or dash ("SNE6227", "SNE 6227", "sne-6227") finds the vehicle */
class PlateSearchTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    public function test_plate_search_ignores_spaces_and_dashes(): void
    {
        $super = $this->superAdmin();
        $hilux = $this->vehicle('Palawan', ['plate_number' => 'SNE 6227', 'brand' => 'Toyota', 'model' => 'Hilux', 'color' => 'White']);
        $this->vehicle('Palawan', ['plate_number' => 'SKP 906', 'brand' => 'Ford', 'model' => 'Everest', 'color' => 'Black']);
        $this->vehicle('Palawan', ['plate_number' => '135209-ZML209', 'brand' => 'Isuzu', 'model' => 'Crosswind']);

        foreach (['SNE6227', 'SNE 6227', 'sne-6227', ' sne6227 ', '6227'] as $search) {
            $this->actingAs($super)->get('/vehicles?search='.urlencode($search))->assertOk()
                ->assertSee('SNE 6227')->assertDontSee('SKP 906');
        }
        $this->actingAs($super)->get('/vehicles?search=ZML209')->assertOk()->assertSee('135209-ZML209');
        // Words are still matched anywhere on the vehicle
        $this->actingAs($super)->get('/vehicles?search='.urlencode('toyota white'))->assertOk()->assertSee('SNE 6227')->assertDontSee('SKP 906');

        $this->maintenance($hilux, ['title' => 'Brake pad replacement']);
        $this->actingAs($super)->get('/maintenance?status=all&search=SNE6227')->assertOk()->assertSee('Brake pad replacement');

        $this->tripRequest($hilux, ['destination' => 'Irawan Station']);
        $this->actingAs($super)->get('/vehicle-requests?search=sne-6227')->assertOk()->assertSee('Irawan Station');
        $this->actingAs($super)->get('/vehicle-assigning?search=SNE6227')->assertOk()->assertSee('SNE 6227')->assertDontSee('SKP 906');
    }
}
