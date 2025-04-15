<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Endpoint Name
    |--------------------------------------------------------------------------
    |
    | Specify a path for the endpoint that will be used to transform and
    | and optimize images. This string will be the prefix for all image
    | transformations. For example, if you set this to `images`, then
    | the endpoint will be `/images/{options}/{path}`.
    |
    */
    'endpoint_name' => 'images',

    /*
    |--------------------------------------------------------------------------
    | Maximum number of attempts for rate limiting
    |--------------------------------------------------------------------------
    |
    | To prevent abuse, you can set a rate limit for each image. This will
    | allow a user to make this many transformations on a single image in
    | a minute before the request is redirected to the original image.
    | You can set this to null to disable rate limiting.
    |
    */
    'max_number_of_attempts' => 2,

    /*
    |--------------------------------------------------------------------------
    | Cache Control
    |--------------------------------------------------------------------------
    |
    | Specify the value to use for the Cache-Control header. This will allow
    | your CDN to cache the image for a long time. You probably don't need
    | to change this, the default will be cached for 1 year before the
    | CDN will need to revalidate the image. Browsers should never
    | revalidate images, as it's marked as immutable.
    |
    */
    'cache_control' => 'public, max-age=31536000, s-maxage=31536000, immutable',

    /*
    |--------------------------------------------------------------------------
    | Default image format fallback
    |--------------------------------------------------------------------------
    |
    | By default, we will use the incoming Accept header to determine the best
    | image format to use. If the Accept header is not present, and you have
    | not specified a format in the URL, we will use this fallback format.
    |
    */
    'default_format_fallback' => \nickwelsh\Fennel\Enums\ImageFormat::WebP,

    /*
    |--------------------------------------------------------------------------
    | Default image quality
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default quality to use for image compression.
    | This is a number between 1 and 100, and only applies to jpg, webp,
    | and avif images. All other image formats are unaffected.
    |
    */
    'default_quality' => 80,

    /*
     |--------------------------------------------------------------------------
     | Use public path
     |--------------------------------------------------------------------------
     |
     | If you have a very small app and are serving images from Laravel's
     | `public_path`, you can set this to true to use the public path
     | to serve images instead of a filesystem disk. If true, the
     | `public_path` will be used as the root for the `{path}`
     | parameter in the endpoint.
     |
     */
    'use_public_path' => false,

    /*
    |--------------------------------------------------------------------------
    | Filesystem disk
    |--------------------------------------------------------------------------
    |
    | If you're not using the public path, you can specify the filesystem
    | disk to use. This will default to the `FILESYSTEM_DISK`
    | environment variable.
    |
    */
    'disk' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Preserve animation frames
    |--------------------------------------------------------------------------
    |
    | Specify weather to preserve animation frames from input files. Setting
    | this to false reduces animations to still images. This setting is
    | recommended when enlarging images or processing arbitrary user
    | content, because large GIF animations can weigh tens or even
    | hundreds of megabytes.
    |
    */
    'preserve_animation_frames' => true,

    /*
    |--------------------------------------------------------------------------
    | Strip Metadata
    |--------------------------------------------------------------------------
    |
    | Specify whether to strip metadata from input files. Setting this to
    | true will strip all metadata from the image, including EXIF data.
    | NOTE: This does not apply when using GIF, PNG, or BMP encoders.
    |
    */
    'strip_metadata' => false,

    /*
    |--------------------------------------------------------------------------
    | Slow Connection Quality
    |--------------------------------------------------------------------------
    |
    | Specify the quality to use whenever a slow connection is detected. If
    | you prefer to not use this feature, set this to null. You can always
    | override this value by specifying a `slow-connection-quality`
    | option in the URL.
    |
    | Detecting slow connections is currently only supported on Chromium-based
    | browsers such as Chrome, Edge, and Opera. You can enable any of the
    | following client hints via HTTP in a header:
    | `accept-ch: rtt, save-data, ect, downlink`
    |
    | `slow-connection-quality` applies whenever any of the following is true
    | and the client hint is present:
    | - rtt: Greater than 150ms
    | - save-data: Value is "on"
    | - ect: Value is one of `slow-2g`, `2g, or `3g`
    | - downlink: Less than 5Mbps
    |
    */
    'slow_connection_quality' => 50,
];
