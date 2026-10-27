<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\StoreOrderController;
use Illuminate\Support\Facades\Route;

Route::post('/orders', StoreOrderController::class);
Route::get('/users/{userId}/orders', [OrderController::class, 'index'])->whereNumber('userId');
Route::get('/users/{userId}/orders/{orderId}', [OrderController::class, 'show'])
    ->whereNumber('userId')
    ->whereNumber('orderId');
Route::patch('/users/{userId}/orders/{orderId}/status', [OrderController::class, 'updateStatus'])
    ->whereNumber('userId')
    ->whereNumber('orderId');
