<?php
declare(strict_types = 1);

/**
 * /tests/Integration/Decorator/DevelopmentKernel.php
 */

namespace App\Tests\Integration\Decorator;

use App\Kernel;
use Monolog\Handler\FilterHandler;
use Monolog\Handler\NullHandler;
use Monolog\Level;
use Override;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class DevelopmentKernel extends Kernel
{
    #[Override]
    protected function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new class() implements CompilerPassInterface {
            #[Override]
            public function process(ContainerBuilder $container): void
            {
                $handler = new Definition(FilterHandler::class, [
                    new Definition(NullHandler::class),
                    Level::Debug,
                    Level::Info,
                    false,
                ]);

                $container->getDefinition('monolog.logger.doctrine')
                    ->addMethodCall('pushHandler', [$handler]);
            }
        });
    }
}
