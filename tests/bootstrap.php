<?php
declare(strict_types = 1);
/**
 * /tests/bootstrap.php
 *
 * Bootstrap for PHPUnit tests, basically we need to do following things:
 *  1) Load test environment variables
 *  2) Boot kernel and create console application with that
 *  3) Drop test environment database
 *  4) Create empty database to test environment
 *  5) Update database schema
 *  6) Create user roles to database
 *
 * @package App\Tests
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Filesystem\Filesystem;

// Specify used environment file
putenv('ENVIRONMENT_FILE=.env.test');

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../bootstrap.php';

// Create and boot 'test' kernel
$kernel = new Kernel(getenv('APP_ENV'), (bool)getenv('APP_DEBUG'));
$kernel->boot();

// Create new application
$application = new Application($kernel);
$application->setAutoExit(false);

// Add the doctrine:database:drop command to the application and run it
$dropDatabaseDoctrineCommand = function () use ($application) {
    $input = new ArrayInput([
        'command'       => 'doctrine:database:drop',
        '--force'       => true,
        '--if-exists'   => true,
    ]);

    $input->setInteractive(false);

    $application->run($input, new ConsoleOutput());
};

// Add the doctrine:database:create command to the application and run it
$createDatabaseDoctrineCommand = function () use ($application) {
    $input = new ArrayInput([
        'command' => 'doctrine:database:create',
    ]);

    $input->setInteractive(false);

    $application->run($input, new ConsoleOutput());
};

// Add the doctrine:schema:update command to the application and run it
$updateSchemaDoctrineCommand = function () use ($application) {
    $input = new ArrayInput([
        'command' => 'doctrine:schema:update',
        '--force' => true,
    ]);

    $input->setInteractive(false);

    $application->run($input, new ConsoleOutput());
};

// Add the doctrine:fixtures:load command to the application and run it
$loadFixturesDoctrineCommand = function () use ($application) {
    $input = new ArrayInput([
        'command'           => 'doctrine:fixtures:load',
        '--no-interaction'  => true,
    ]);

    $input->setInteractive(false);

    $application->run($input, new ConsoleOutput());
};

// Ensure that used cache folder is cleared
$clearCaches = function () use ($kernel) {
    $fs = new Filesystem();
    $fs->remove($kernel->getCacheDir());
};

// And finally call each of initialize functions to make test environment ready
array_map(
    '\call_user_func',
    [
        $dropDatabaseDoctrineCommand,
        $createDatabaseDoctrineCommand,
        $updateSchemaDoctrineCommand,
        $loadFixturesDoctrineCommand,
        // Weird - really weird this cache delete will slowdown tests ~50%
        //$clearCaches,
    ]
);
