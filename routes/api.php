<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\ProfileController; // Puede estar o no, no es crítico ahora

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// ESTA LÍNEA es la más importante para que /api/register funcione
require __DIR__.'/auth.php';

// Si tenías otras rutas API, también deberían estar aquí