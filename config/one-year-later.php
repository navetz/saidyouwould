<?php

return [
    'price_cents' => (int) env('OYL_PRICE_CENTS', 500),
    'currency' => env('OYL_CURRENCY', 'usd'),
    'video_disk' => env('OYL_VIDEO_DISK', 'r2'),
    'video_directory' => env('OYL_VIDEO_DIRECTORY', 'one-year-later/videos'),
    'max_video_size_kb' => (int) env('OYL_MAX_VIDEO_SIZE_KB', 102400),
    'allowed_video_types' => [
        'video/mp4',
        'application/mp4',
        'video/quicktime',
        'video/webm',
        'video/x-m4v',
    ],
    'upload_link_minutes' => (int) env('OYL_UPLOAD_LINK_MINUTES', 15),
    'playback_link_minutes' => (int) env('OYL_PLAYBACK_LINK_MINUTES', 120),
    'max_delivery_years' => (int) env('OYL_MAX_DELIVERY_YEARS', 5),
    'delivery_link_days' => (int) env('OYL_DELIVERY_LINK_DAYS', 365),
];
