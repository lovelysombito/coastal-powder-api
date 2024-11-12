<?php

return [
    'status_code' => [
        'success' => 200,
        'error' => 400,
        'not_authorize' => 401,
        'not_found' => 404,
        'bad_request' => 400,
        'server_error'=>500
    ],
    'pagination' => [
        'job' => 100,
        'dispatch'=>100,
        'product'=>100,
        'failed_option'=>100,
        'comment'=>100,
        'treatment'=>100
    ],
    'holidayList' => [
        '2022-07-22',
        '2022-07-26',
        '2022-07-28'
    ],
    'ready_to_schedule' => 'Ready to Schedule',
    'error_message' => 'Something has gone wrong, please try again',
    'today' => 'Today',
    'tomorrow' => 'Tomorrow',
    'ready' => 'ready',
    'front_url' => env('FRONT_END_URL') ? env('FRONT_END_URL') : 'http://localhost:3000/'
];
