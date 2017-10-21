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
use Symfony\Component\Dotenv\Dotenv;

$environmentFile = getenv('ENVIRONMENT_FILE');

// Application is started against 'fastest' library, so we need to override database name manually
if ($readableChannel = getenv('ENV_TEST_CHANNEL_READABLE')) {
    // Parse current '.env.test' file
    $variables = (new Dotenv())->parse(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . $environmentFile));

    // Specify new database name for current test env
    $databaseName = $variables['DATABASE_NAME'] . '_' . $readableChannel;

    // Replace DATABASE_URL variable
    $variables['DATABASE_URL'] = \str_replace(
        '/' . $variables['DATABASE_NAME'] . '?',
        '/' . $databaseName . '?',
        $variables['DATABASE_URL']
    );

    // Replace DATABASE_NAME variable
    $variables['DATABASE_NAME'] = $databaseName;

    // And finally populate new variables to current environment
    (new Dotenv())->populate($variables);
} else {
    // Load environment variables normally
    (new Dotenv())->load(__DIR__ . DIRECTORY_SEPARATOR . $environmentFile);
}
