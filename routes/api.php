<?php

use App\Http\Controllers\Api\ProjectApiController;
use Illuminate\Support\Facades\Route;

// API JSON para n8n / integrações. Header: X-Api-Key: <VIX_API_KEY>
Route::middleware('api.key')->group(function () {
    Route::get('/projetos', [ProjectApiController::class, 'index']);
    Route::get('/projetos/{project}', [ProjectApiController::class, 'show']);
});
