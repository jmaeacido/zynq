<?php

return [
    'invoice_range_enabled' => env('OFFLINE_INVOICE_RANGE_ENABLED', false),
    'invoice_range_size' => (int) env('OFFLINE_INVOICE_RANGE_SIZE', 25),
    'invoice_range_ttl_hours' => (int) env('OFFLINE_INVOICE_RANGE_TTL_HOURS', 24),
    'stale_snapshot_minutes' => (int) env('OFFLINE_STALE_SNAPSHOT_MINUTES', 240),
];
