<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VisitorLogController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/webhook/preapproved-visitor', [VisitorLogController::class, 'webhookPreapproved']);
Route::post('/test', function () {
    return ['success' => true];
});
