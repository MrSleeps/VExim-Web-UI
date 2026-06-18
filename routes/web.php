<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\AutodiscoverController;

Route::get('/autodiscover/autodiscover.xml', [AutodiscoverController::class, 'showAutodiscoverXml']);

Route::get('/debug-dns-providers', function () {
    $discoveryService = app(\VEximUI\DnsCore\Services\DnsProviderDiscoveryService::class);
    
    return response()->json([
        'providers' => $discoveryService->debug(),
        'registered_plugins' => $discoveryService->getAllProviders()->map(function ($class) {
            return [
                'class' => $class,
                'type' => $class::getType(),
                'name' => $class::getName(),
            ];
        }),
    ]);
});