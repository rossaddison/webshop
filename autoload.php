<?php

declare(strict_types=1);

/**
 * autoload.php is Psalm level 1 tested (see psalm.xml at root):
 *   php ./vendor/bin/psalm autoload.php
 */

$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    fwrite(
        STDERR,
        "Dependencies not found. Please run 'composer install' in the project directory first.\n" .
        "If Composer is not installed, visit https://getcomposer.org/download/ for instructions.\n"
    );
    exit(1);
}

require_once $autoloadPath;

// Only attempt to use Dotenv if the class exists (in case dependencies are not fully installed)
if (class_exists('Dotenv\Dotenv')) {
    /** @var class-string<\Dotenv\Dotenv> $dotenvClass */
    $dotenvClass = 'Dotenv\Dotenv';
    $dotenv = $dotenvClass::createImmutable(__DIR__);
    $dotenv->load();
} else {
    fwrite(STDERR, "Dotenv not found. Ensure your Composer dependencies are installed.\n");
    exit(1);
}

// Safely parse and mirror important environment variables
$_ENV['YII_ENV'] = isset($_ENV['YII_ENV']) && strlen($_ENV['YII_ENV']) > 0 ? $_ENV['YII_ENV'] : null;
$_SERVER['YII_ENV'] = $_ENV['YII_ENV'];

$_ENV['YII_DEBUG'] = isset($_ENV['YII_DEBUG']) && strlen($_ENV['YII_DEBUG']) > 0
    ? filter_var($_ENV['YII_DEBUG'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
    : false;
$_SERVER['YII_DEBUG'] = $_ENV['YII_DEBUG'];

/**
 * Session cookies get the Secure flag only once a deployment opts in, after
 * confirming TLS is actually live end-to-end — yiisoft/session throws a hard
 * SessionException on every request if cookie_secure is on but the request
 * scheme isn't https, so defaulting this to true would break any deployment
 * that isn't behind confirmed HTTPS yet.
 */
$_ENV['SESSION_COOKIE_SECURE'] = isset($_ENV['SESSION_COOKIE_SECURE']) && strlen($_ENV['SESSION_COOKIE_SECURE']) > 0
    ? filter_var($_ENV['SESSION_COOKIE_SECURE'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
    : false;
$_SERVER['SESSION_COOKIE_SECURE'] = $_ENV['SESSION_COOKIE_SECURE'];
