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
 *  5) Run migrations to test database
 *  6) Load fixture date to database
 *  7) Write cache files about database initialization
 *
 * @package App\Tests
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
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

$databaseCacheFile = sprintf(
    '%s%stest_database_cache%s.json',
    sys_get_temp_dir(),
    DIRECTORY_SEPARATOR,
    (string)getenv('ENV_TEST_CHANNEL_READABLE')
);

// Oh yeah, database is already created we don't want to do any lifting anymore \o/
if (is_readable($databaseCacheFile) && (string)getenv('ENV_TEST_CHANNEL_READABLE') !== '') {
    return;
}

// Create and boot 'test' kernel
$kernel = new Kernel(getenv('APP_ENV'), (bool)getenv('APP_DEBUG'));
$kernel->boot();

// Create new application
$application = new Application($kernel);
$application->setAutoExit(false);

// Add the doctrine:database:drop command to the application and run it
$dropDatabaseDoctrineCommand = static function () use ($application): void {
    $input = new ArrayInput([
        'command' => 'doctrine:database:drop',
        '--force' => true,
        '--if-exists' => true,
    ]);

    $input->setInteractive(false);

    $application->run($input, new ConsoleOutput());
};

// Add the doctrine:database:create command to the application and run it
$createDatabaseDoctrineCommand = static function () use ($application): void {
    $input = new ArrayInput([
        'command' => 'doctrine:database:create',
    ]);

    $input->setInteractive(false);

    $application->run($input, new ConsoleOutput());
};

// Add the doctrine:schema:update command to the application and run it
$updateSchemaDoctrineCommand = static function () use ($application): void {
    $input = new ArrayInput([
        'command' => 'doctrine:migrations:migrate',
        '--no-interaction' => true,
    ]);

    $input->setInteractive(false);

    $application->run($input, new ConsoleOutput());
};

// Add the doctrine:fixtures:load command to the application and run it
$loadFixturesDoctrineCommand = static function () use ($application): void {
    $input = new ArrayInput([
        'command' => 'doctrine:fixtures:load',
        '--no-interaction' => true,
    ]);

    $input->setInteractive(false);

    $application->run($input, new ConsoleOutput());
};

// Ensure that we have "clean" JWT auth cache file
$createJwtAuthCache = static function (): void {
    // Specify used cache file
    $filename = sprintf(
        '%s%stest_jwt_auth_cache%s.json',
        sys_get_temp_dir(),
        DIRECTORY_SEPARATOR,
        (string)getenv('ENV_TEST_CHANNEL_READABLE')
    );

    // Remove existing cache if exists
    $fs = new Filesystem();
    $fs->remove($filename);
    unset($fs);

    // Create empty cache file
    file_put_contents($filename, '{}');
};

// Create database cache file
$createDatabaseCreateCache = static function () use ($databaseCacheFile): void {
    // Create database cache file
    file_put_contents($databaseCacheFile, '{"init": ' . (new DateTime())->format(DATE_RFC3339) . '}');
};

// And finally call each of initialize functions to make test environment ready
array_map(
    '\call_user_func',
    [
        $dropDatabaseDoctrineCommand,
        $createDatabaseDoctrineCommand,
        $updateSchemaDoctrineCommand,
        $loadFixturesDoctrineCommand,
        $createJwtAuthCache,
        $createDatabaseCreateCache,
    ]
);
