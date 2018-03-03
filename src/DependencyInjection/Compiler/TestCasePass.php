<?php
/**
 * /src/DependencyInjection/Compiler/TestCasePass.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
declare(strict_types = 1);

namespace App\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class TestCasePass
 *
 * @package App\DependencyInjection\Compiler
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class TestCasePass implements CompilerPassInterface
{
    /**
     * Within this compiler pass we're setting some tagged services to public so that we can use those on tests a
     * proper way.
     *
     * @codeCoverageIgnore
     *
     * @param ContainerBuilder $container
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    public function process(ContainerBuilder $container): void
    {
        if (\getenv('APP_ENV') !== 'test') {
            return;
        }

        foreach ($container->findTaggedServiceIds('public_on_test') as $id => $tags) {
            $container->getDefinition($id)->setPublic(true);
        }
    }
}
