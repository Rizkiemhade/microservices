<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [OrderController::class, 'health']);
Route::post('/orders', [OrderController::class, 'store']);
Route::get('/orders/{id}', [OrderController::class, 'show']);
Route::get('/orders/user/{userId}', [OrderController::class, 'userOrders']);
Route::patch('/orders/{id}/pay', [OrderController::class, 'pay']);
