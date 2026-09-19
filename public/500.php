<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/Core/config.php';
require dirname(__DIR__) . '/app/Core/auth.php';
require dirname(__DIR__) . '/app/Support/html.php';

http_response_code(500);

$errorCode = '500';
$errorEyebrow = 'Temporary issue';
$errorTitle = 'AuraJob hit a bump.';
$errorText = 'Something went wrong on our side. No technical details are exposed. Please try again shortly.';
$errorPrimaryLabel = 'Back to AuraJob';
$errorPrimaryHref = hirepeak_url('index.php');
$errorSecondaryLabel = 'Find Jobs';
$errorSecondaryHref = hirepeak_url('jobs.php');

require dirname(__DIR__) . '/app/Views/error-page.php';
