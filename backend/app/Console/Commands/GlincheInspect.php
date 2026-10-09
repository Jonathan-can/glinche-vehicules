<?php

namespace App\Console\Commands;

use App\Exceptions\GlincheApiException;
use App\Services\GlincheApiService;
use Illuminate\Console\Command;

class GlincheInspect extends Command
{
    protected $signature = 'glinche:inspect {--count=1 : Nombre de véhicules à afficher}';

    protected $description = "Affiche la réponse brute de l'API Glinche et sa version normalisée";

    public function handle(GlincheApiService $glinche): int
    {
        try {
            $raw = $glinche->rawVehicles();
        } catch (GlincheApiException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(count($raw).' véhicule(s) récupéré(s).');

        $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

        foreach (array_slice($raw, 0, (int) $this->option('count')) as $vehicle) {
            $this->newLine();
            $this->comment('--- Réponse brute ---');
            $this->line(json_encode($vehicle, $flags));
            $this->comment('--- Après normalisation ---');
            $this->line(json_encode($glinche->normalize($vehicle), $flags));
        }

        return self::SUCCESS;
    }
}
