<?php
return [
    'users' => [
        [
            'user' => env('API_BASIC_AUTH_USERNAME', 'admin'),
            'password' => env('API_BASIC_AUTH_PASSWORD')
        ],
    ],
];