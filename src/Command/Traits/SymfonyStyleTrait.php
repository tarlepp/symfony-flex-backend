<?php
declare(strict_types = 1);
/**
 * /src/Command/Traits/SymfonyStyleTrait.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Command\Traits;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Trait SymfonyStyleTrait
 *
 * @package App\Command\Traits
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait SymfonyStyleTrait
{
    /**
     * Method to get SymfonyStyle object for console commands.
     */
    protected function getSymfonyStyle(
        InputInterface $input,
        OutputInterface $output,
        ?bool $clearScreen = null
    ): SymfonyStyle {
        $clearScreen ??= true;

        $io = new SymfonyStyle($input, $output);

        if ($clearScreen) {
            $io->write("\033\143");
        }

        return $io;
    }
}
