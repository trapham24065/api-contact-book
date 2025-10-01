<?php

/**
 * @project aio-backend
 * @author  M397
 * @email m397.dev@gmail.com
 * @date    9/30/2025
 * @time    1:57 PM
 */
declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('/v1/auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:10,1'); // **Security: Limit 10 requests/minute/IP**

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1');
});
