<?php
/**
 * /src/DependencyInjection/Compiler/ResourceCollectionPass.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
declare(strict_types = 1);

namespace App\DependencyInjection\Compiler;

use App\Resource\Collection;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class ResourceCollectionPass
 *
 * @package App\DependencyInjection\Compiler
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ResourceCollectionPass implements CompilerPassInterface
{
    /**
     * This process will attach all REST resource objects to collection class, where we can use those on certain cases.
     *
     * @codeCoverageIgnore
     *
     * @param ContainerBuilder $container
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    public function process(ContainerBuilder $container): void
    {
        $collection = $container->getDefinition(Collection::class);

        foreach ($container->findTaggedServiceIds('app.rest.resource') as $id => $tags) {
            $collection->addMethodCall('set', [new Reference($id)]);
        }
    }
}
