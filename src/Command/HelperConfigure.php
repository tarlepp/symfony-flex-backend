<?php
declare(strict_types=1);
/**
 * /src/Command/HelperConfigure.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

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
     * @param array   $parameters
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    public static function configure(Command $command, array $parameters): void
    {
        /**
         * Lambda iterator function to parse specified inputs.
         *
         * @param array $input
         *
         * @return InputOption
         */
        $iterator = function (array $input): InputOption {
            return new InputOption(
                $input['name'],
                $input['shortcut'] ?? null,
                $input['mode'] ?? InputOption::VALUE_OPTIONAL,
                $input['description'] ?? '',
                $input['default'] ?? null
            );
        };

        // Configure command
        $command->setDefinition(new InputDefinition(\array_map($iterator, $parameters)));
    }
}
