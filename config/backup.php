<?php

return [
    'path' => env('BACKUP_PATH', base_path('backups')),
    'keep_days' => (int) env('BACKUP_KEEP_DAYS', 14),
];
