<?php

namespace App\Http\Controllers;

use App\Exceptions\GlincheApiException;
use App\Services\GlincheApiService;
use Illuminate\Http\JsonResponse;

class VehicleController extends Controller
{
    public function index(GlincheApiService $glinche): JsonResponse
    {
        try {
            $vehicles = $glinche->vehicles();
        } catch (GlincheApiException $e) {
            // Le détail technique reste dans les logs, le front reçoit un message propre.
            report($e);

            return response()->json([
                'message' => 'Les véhicules sont momentanément indisponibles. Veuillez réessayer dans quelques instants.',
            ], 502);
        }

        return response()->json(['data' => $vehicles]);
    }
}
