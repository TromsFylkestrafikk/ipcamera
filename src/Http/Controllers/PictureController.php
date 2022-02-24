<?php

namespace TromsFylkestrafikk\Camera\Http\Controllers;

use DateTime;
use DateInterval;
use DateTimezone;
use Illuminate\Http\Response;
use TromsFylkestrafikk\Camera\Models\Picture;

class PictureController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param  \TromsFylkestrafikk\Camera\Models\Picture  $picture
     * @return \Illuminate\Http\Response
     */
    public function show(Picture $picture)
    {
        return $picture->load('camera');
    }

    /**
     * @param \TromsFylkestrafikk\Camera\Models\Picture $picture
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\Response
     */
    public function download(Picture $picture)
    {
        if (!file_exists($picture->full_path)) {
            return response('', Response::HTTP_NOT_FOUND);
        }
        return $this->responseCachedFile($picture->full_path);
    }

    /**
     * Return binary file response with appropriate cache headers.
     *
     * @param string $filename
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\Response
     */
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
}
