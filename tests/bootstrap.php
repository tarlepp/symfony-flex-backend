<?php
declare(strict_types=1);
/**
 * /tests/bootstrap.php
 *
 * Bootstrap for PHPUnit tests, basically we need to do following things:
 *  1) Load test environment variables
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

// Load test environment variables
(new Dotenv())->load(__DIR__.'/../.env.test');
