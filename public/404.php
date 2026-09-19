<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/Core/config.php';
require dirname(__DIR__) . '/app/Core/auth.php';
require dirname(__DIR__) . '/app/Support/html.php';

http_response_code(404);

$errorCode = '404';
$errorEyebrow = 'Page not found';
$errorTitle = 'This page took a different path.';
$errorText = 'The page you requested may have moved, been removed, or never existed.';
$errorPrimaryLabel = 'Back to AuraJob';
$errorPrimaryHref = hirepeak_url('index.php');
$errorSecondaryLabel = 'Find Jobs';
$errorSecondaryHref = hirepeak_url('jobs.php');

require dirname(__DIR__) . '/app/Views/error-page.php';
