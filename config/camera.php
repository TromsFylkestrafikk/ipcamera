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
     * NOTE! It is highly recommended to place image files in separate folders
     * per camera, identified by a unique camera attribute.  This will optimize
     * and simplify reverse camera detection on new image arrivals.
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
     * Regex pattern of filename within camera directory.
     *
     * Macro-expandable.
     */
    'file_pattern' => '[[camera_id]].*\.jpg',

    /**
     * If several cameras match same file pattern, return first.
     *
     * Otherwise, exception are thrown.
     */
    'pick_first_match' => false,
];
