<?php

namespace TromsFylkestrafikk\Camera\Http\Controllers;

use DateTime;
use DateInterval;
use DateTimezone;
use Illuminate\Http\Response;
use TromsFylkestrafikk\Camera\Models\Camera;
use TromsFylkestrafikk\Camera\Services\CurrentHandler;

class CameraController extends Controller
{
    /**
     * Resource controller callback for 'Camera' model.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Camera $camera)
    {
        $current = new CurrentHandler($camera);
        return response([
            'success' => true,
            'camera' => $current->refresh(),
        ]);
    }

    /**
     * Get specific file for given camera.
     *
     * @param string $fileName
     * @return \Illuminate\Http\Response
     */
    public function showFile(Camera $camera, $fileName)
    {
        $filePath = $camera->fullDir . '/' . $fileName;
        if (!file_exists($filePath)) {
            return response('', Response::HTTP_NOT_FOUND);
        }
        return $this->responseCachedFile($filePath);
    }

    protected function responseCachedFile($filename)
    {
        $fileStats = stat($filename);
        $modified = DateTime::createFromFormat('U', $fileStats['mtime'], new DateTimezone(config('app.timezone')));
        $headerEtag = md5(md5_file($filename) . $fileStats['mtime']);
        $headers = [
            'Cache-Control' => 'max-age=0, must-revalidate',
            'Content-Disposition' => sprintf('inline; filename="%s"', basename($filename)),
            'Etag' => $headerEtag,
            'Expires' => (clone $modified)->add(new DateInterval('P1M'))->format('r'),
            'Last-Modified' => $modified->format('r'),
            'Pragma' => 'public',
        ];
        $server = request()->server;
        if ($server->has('HTTP_IF_MODIFIED_SINCE')) {
            $ifModifiedSince = DateTime::createFromFormat('D, j M Y H:i:s T', $server->get('HTTP_IF_MODIFIED_SINCE'));
            $isModifiedSince = $modified > $ifModifiedSince;
        } else {
            $isModifiedSince = true;
        }

        $isMatch =
            $server->has('HTTP_IF_NONE_MATCH') &&
            $server->get('HTTP_IF_NONE_MATCH') === $headerEtag;
        if (!$isModifiedSince || $isMatch) {
            return response('', Response::HTTP_NOT_MODIFIED, $headers);
        }
        return response()->file($filename, $headers);
    }

    /**
     * Expand given macro for this camera.
     */
    protected function expandMacro($macro, Camera $camera)
    {
        $allowed = ['id', 'camera_id', 'name', 'ip', 'mac', 'latitude', 'longitude'];
        return preg_replace_callback('|\[\[(?<property>[-a-zA-Z_]+)\]\]|U', function ($matches) use ($allowed, $camera) {
            $prop = $matches['property'];
            return in_array($prop, $allowed) ? $camera->{$prop} : '';
        }, $macro);
    }
}
