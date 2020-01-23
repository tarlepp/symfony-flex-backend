<?php
declare(strict_types = 1);

use App\Kernel;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__) . '/config/bootstrap.php';

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
/** @noinspection PhpUnhandledExceptionInspection */
$response = $kernel->handle($request);
$response->send();

// Terminate application
$kernel->terminate($request, $response);
