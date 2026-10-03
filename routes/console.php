<?php

use App\Jobs\CleanupStagedUploads;
use App\Jobs\ExpireTestRuns;
use Illuminate\Support\Facades\Schedule;

// One cron entry runs `schedule:run` every minute (DEPLOYMENT.md). Jobs run on the
// sync connection inside that call, so no queue worker process is needed.
Schedule::job(new CleanupStagedUploads, connection: 'sync')->daily()->withoutOverlapping();
Schedule::job(new ExpireTestRuns, connection: 'sync')->everyFiveMinutes()->withoutOverlapping();
