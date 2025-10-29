<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FreightImportController;

Route::prefix('freight')->group(function () {
    Route::post('/import', [FreightImportController::class, 'import'])
        ->name('freight.import');

    Route::get('/import/{freightTableId}/status', [FreightImportController::class, 'status'])
        ->name('freight.import.status')
        ->where('freightTableId', '[0-9]+');

    Route::get('/imports', [FreightImportController::class, 'list'])
        ->name('freight.imports.list');
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'version' => config('app.version', '1.0.0'),
        'environment' => config('app.env'),
    ]);
})->name('health');
