<?php
declare(strict_types=1);

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Debug\Debug;

require __DIR__.'/../vendor/autoload.php';

// The check is to ensure we don't use .env in production
if (!getenv('APP_ENV')) {
    (new Dotenv())->load(__DIR__.'/../.env');
}

if (getenv('APP_DEBUG')) {
    // WARNING: You should setup permissions the proper way!
    // REMOVE the following PHP line and read
    // https://symfony.com/doc/current/book/installation.html#checking-symfony-application-configuration-and-setup
    umask(0000);

    // Get allowed IP addresses
    $allowedAddress = require __DIR__ . '/../etc/packages/dev/allowed_addresses.php';

    if (!\in_array('*', $allowedAddress, true)
        && (
            isset($_SERVER['HTTP_CLIENT_IP'])
            || isset($_SERVER['HTTP_X_FORWARDED_FOR'])
            || !(\in_array($_SERVER['REMOTE_ADDR'], $allowedAddress, true)
            || PHP_SAPI === 'cli-server')
        )
    ) {
        header('HTTP/1.0 403 Forbidden');
        exit('You are not allowed to access this file. Check '.basename(__FILE__).' for more information.');
    }

    /** @noinspection ForgottenDebugOutputInspection */
    Debug::enable();
}

// Request::setTrustedProxies(['0.0.0.0/0'], Request::HEADER_FORWARDED);

$kernel = new Kernel(getenv('APP_ENV'), getenv('APP_DEBUG'));
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
