<?php

use App\Http\Controllers\Api\HubController;
use App\Http\Controllers\Api\TodoController;
use App\Http\Controllers\Api\TodoSettingController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:120,1'])->group(function (): void {
    Route::get('todos', [TodoController::class, 'index']);
    Route::post('todos', [TodoController::class, 'store']);
    Route::get('todos/{todo}', [TodoController::class, 'show']);
    Route::post('todos/{todo}/done', [TodoController::class, 'done']);

    Route::apiResource('todo-settings', TodoSettingController::class);
});

Route::prefix('hub')->middleware('throttle:60,1')->group(function (): void {
    Route::get('packs', [HubController::class, 'index']);
    Route::post('packs/{pack}/imports', [HubController::class, 'import'])->whereNumber('pack');
});
