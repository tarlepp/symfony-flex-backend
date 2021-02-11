<?php
declare(strict_types = 1);
/**
 * /src/Command/HelperConfigure.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Command;

use Closure;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use function array_key_exists;
use function array_map;

/**
 * Class HelperConfigure
 *
 * @package App\Command
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class HelperConfigure
{
    /**
     * @param array<int, array<string, int|string>> $parameters
     */
    public static function configure(Command $command, array $parameters): void
    {
        // Configure command
        $command->setDefinition(new InputDefinition(array_map(self::getParameterIterator(), $parameters)));
    }

    private static function getParameterIterator(): Closure
    {
        return static fn (array $input): InputOption => new InputOption(
            (string)$input['name'],
            array_key_exists('shortcut', $input) ? (string)$input['shortcut'] : null,
            array_key_exists('mode', $input) ? (int)$input['mode'] : InputOption::VALUE_OPTIONAL,
            array_key_exists('description', $input) ? (string)$input['description'] : '',
            array_key_exists('default', $input) ? (string)$input['default'] : null,
        );
    }
}
