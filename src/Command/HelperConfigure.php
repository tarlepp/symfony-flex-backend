<?php
declare(strict_types = 1);
/**
 * /src/Command/HelperConfigure.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Command;

use Closure;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use function array_key_exists;
use function array_map;

/**
 * Class HelperConfigure
 *
 * @package App\Command
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class HelperConfigure
{
    /**
     * @param array<int, array<string, int|string>> $parameters
     *
     * @throws InvalidArgumentException
     */
    public static function configure(Command $command, array $parameters): void
    {
        // Configure command
        $command->setDefinition(new InputDefinition(array_map(self::getParameterIterator(), $parameters)));
    }

    /**
     * @throws InvalidArgumentException
     */
    private static function getParameterIterator(): Closure
    {
        return static function (array $input): InputOption {
            $name = (string)$input['name'];
            $shortcut = array_key_exists('shortcut', $input) ? (string)$input['shortcut'] : null;
            $mode = array_key_exists('mode', $input) ? (int)$input['mode'] : InputOption::VALUE_OPTIONAL;
            $description = array_key_exists('description', $input) ? (string)$input['description'] : '';
            $default = array_key_exists('default', $input) ? (string)$input['default'] : null;

            return new InputOption($name, $shortcut, $mode, $description, $default);
        };
    }
}
