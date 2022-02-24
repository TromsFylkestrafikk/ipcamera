<?php

use Illuminate\Support\Facades\Route;
use TromsFylkestrafikk\Camera\Http\Controllers\CameraController;
use TromsFylkestrafikk\Camera\Http\Controllers\PictureController;

Route::resource('cameras', CameraController::class)->only(['show']);
Route::resource('cameras.pictures', PictureController::class)->shallow()->only(['show']);
