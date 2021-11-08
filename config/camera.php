<?php

return [

    /**
     * Route attributes group for camera routes.
     */
    'route_attributes' => [
        'prefix' => '',
        'middleware' => ['api'],
    ],

    /*------------------------------------------------------------------------
     |
     | Disk, storage and file system settings
     |
     *------------------------------------------------------------------------
     |
     | NOTE! It is highly recommended to place image files in separate folders
     | per camera, identified by a unique camera attribute.  This will optimize
     | and simplify reverse camera detection on new image arrivals.
     |
     | Many parameters support tokens that substitutes various camera
     | parameters. These tokens are encapsulated with double brackets,
     | e.g. [[name]]. The following tokens can be used:
     |
     | - id:  Camera ID (Laravel internal ID)
     | - camera_id:  The camera's internal ID.
     | - name: Camera's name
     | - mac: Camera's MAC address
     | - ip: Camera's IP address.
     | - latitude
     | - longitude
     *------------------------------------------------------------------------
     */

    /**
     * Laravel disk used for incoming files.
     */
    'incoming_disk' => 'camera_listen',

    /**
     * Folder on disk where images are dropped. Supports tokens.
     */
    'incoming_folder' => 'camera/[[id]]',

    /**
     * File system disk where served, processed camera images reside.
     *
     * If no manipulation or filtering is required, use the same disk as
     * 'disk_incoming'
     */
    'disk' => 'public',

    /**
     * Folder where final, published images reside. Supports tokens.
     */
    'folder' => 'camera/[[id]]',

    /**
     * Regex pattern of filename within camera directory.
     *
     * Supports tokens. Note that tokens are expanded first, and any regex
     * characters within these are escaped.
     */
    'file_regex' => '[[camera_id]].*\.(?i:jpe?g)',

    /**
     * During reverse filename => IP Camera lookup, this sets the behavior when
     * several cameras match the same file pattern. When set to true, it will
     * pick and broadcast the image to that camera's channel. If false, when
     * several cameras match the same file, nothing will be done.
     */
    'pick_first_match' => false,

    /*------------------------------------------------------------------------
     |
     | Cache, optimization, tweaks
     |
     *------------------------------------------------------------------------
     */

    /**
     * Attach image as base64-encoded data on broadcast events when image files
     * are below this many bytes. Set to 0 or false to disable.
     */
    'base64_encode_below' => 32000,

    /**
     * Lifetime of current camera image.
     *
     * The API call for fetching the latest image will cache the currently found
     * 'latest' image for these many seconds.
     */
    'cache_current' => 5,

    /**
     * Do not provide latest image if the last incoming image is older than this.
     *
     * Max age is configured as an ISO-8601 duration.
     * @see https://en.wikipedia.org/wiki/ISO_8601#Durations
     *
     * Also, this value should be considerably longer than the 'cache_current'
     * configuration property.
     */
    'max_age' => 'PT1H',

    /**
     * If the inotify extension isn't available, run a cron job every configured
     * seconds to look for the latest file for each camera.
     */
    'poor_mans_inotify' => false,

    /*------------------------------------------------------------------------
     |
     | Per camera image processing
     |
     *------------------------------------------------------------------------
     |
     | Images may be modified using the Intervention image API with custom
     | config per camera. The "configuration" is a PHP script that returns a
     | closure which accepts an image object and camera model as arguments.  An
     | example config for a camera may be:
     |
     |     <?php
     |
     |     use Intervention\Image\Image;
     |     use TromsFylkestrafikk\Camera\Models\Camera;
     |
     |     return function (Image $image, Camera $camera) {
     |         $image->->crop(720, 406, 10, 30);
     |     };
     |
     | @see http://image.intervention.io/
     |
     *------------------------------------------------------------------------
     */

    /**
     * Directory containing per-camera image processors.
     *
     * This is relative to app root, i.e. laravel root folder containing 'app',
     * 'config', 'routes', 'storage', etc.
     */
    'processor_dir' => 'config/camera_processors',

    /**
     * File pattern on custom image processors.
     *
     * Supports model macros. See section 'Disk, storage and file system
     * settings'
     */
    'processor_inc' => '[[id]]-[[name]].inc',
];
