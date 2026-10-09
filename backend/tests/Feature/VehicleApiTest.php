<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VehicleApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'glinche.base_url' => 'https://api.test',
            'glinche.login_path' => '/login',
            'glinche.vehicles_path' => '/vehicles',
            'glinche.email' => 'test@example.com',
            'glinche.password' => 'secret',
            'glinche.vehicles_cache_ttl' => 0,
        ]);
    }

    public function test_it_returns_normalized_vehicles(): void
    {
        Http::fake([
            'api.test/login' => Http::response(['token' => 'abc']),
            'api.test/vehicles*' => Http::response(['data' => [[
                'id' => 1,
                'make' => 'Peugeot',
                'model' => '208',
                'fuel' => 'Essence',
                'price' => '12 990 €',
                'mileage' => 45000,
                'photos' => [['url' => 'https://img.test/1.jpg']],
            ]]]),
        ]);

        $this->getJson('/api/vehicles')
            ->assertOk()
            ->assertJsonPath('data.0.brand', 'Peugeot')
            ->assertJsonPath('data.0.energy', 'Essence')
            ->assertJsonPath('data.0.price', 12990)
            ->assertJsonPath('data.0.image', 'https://img.test/1.jpg');
    }

    public function test_it_returns_502_when_the_api_fails(): void
    {
        Http::fake(['api.test/*' => Http::response([], 500)]);

        $this->getJson('/api/vehicles')
            ->assertStatus(502)
            ->assertJsonStructure(['message']);
    }
}
