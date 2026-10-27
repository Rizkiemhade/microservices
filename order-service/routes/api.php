<?php

use App\Http\Controllers\StoreOrderController;
use Illuminate\Support\Facades\Route;

Route::post('/orders', StoreOrderController::class);
