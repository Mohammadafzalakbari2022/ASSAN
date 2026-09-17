<?php

use App\Http\Controllers\Delivery\AuthController;
use App\Http\Controllers\Delivery\LocationController;
use App\Http\Controllers\Delivery\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Delivery app routes (delivery staff only)
|--------------------------------------------------------------------------
|
| A separate, phone-first area at /delivery with its own sign-in. Only active
| accounts with the "delivery" role can reach the order screens.
|
*/

Route::prefix( 'delivery' )->name( 'delivery.' )->group( function() {
    Route::middleware( 'guest' )->group( function() {
        Route::get( 'login', [AuthController::class, 'showLogin'] )->name( 'login' );
        Route::post( 'login', [AuthController::class, 'login'] )->name( 'login.store' );
    } );

    Route::middleware( ['auth', 'role:delivery', 'active'] )->group( function() {
        Route::get( '', [OrderController::class, 'index'] )->name( 'orders.index' );
        Route::post( 'logout', [AuthController::class, 'logout'] )->name( 'logout' );
        Route::post( 'location', [LocationController::class, 'store'] )->name( 'location' );

        Route::get( 'orders/{assignment}', [OrderController::class, 'show'] )->name( 'orders.show' );
        Route::post( 'orders/{assignment}/start', [OrderController::class, 'start'] )->name( 'orders.start' );
        Route::post( 'orders/{assignment}/delivered', [OrderController::class, 'deliver'] )->name( 'orders.deliver' );
        Route::post( 'orders/{assignment}/failed', [OrderController::class, 'fail'] )->name( 'orders.fail' );
    } );
} );
