<?php

// Front controller for Hostinger's public_html/ (DEPLOYMENT.md §3). The Laravel project lives in ../app-core/.
// Replace the default public/index.php with this file after you move public/ into public_html/.

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/../app-core/storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../app-core/vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/../app-core/bootstrap/app.php';

// public_html/ is the public folder (build/, favicon, .htaccess).
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
