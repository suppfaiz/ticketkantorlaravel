<?php

use App\Http\Controllers\Api\LoaWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

Route::prefix('v1')->group(function () {
    Route::post('/webhooks/loa-decision', [LoaWebhookController::class, 'handle'])
        ->middleware('verify.loa.signature');
});
