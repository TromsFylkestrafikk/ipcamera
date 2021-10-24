<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use TromsFylkestrafikk\Camera\Http\Controllers\CameraController;

Route::resource('cameras', CameraController::class)->only(['show']);
Route::get('cameras/{camera}/file/{file}', [CameraController::class, 'showFile'])->name('camera.file');
