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
        // Même structure que la vraie réponse de l'API (réduite aux champs utilisés).
        Http::fake([
            'api.test/login' => Http::response(['token' => 'abc']),
            'api.test/vehicles' => Http::response([[
                'reference' => 'GLI00281653',
                'pictures' => [
                    ['type' => 'IMAGE', 'url' => 'https://img.test/2.jpg'],
                    ['type' => 'MAIN', 'url' => 'https://img.test/1.jpg'],
                ],
                'vehicle' => [
                    'manufacturer' => 'Peugeot',
                    'model' => '308',
                    'finish' => 'II 1.2 PureTech 110ch Active',
                    'year' => 2018,
                    'mileage' => 46410,
                    'energy' => 'ES',
                    'gearbox' => 'MAN',
                    'prices' => ['merchantPrice' => '12900.00', 'catalogPrice' => '26500.00'],
                ],
            ]]),
        ]);

        $this->getJson('/api/vehicles')
            ->assertOk()
            ->assertExactJson(['data' => [[
                'id' => 'GLI00281653',
                'brand' => 'Peugeot',
                'model' => '308',
                'version' => 'II 1.2 PureTech 110ch Active',
                'year' => 2018,
                'mileage' => 46410,
                'energy' => 'Essence',
                'gearbox' => 'Manuelle',
                'price' => 12900,
                'image' => 'https://img.test/1.jpg',
            ]]]);

        Http::assertSent(fn ($request) => $request->url() === 'https://api.test/vehicles'
            && $request->hasHeader('Authorization', 'Bearer abc'));
    }

    public function test_missing_fields_become_null(): void
    {
        Http::fake([
            'api.test/login' => Http::response(['token' => 'abc']),
            'api.test/vehicles' => Http::response([[
                'reference' => 'GLI1',
                'pictures' => [],
                'vehicle' => ['manufacturer' => 'Iveco', 'energy' => 'XX'],
            ]]),
        ]);

        $this->getJson('/api/vehicles')
            ->assertOk()
            ->assertJsonPath('data.0.brand', 'Iveco')
            ->assertJsonPath('data.0.year', null)
            ->assertJsonPath('data.0.price', null)
            ->assertJsonPath('data.0.image', null)
            ->assertJsonPath('data.0.energy', 'XX'); // code inconnu : affiché tel quel
    }

    public function test_it_returns_502_when_the_api_fails(): void
    {
        Http::fake(['api.test/*' => Http::response([], 500)]);

        $this->getJson('/api/vehicles')
            ->assertStatus(502)
            ->assertJsonStructure(['message']);
    }
}
