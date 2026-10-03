<?php

return [

    // First administrator, created by AdminUserSeeder when no user has this email.
    'admin' => [
        'name' => env('ADMIN_NAME', 'Admin'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    // Maximum size per uploaded file. Must not exceed the PHP limits (DEPLOYMENT.md §2).
    'asset_max_upload_mb' => (int) env('ASSET_MAX_UPLOAD_MB', 50),

    // Abandoned staged uploads are deleted after this many days (PRD.md §7.1).
    'staging_retention_days' => (int) env('STAGING_RETENTION_DAYS', 7),

    // A run that has not submitted results expires this many minutes after start (BENCHMARK.md §5).
    'run_expiry_minutes' => 30,

    // The only database that the verification scripts, tests, and DevFixtureSeeder may touch (AGENT.md §9).
    'verify_database' => 'render_lab_verify',

];
