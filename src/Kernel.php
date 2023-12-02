<?php
declare(strict_types = 1);
/**
 * /src/Kernel.php
 */

namespace App;

use App\Compiler\StopwatchCompilerPass;
use Override;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

/**
 * Class Kernel
 *
 * @package App
 */
class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    #[Override]
    protected function build(ContainerBuilder $container): void
    {
        parent::build($container);

        if ($this->environment === 'dev') {
            $container->addCompilerPass(new StopwatchCompilerPass());
        }
    }
}
