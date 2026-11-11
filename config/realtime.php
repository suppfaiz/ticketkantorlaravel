<?php

return [
    'driver' => env('REALTIME_DRIVER', 'polling'),
    'poll_interval_ms' => (int) env('REALTIME_POLL_INTERVAL_MS', 5000),
    'websocket_url' => env('WEBSOCKET_URL'),
];
