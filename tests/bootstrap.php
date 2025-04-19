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
use App\Utils\JSON;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Filesystem\Filesystem;

require dirname(__DIR__) . '/vendor/autoload.php';

/**
 * Function to make necessary initialization for `fastest` - This means that we
 * need to set `DATABASE_URL` environment variable, before we initialize `DotEnv`
 * component for testing environment.
 *
 * @throws Throwable
 */
$InitializeFastestEnvironmentVariables = static function (string $readableChannel): void {
    /**
     * @var array<string, string> $cache
     */
    static $cache = [];

    if (!array_key_exists($readableChannel, $cache)) {
        /**
         * Parse current environment file
         *
         * @var array<string, string> $variables
         */
        $variables = new Dotenv()->parse((string)file_get_contents(dirname(__DIR__) . '/.env.test'));

        /** @var array<string, string> $configuration */
        $configuration = JSON::decode((string)file_get_contents($variables['APPLICATION_CONFIG']), true);

        if (!array_key_exists('DATABASE_URL', $configuration)) {
            throw new RuntimeException('Cannot get `DATABASE_URL from specified env file.');
        }

        $originalDatabaseUrl = $configuration['DATABASE_URL'];

        $databaseName = trim(((array)parse_url($originalDatabaseUrl))['path'] ?? '', '/');

        // Replace DATABASE_URL variable with proper database name
        $databaseUrl = str_replace(
            '/' . $databaseName . '?',
            '/' . $databaseName . '_' . $readableChannel . '?',
            $originalDatabaseUrl
        );

        $cache[$readableChannel] = $databaseUrl;
    }

    // And finally populate new variables to current environment
    putenv('DATABASE_URL=' . $cache[$readableChannel]);
};

/**
 * Function to initialize test environment for use
 */
$InitializeEnvironment = static function (): void {
    $localPhpEnvFile = dirname(__DIR__) . '/.env.local.php';

    /**
     * @psalm-suppress MissingFile
     * @var array<string, string>|null $env
     */
    $env = is_readable($localPhpEnvFile) ? include $localPhpEnvFile : null;

    // Load cached env vars if the .env.local.php file exists
    // Run "composer dump-env prod" to create it (requires symfony/flex >=1.2)
    if (is_array($env)
        && ($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? $env['APP_ENV'] ?? null) === ($env['APP_ENV'] ?? null)
    ) {
        foreach ($env as $k => $v) {
            $_ENV[$k] ??= (isset($_SERVER[$k]) && !str_starts_with($k, 'HTTP_') ? $_SERVER[$k] : $v);
        }
    }

    // load all the .env files
    new Dotenv()->loadEnv(dirname(__DIR__) . '/.env');

    /** @noinspection AdditionOperationOnArraysInspection */
    $_SERVER += $_ENV;

    $environment = $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? null;

    $_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = $environment ?? 'dev';
    $_SERVER['APP_DEBUG'] ??= $_ENV['APP_DEBUG'] ?? $_SERVER['APP_ENV'] !== 'prod';

    $debug = (int)$_SERVER['APP_DEBUG'] || filter_var($_SERVER['APP_DEBUG'], FILTER_VALIDATE_BOOLEAN) ? '1' : '0';

    $_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = $debug;
};

chdir(dirname(__DIR__));

$readableChannel = (string)getenv('ENV_TEST_CHANNEL_READABLE');

// Application is started against 'fastest' library, so we need to override database name manually
if (strlen($readableChannel) > 0) {
    $InitializeFastestEnvironmentVariables($readableChannel);
}

$InitializeEnvironment();

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
$kernel = new Kernel((string)getenv('APP_ENV'), (bool)getenv('APP_DEBUG'));
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
    file_put_contents($databaseCacheFile, '{"init": ' . new DateTime()->format(DATE_RFC3339) . '}');
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
