<?php

return [
    'gateway_1' => [
        'name'  => env('GATEWAY_1_NAME', 'Gateway 1'),
        'url'   => env('GATEWAY_1_URL'),
        'token' => env('GATEWAY_1_TOKEN'),
    ],
    'gateway_2' => [
        'name'   => env('GATEWAY_2_NAME', 'Gateway 2'),
        'url'    => env('GATEWAY_2_URL'),
        'token'  => env('GATEWAY_2_AUTH_TOKEN'),
        'secret' => env('GATEWAY_2_AUTH_SECRET'),
    ],
];