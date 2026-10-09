<?php

namespace App\Services;

use App\Exceptions\GlincheApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Communique avec l'API partenaires Glinche (V1) :
 *  - authentification (token mis en cache, ou Basic)
 *  - récupération des véhicules (avec pagination éventuelle)
 *  - normalisation vers un format simple pour le front
 */
class GlincheApiService
{
    private const TOKEN_CACHE_KEY = 'glinche.token';
    private const VEHICLES_CACHE_KEY = 'glinche.vehicles';

    /**
     * Véhicules normalisés (mis en cache quelques minutes).
     *
     * @return array<int, array<string, mixed>>
     */
    public function vehicles(): array
    {
        $load = fn (): array => array_map(
            fn (array $vehicle): array => $this->normalize($vehicle),
            $this->rawVehicles()
        );

        $ttl = config('glinche.vehicles_cache_ttl');

        return $ttl > 0 ? Cache::remember(self::VEHICLES_CACHE_KEY, $ttl, $load) : $load();
    }

    /**
     * Véhicules tels que renvoyés par l'API (sans cache ni transformation).
     * Utilisé aussi par la commande `php artisan glinche:inspect`.
     *
     * @return array<int, array<string, mixed>>
     */
    public function rawVehicles(): array
    {
        $items = [];
        $page = 1;
        $maxPages = max(1, config('glinche.max_pages'));

        do {
            $payload = $this->get(config('glinche.vehicles_path'), ['page' => $page]);
            $items = array_merge($items, $this->extractList($payload));

            $lastPage = (int) ($this->pick($payload, ['meta.last_page', 'last_page', 'pagination.last_page']) ?? 1);
            $page++;
        } while ($page <= $lastPage && $page <= $maxPages);

        return $items;
    }

    /**
     * Transforme un véhicule de l'API en tableau simple et stable.
     * Chaque champ essaie plusieurs noms possibles : à ajuster une fois la
     * vraie réponse observée (php artisan glinche:inspect).
     */
    public function normalize(array $vehicle): array
    {
        return [
            'id' => $this->pick($vehicle, ['id', 'uuid', 'reference', 'vehicle_id']),
            'brand' => $this->text($this->pick($vehicle, ['brand', 'make', 'marque', 'brand_name', 'make_name'])),
            'model' => $this->text($this->pick($vehicle, ['model', 'modele', 'model_name'])),
            'version' => $this->text($this->pick($vehicle, ['version', 'trim', 'finition', 'version_name'])),
            'year' => $this->year($this->pick($vehicle, ['year', 'annee', 'first_registration_date', 'registration_date', 'first_registration'])),
            'mileage' => $this->number($this->pick($vehicle, ['mileage', 'kilometrage', 'km', 'odometer'])),
            'energy' => $this->text($this->pick($vehicle, ['energy', 'energie', 'fuel', 'fuel_type'])),
            'gearbox' => $this->text($this->pick($vehicle, ['gearbox', 'transmission', 'boite', 'boite_de_vitesse'])),
            'price' => $this->number($this->pick($vehicle, ['price', 'selling_price', 'sale_price', 'price_ttc', 'prix'])),
            'image' => $this->url($this->pick($vehicle, [
                'photo', 'image', 'picture', 'thumbnail', 'main_image', 'main_photo',
                'photos', 'images', 'pictures', 'medias', 'media',
            ])),
        ];
    }

    // ------------------------------------------------------------------
    // Appels HTTP
    // ------------------------------------------------------------------

    private function get(string $path, array $query = [], bool $canRetry = true): array
    {
        try {
            $response = $this->client()->get($path, $query);
        } catch (ConnectionException $e) {
            throw new GlincheApiException("L'API Glinche est injoignable.", 0, $e);
        }

        // Token expiré ou révoqué : on en redemande un et on réessaie une seule fois.
        if ($response->status() === 401 && $canRetry && config('glinche.auth_mode') === 'token') {
            Cache::forget(self::TOKEN_CACHE_KEY);

            return $this->get($path, $query, false);
        }

        if ($response->failed()) {
            throw new GlincheApiException("L'API Glinche a répondu avec le statut {$response->status()}.");
        }

        return $response->json() ?? [];
    }

    private function baseRequest(): PendingRequest
    {
        return Http::baseUrl(rtrim(config('glinche.base_url'), '/'))
            ->acceptJson()
            ->timeout(config('glinche.timeout'));
    }

    private function client(): PendingRequest
    {
        $this->assertCredentials();

        if (config('glinche.auth_mode') === 'basic') {
            return $this->baseRequest()->withBasicAuth(config('glinche.email'), config('glinche.password'));
        }

        return $this->baseRequest()->withToken($this->token());
    }

    private function token(): string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, config('glinche.token_ttl'), function (): string {
            try {
                $response = $this->baseRequest()->post(config('glinche.login_path'), [
                    config('glinche.login_email_field') => config('glinche.email'),
                    config('glinche.login_password_field') => config('glinche.password'),
                ]);
            } catch (ConnectionException $e) {
                throw new GlincheApiException("L'API Glinche est injoignable.", 0, $e);
            }

            if ($response->failed()) {
                throw new GlincheApiException("Authentification refusée par l'API Glinche (statut {$response->status()}).");
            }

            $token = $this->pick($response->json() ?? [], [
                'token', 'access_token', 'plainTextToken', 'data.token', 'data.access_token',
            ]);

            if (! is_string($token) || $token === '') {
                throw new GlincheApiException("Aucun token trouvé dans la réponse d'authentification.");
            }

            return $token;
        });
    }

    private function assertCredentials(): void
    {
        if (! config('glinche.email') || ! config('glinche.password')) {
            throw new GlincheApiException('Identifiants API manquants : renseignez GLINCHE_EMAIL et GLINCHE_PASSWORD dans le fichier .env.');
        }
    }

    // ------------------------------------------------------------------
    // Utilitaires de lecture / normalisation
    // ------------------------------------------------------------------

    /** Retrouve la liste de véhicules dans la réponse, quelle que soit son enveloppe. */
    private function extractList(array $payload): array
    {
        if (array_is_list($payload)) {
            return $payload;
        }

        foreach (['data', 'vehicles', 'items', 'results', 'data.data', 'data.vehicles'] as $key) {
            $value = data_get($payload, $key);

            if (is_array($value) && array_is_list($value)) {
                return $value;
            }
        }

        throw new GlincheApiException("Format de réponse inattendu : liste de véhicules introuvable.");
    }

    /** Premier chemin (notation pointée) qui contient une valeur non vide. */
    private function pick(array $data, array $keys): mixed
    {
        foreach ($keys as $key) {
            $value = data_get($data, $key);

            if ($value !== null && $value !== '' && $value !== []) {
                return $value;
            }
        }

        return null;
    }

    private function text(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = $this->pick($value, ['name', 'label', 'title', 'value']);
        }

        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function number(mixed $value): int|float|null
    {
        if (is_array($value)) {
            $value = $this->pick($value, ['amount', 'value', 'total']);
        }

        if (is_string($value)) {
            $value = str_replace(',', '.', preg_replace('/[^\d.,]/', '', $value));
        }

        return is_numeric($value) ? $value + 0 : null;
    }

    private function year(mixed $value): ?int
    {
        if (is_int($value) || (is_string($value) && preg_match('/\b(19|20)\d{2}\b/', $value, $m))) {
            return is_int($value) ? $value : (int) $m[0];
        }

        return null;
    }

    private function url(mixed $value): ?string
    {
        if (is_string($value)) {
            return $value !== '' ? $value : null;
        }

        if (is_array($value)) {
            foreach (['url', 'src', 'path', 'large', 'medium', 'original'] as $key) {
                if (isset($value[$key])) {
                    return $this->url($value[$key]);
                }
            }

            $first = reset($value);

            return $first === false ? null : $this->url($first);
        }

        return null;
    }
}
