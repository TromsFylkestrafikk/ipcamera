<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use TromsFylkestrafikk\Camera\Http\Controllers\CameraController;

Route::get('camera/latest/{id}', [CameraController::class, 'getStopPlaceImage']);
