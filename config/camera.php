<?php

return [

    /**
     * Route attributes group for camera routes.
     */
    'route_attributes' => [
        'prefix' => '',
        'middleware' => ['api'],
    ],

    /**
     * Disk where camera images resides.
     */
    'disk' => 'public',

    /**
     * Folder w/tokens where images are dropped.
     *
     * NOTE! It is highly recommended to place image files in separate folders
     * per camera, identified by a unique camera attribute.  This will optimize
     * and simplify reverse camera detection on new image arrivals.
     *
     * The folder can contain tokens that substitutes various camera
     * parameters. These tokens are encapsulated with double brackets,
     * e.g. [[name]]. The following tokens can be used:
     * - id:  Camera ID (Laravel internal ID)
     * - camera_id:  The camera's internal ID.
     * - name: Camera's name
     * - mac: Camera's MAC address
     * - ip: Camera's IP address.
     * - latitude
     * - longitude
     */
    'folder' => 'camera/[[id]]',

    /**
     * Regex pattern of filename within camera directory.
     *
     * Macro-expandable.
     */
    'file_regex' => '[[camera_id]].*\.(?i:jpe?g)',

    /**
     * During reverse filename => IP Camera lookup, this sets the behavior when
     * several cameras match the same file pattern. When set to true, it will
     * pick and broadcast the image to that camera's channel. If false, when
     * several cameras match the same file, nothing will be done.
     */
    'pick_first_match' => false,

    /**
     * Attach image as base64-encoded data on broadcast events when image files
     * are below this many bytes. Set to 0 or false to disable.
     */
    'base64_encode_below' => 64000,

    /**
     * Lifetime of current camera image.
     *
     * The API call for fetching the latest image will cache the currently found
     * 'latest' image for this period. The value must be in ISO8601 interval
     * format.  Set to 0 or false to omit caching.
     */
    'cache_current' => 'PT5S',
];
