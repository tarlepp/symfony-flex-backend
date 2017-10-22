<?php
declare(strict_types=1);
/**
 * /tests/bootstrap_fastest.php
 *
 * @package App\Tests
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

// Specify used environment file
putenv('ENVIRONMENT_FILE=.env.test');

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../bootstrap.php';
