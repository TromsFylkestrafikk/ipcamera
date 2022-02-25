<?php

use Illuminate\Support\Facades\Route;
use TromsFylkestrafikk\Camera\Http\Controllers\PictureController;

Route::get('pictures/{picture}/download', [PictureController::class, 'download'])->name('camera.picture.download');
