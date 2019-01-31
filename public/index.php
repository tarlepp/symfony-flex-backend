<?php
declare(strict_types = 1);
/**
 * /public/index.php
 *
 * @package App
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
use App\Kernel;
use Liuggio\Fastest\Environment\FastestEnvironment;
use Symfony\Component\Debug\Debug;
use Symfony\Component\HttpFoundation\Request;

require __DIR__ . '/../vendor/autoload.php';

// Set fastest environment
if (class_exists(FastestEnvironment::class)) {
    FastestEnvironment::setFromRequest();
}

$bootstrapFile = dirname(__DIR__) . '/config/bootstrap.php';

// The check is to ensure we don't use .env in production
if (!getenv('APP_ENV')) {
    // Specify used environment file
    \putenv('ENVIRONMENT_FILE=.env');

    $bootstrapFile = __DIR__ . '/../bootstrap.php';
}

require $bootstrapFile;

if ($_SERVER['APP_DEBUG']) {
    umask(0000);

    Debug::enable();
}

$trustedProxies = $_SERVER['TRUSTED_PROXIES'] ?? $_ENV['TRUSTED_PROXIES'] ?? false;
$trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? $_ENV['TRUSTED_HOSTS'] ?? false;

if ($trustedProxies !== false) {
    Request::setTrustedProxies(
        explode(',', $trustedProxies),
        Request::HEADER_X_FORWARDED_ALL ^ Request::HEADER_X_FORWARDED_HOST
    );
}

if ($trustedHosts !== false) {
    Request::setTrustedHosts([$trustedHosts]);
}

// Create new application kernel
$kernel = new Kernel($_SERVER['APP_ENV'], (bool)$_SERVER['APP_DEBUG']);

// Create request
$request = Request::createFromGlobals();

// Handle request and send response to client
$response = $kernel->handle($request);
$response->send();

// Terminate application
$kernel->terminate($request, $response);
