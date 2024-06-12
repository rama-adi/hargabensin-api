<?php

use App\Http\Controllers\API\V1\HistoricalFuelPriceController;
use App\Http\Controllers\API\V1\LatestFuelPriceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::prefix('/v1')->middleware('auth:sanctum')->group(function () {
    Route::prefix('/latest-prices')->group(function () {
        Route::get('/', [LatestFuelPriceController::class, 'latestPrices']);
        Route::get('/province/{province}', [LatestFuelPriceController::class, 'byProvinceLatest']);
        Route::get('/brand/{brand}', [LatestFuelPriceController::class, 'byBrandLatest']);
        Route::get('/product/{product}', [LatestFuelPriceController::class, 'byProductLatest']);
    });

    // Historical prices
    Route::prefix('/historical-prices')->group(function () {
        Route::get('/province/{province}', [HistoricalFuelPriceController::class, 'byProvinceHistorical']);
        Route::get('/brand/{brand}', [HistoricalFuelPriceController::class, 'byBrandHistorical']);
        Route::get('/product/{product}', [HistoricalFuelPriceController::class, 'byProductHistorical']);
    });

});
