<?php
declare(strict_types = 1);
/**
 * rector.php
 */

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;

/**
 * @see /app/tools/09_rector/vendor/rector/rector/vendor/rector/rector-symfony/src/Set/SymfonyLevelSetList.php
 */
use Rector\Symfony\Set\SymfonyLevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/config',
        __DIR__ . '/migrations',
        __DIR__ . '/public',
        __DIR__ . '/src',
        /**
         * First run some rule(s) or sets to whole codebase and
         * run all the tests (phpunit, ecs, psalm and phpstan),
         * after that fix possible issues of those and run those
         * again until you don't have any issues left.
         *
         * After that enable this directory and run rector again
         * and do that whole process again.
         */
        //__DIR__ . '/tests',
    ]);

    // Enable single or multiple rules with rector
    //$rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);

    // Enable the set(s) that you want to run with rector
    $rectorConfig->sets([
        //LevelSetList::UP_TO_PHP_82, // This is for PHP version upgrade
        //SymfonyLevelSetList::UP_TO_SYMFONY_62, // This is for Symfont version upgrade
    ]);
};
