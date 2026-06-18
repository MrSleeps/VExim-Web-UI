<?php

use App\Http\Controllers\Api\V1\RspamdCheckController;
use App\Http\Controllers\Api\V1\RspamdUserSettingsController;
use App\Http\Controllers\Api\V1\RspamdMetadataController;
use Illuminate\Http\Request;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    
    Route::prefix('rspamd')->group(function () {
        Route::post('/check', [RspamdCheckController::class, 'check'])
            ->middleware('throttle:100,1');
            // Get all user settings (for periodic fetching)
            Route::get('/settings', [RspamdUserSettingsController::class, 'getAllSettings'])
                ->name('rspamd.settings.all');

            // Get single user settings (for real-time lookup or debugging)
            Route::get('/settings/{email}', [RspamdUserSettingsController::class, 'getUserSettings'])
                ->name('rspamd.settings.user');

            // Admin endpoint to clear cache (optional)
            Route::post('/cache/clear', [RspamdUserSettingsController::class, 'clearCache'])
                ->name('rspamd.cache.clear');
           Route::post('/metadata', [RspamdMetadataController::class, 'import']);
    });
});
