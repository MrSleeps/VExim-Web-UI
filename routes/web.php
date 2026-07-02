<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\AutodiscoverController;

Route::get('/autodiscover/autodiscover.xml', [AutodiscoverController::class, 'showAutodiscoverXml']);