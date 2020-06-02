<?php
declare(strict_types = 1);
/**
 * Application bootstrap file to load specified environment variables.
 *
 * @see ./public/index.php
 * @see ./tests/bootstrap.php
 *
 * @package App
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

use App\Utils\JSON;
use Symfony\Component\Dotenv\Dotenv;

$environmentFile = (string)getenv('ENVIRONMENT_FILE');
$readableChannel = (string)getenv('ENV_TEST_CHANNEL_READABLE');

// Application is started against 'fastest' library, so we need to override database name manually
if (strlen($readableChannel) > 0) {
    // Parse current environment file - most likely '.env.test' file because `$readableChannel` exists
    $variables = (new Dotenv())->parse(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . $environmentFile));

    /** @noinspection PhpUnhandledExceptionInspection */
    $applicationConfig = JSON::decode(file_get_contents($variables['APPLICATION_CONFIG']), true);

    // Specify new database name for current test env
    $databaseName = $applicationConfig['DATABASE_NAME'] . '_' . $readableChannel;

    // Replace DATABASE_URL variable with proper database name
    $databaseUrl = str_replace(
        '/' . $applicationConfig['DATABASE_NAME'] . '?',
        '/' . $databaseName . '?',
        $applicationConfig['DATABASE_URL']
    );

    // And finally populate new variables to current environment
    putenv('DATABASE_NAME=' . $databaseName);
    putenv('DATABASE_URL=' . $databaseUrl);
}

require __DIR__ . '/config/bootstrap.php';
