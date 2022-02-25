<?php

namespace TromsFylkestrafikk\Camera\Http\Controllers;

use TromsFylkestrafikk\Camera\Models\Camera;

class CameraController extends Controller
{
    /**
     * Resource controller callback for 'Camera' model.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Camera $camera)
    {
        return $camera;
    }
}
