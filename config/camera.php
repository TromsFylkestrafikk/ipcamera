<?php

return [

    /**
     * Route attributes group for camera routes.
     */
    'route_attributes' => [
        'api' => [
            'prefix' => 'api',
            'middleware' => ['api'],
        ],
        'web' => [
            'prefix' => '',
            'middleware' => ['web'],
        ],
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
     * 'incoming_disk'
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
     * several cameras match the same file pattern.  When true, it will pick and
     * broadcast to the first found camera. If false, when several cameras match
     * the same file, nothing will be done.
     */
    'pick_first_match' => false,

    /*------------------------------------------------------------------------
     |
     | Cache, optimization, tweaks
     |
     *------------------------------------------------------------------------
     */

    /**
     * Picture URLs are base64-encoded when size is below this.
     *
     * Set to 0 or false to disable. This comes handy during broadcasting events
     * as small images are sent directly over the web socket, instead of
     * creating a new http get request.
     */
    'base64_encode_below' => 32000,

    /**
     * Do not provide latest image if the last incoming image is older than this.
     *
     * Max age is configured as an ISO-8601 duration.
     * @see https://en.wikipedia.org/wiki/ISO_8601#Durations
     */
    'max_age' => 'PT1H',

    /**
     * If the inotify extension isn't available, run a cron job every n-th
     * minutes to look for the latest file for each camera.
     *
     * Set to 0 to disable.
     */
    'poor_mans_inotify' => 0,

    /*------------------------------------------------------------------------
     |
     | Camera image manipulation
     |
     *------------------------------------------------------------------------
     |
     | Images may be modified using the intervention/image package.
     |
     | @see https://github.com/intervention/image
     |
     | This image, along with the picture model is sent through this
     | configurable pipeline of handlers.  Each handler accepts an array
     | containing the image and picture model ($state['image'],
     | $state['picture']), and return the same structure through a given
     | callback handler.  Example implementation may be:
     |
     | @begincode
     | namespace MyApp;
     |
     | class MyCameraProcessor {
     |     public function handle ($state, $next) {
     |         $state['image']->blur(50);
     |         return $next($state);
     |     }
     | }
     | @endcode
     |
     | Then, in here:
     |
     | @begincode
     | 'manipulators' => [
     |     MyApp\MyCameraProcessor::class,
     | ],
     | @endcode
     |
     *------------------------------------------------------------------------
     */
    'manipulators' => [],
];
