<?php

use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

// GET /api/vehicles
Route::get('/vehicles', [VehicleController::class, 'index']);
