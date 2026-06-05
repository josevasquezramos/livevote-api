<?php

use App\Http\Controllers\VoteController;
use Illuminate\Support\Facades\Route;

Route::get('/status', [VoteController::class, 'status']);
Route::post('/vote', [VoteController::class, 'vote']);
