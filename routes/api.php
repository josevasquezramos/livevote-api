<?php

use App\Http\Controllers\BroadcastAuthController;
use App\Http\Controllers\VoteController;
use Illuminate\Support\Facades\Route;

Route::get('/status', [VoteController::class, 'status']);
Route::post('/vote', [VoteController::class, 'vote']);
Route::get('/participants', [VoteController::class, 'participants']);

Route::post('/broadcasting/auth', [BroadcastAuthController::class, 'authenticate']);
