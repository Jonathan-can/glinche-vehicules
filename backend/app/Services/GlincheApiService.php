<?php

namespace App\Services;

use App\Exceptions\GlincheApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Communique avec l'API partenaires Glinche :
 *  - authentification par token (mis en cache)
 *  - récupération des véhicules
 *  - normalisation vers un format simple pour le front
 */
class GlincheApiService
{
    private const TOKEN_CACHE_KEY = 'glinche.token';
    private const VEHICLES_CACHE_KEY = 'glinche.vehicles';

    // L'API renvoie des codes : on les traduit en libellés lisibles.
    private const ENERGY_LABELS = ['ES' => 'Essence', 'GO' => 'Diesel', 'EH' => 'Hybride', 'EL' => 'Électrique'];
    private const GEARBOX_LABELS = ['AUT' => 'Automatique', 'MAN' => 'Manuelle'];

    /**
     * Véhicules normalisés (mis en cache quelques minutes).
     *
     * @return array<int, array<string, mixed>>
     */
    public function vehicles(): array
    {
        $load = fn (): array => array_map(
            fn (array $item): array => $this->normalize($item),
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
        $payload = $this->get(config('glinche.vehicles_path'));
        $vehicles = $payload['data'] ?? $payload;

        if (! is_array($vehicles) || ! array_is_list($vehicles)) {
            throw new GlincheApiException('Format de réponse inattendu : liste de véhicules introuvable.');
        }

        return $vehicles;
    }

    /**
     * Transforme un véhicule de l'API en tableau simple et stable.
     * L'essentiel des informations se trouve dans le sous-objet "vehicle".
     */
    public function normalize(array $item): array
    {
        $vehicle = $item['vehicle'] ?? [];

        return [
            'id' => $item['reference'] ?? null,
            'brand' => $vehicle['manufacturer'] ?? null,
            'model' => $vehicle['model'] ?? null,
            'version' => $vehicle['finish'] ?? null,
            'year' => $vehicle['year'] ?? null,
            'mileage' => $vehicle['mileage'] ?? null,
            'energy' => $this->label(self::ENERGY_LABELS, $vehicle['energy'] ?? null),
            'gearbox' => $this->label(self::GEARBOX_LABELS, $vehicle['gearbox'] ?? null),
            'price' => $this->price($vehicle['prices']['merchantPrice'] ?? null),
            'image' => $this->mainPicture($item['pictures'] ?? []),
        ];
    }

    // ------------------------------------------------------------------
    // Appels HTTP
    // ------------------------------------------------------------------

    private function get(string $path, bool $canRetry = true): array
    {
        try {
            $response = $this->baseRequest()->withToken($this->token())->get($path);
        } catch (ConnectionException $e) {
            throw new GlincheApiException("L'API Glinche est injoignable.", 0, $e);
        }

        // Token expiré ou révoqué : on en redemande un et on réessaie une seule fois.
        if ($response->status() === 401 && $canRetry) {
            Cache::forget(self::TOKEN_CACHE_KEY);

            return $this->get($path, false);
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

    private function token(): string
    {
        if (! config('glinche.email') || ! config('glinche.password')) {
            throw new GlincheApiException('Identifiants API manquants : renseignez GLINCHE_EMAIL et GLINCHE_PASSWORD dans le fichier .env.');
        }

        return Cache::remember(self::TOKEN_CACHE_KEY, config('glinche.token_ttl'), function (): string {
            try {
                $response = $this->baseRequest()->post(config('glinche.login_path'), [
                    'email' => config('glinche.email'),
                    'password' => config('glinche.password'),
                ]);
            } catch (ConnectionException $e) {
                throw new GlincheApiException("L'API Glinche est injoignable.", 0, $e);
            }

            if ($response->failed()) {
                throw new GlincheApiException("Authentification refusée par l'API Glinche (statut {$response->status()}).");
            }

            $token = $response->json('token');

            if (! is_string($token) || $token === '') {
                throw new GlincheApiException("Aucun token trouvé dans la réponse d'authentification.");
            }

            return $token;
        });
    }

    // ------------------------------------------------------------------
    // Normalisation
    // ------------------------------------------------------------------

    /** Libellé correspondant à un code ; un code inconnu est renvoyé tel quel. */
    private function label(array $labels, ?string $code): ?string
    {
        return $code === null ? null : ($labels[strtoupper($code)] ?? $code);
    }

    /** L'API envoie le prix sous forme de texte ("26700.00"). */
    private function price(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    /** URL de la photo principale (type MAIN), sinon de la première photo. */
    private function mainPicture(array $pictures): ?string
    {
        $main = collect($pictures)->firstWhere('type', 'MAIN') ?? ($pictures[0] ?? null);

        return $main['url'] ?? null;
    }
}
