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
use function array_map;

/**
 * Class HelperConfigure
 *
 * @package App\Command
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class HelperConfigure
{
    /**
     * @param Command $command
     * @param mixed[] $parameters
     *
     * @throws InvalidArgumentException
     */
    public static function configure(Command $command, array $parameters): void
    {
        // Configure command
        $command->setDefinition(new InputDefinition(array_map(self::getParameterIterator(), $parameters)));
    }

    /**
     * @return Closure
     *
     * @throws InvalidArgumentException
     */
    private static function getParameterIterator(): Closure
    {
        /**
         * Lambda iterator function to parse specified inputs.
         *
         * @param array $input
         *
         * @return InputOption
         */
        return static function (array $input): InputOption {
            return new InputOption(
                $input['name'],
                $input['shortcut'] ?? null,
                $input['mode'] ?? InputOption::VALUE_OPTIONAL,
                $input['description'] ?? '',
                $input['default'] ?? null
            );
        };
    }
}
