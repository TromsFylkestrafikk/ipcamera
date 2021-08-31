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
     * Folder w/pattern where images are dropped.
     *
     * The pattern can contain tokens that substitutes various camera
     * parameters. These tokens are encapsulated with double brackets,
     * e.g. [[name]]. The following tokens can be used:
     * - id:  Camera ID (Laravel internal ID)
     * - camera_id:  The camera's internal ID.
     * - name: Camera name
     * - mac: Camera's MAC address
     * - ip: Camera's IP address.
     */
    'folder' => '/camera/[[id]]',

    /**
     * Glob pattern of filename within directory.
     *
     * Macro-expandable.
     */
    'file_pattern' => '[[camera_id]]*.jpg'
];
