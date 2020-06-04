<?php
declare(strict_types = 1);

use Liuggio\Fastest\Environment\FastestEnvironment;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

if (!class_exists(Dotenv::class)) {
    throw new LogicException('You need to add "symfony/dotenv" as Composer dependencies.');
}

// Set fastest environment
if (class_exists(FastestEnvironment::class)) {
    FastestEnvironment::setFromRequest();
}

// Ensure that current working directory is project root - this is needed to make relative paths to working properly
chdir(dirname(__DIR__));

$localPhpEnvFile = dirname(__DIR__) . '/.env.local.php';

// Load cached env vars if the .env.local.php file exists
// Run "composer dump-env prod" to create it (requires symfony/flex >=1.2)
/** @noinspection PhpIncludeInspection */
/** @noinspection UsingInclusionReturnValueInspection */
if (is_readable($localPhpEnvFile) && is_array($env = include $localPhpEnvFile)
    && ($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? $env['APP_ENV'] ?? null) === ($env['APP_ENV'] ?? null)
) {
    foreach ($env as $k => $v) {
        $_ENV[$k] ??= (isset($_SERVER[$k]) && strncmp($k, 'HTTP_', 5) !== 0 ? $_SERVER[$k] : $v);
    }
}

// load all the .env files
(new Dotenv())->loadEnv(dirname(__DIR__) . '/.env');

/** @noinspection AdditionOperationOnArraysInspection */
$_SERVER += $_ENV;
$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = ($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? null) ?: 'dev';
$_SERVER['APP_DEBUG'] ??= $_ENV['APP_DEBUG'] ?? $_SERVER['APP_ENV'] !== 'prod';

$debug = (int)$_SERVER['APP_DEBUG'] || filter_var($_SERVER['APP_DEBUG'], FILTER_VALIDATE_BOOLEAN) ? '1' : '0';

$_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = $debug;
