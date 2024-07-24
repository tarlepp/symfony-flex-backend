<?php
declare(strict_types = 1);
/**
 * /src/Command/Traits/GetApplicationTrait.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Command\Traits;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Exception\RuntimeException;

/**
 * @package App\Command\Traits
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
trait GetApplicationTrait
{
    /**
     * @throws RuntimeException
     */
    public function getApplication(): Application
    {
        return parent::getApplication()
            ?? throw new RuntimeException('Cannot determine application for console command to use.');
    }
}
