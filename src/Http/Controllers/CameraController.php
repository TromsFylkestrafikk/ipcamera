<?php

namespace TromsFylkestrafikk\Camera\Http\Controllers;

use TromsFylkestrafikk\Camera\Models\Camera;

class CameraController extends Controller
{
    /**
     * Resource controller callback for 'Camera' model.
     *
     * @return \TromsFylkestrafikk\Camera\Models\Camera
     */
    public function show(Camera $camera)
    {
        return $camera;
    }
}
