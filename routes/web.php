<?php

use Illuminate\Support\Facades\Route;
use ItsmeLaravel\Itsme\Controllers\ItsmeController;

Route::get('/redirect', [ItsmeController::class, 'redirect'])
    ->name('itsme.redirect');

Route::get('/callback', [ItsmeController::class, 'callback'])
    ->name('itsme.callback');

