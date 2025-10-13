<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VismaWebhookController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/webhooks/visma/inventory/{token}', [VismaWebhookController::class, 'inventory'])
    ->name('visma.webhooks.inventory');

// (optional, only if you’ll use SalesOrder webhooks)
Route::post('/webhooks/visma/sales/{token}', [VismaWebhookController::class, 'sales'])
    ->name('visma.webhooks.sales');
