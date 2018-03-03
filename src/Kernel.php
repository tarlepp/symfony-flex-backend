<?php
declare(strict_types = 1);
/**
 * /src/Kernel.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App;

use App\DependencyInjection\Compiler\TestCasePass;
use App\Resource\Collection;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

/**
 * Class Kernel
 *
 * @package App
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class Kernel extends BaseKernel implements CompilerPassInterface
{
    // Traits
    use MicroKernelTrait;

    private const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * Gets the cache directory.
     *
     * @return string The cache directory
     */
    public function getCacheDir(): string
    {
        return \dirname(__DIR__) . '/var/cache/' . $this->environment;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * Gets the log directory.
     *
     * @return string The log directory
     */
    public function getLogDir(): string
    {
        return \dirname(__DIR__) . '/var/log';
    }

    /**
     * Returns an array of bundles to register.
     *
     * @return iterable An array of bundle instances
     */
    public function registerBundles(): iterable
    {
        /** @noinspection UsingInclusionReturnValueInspection */
        /** @var array $contents */
        $contents = require \dirname(__DIR__) . '/config/bundles.php';

        foreach ($contents as $class => $envs) {
            if (isset($envs['all']) || isset($envs[$this->environment])) {
                yield new $class();
            }
        }
    }

    /**
     * The extension point similar to the Bundle::build() method.
     *
     * Use this method to register compiler passes and manipulate the container during the building process.
     *
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new TestCasePass());
    }

    /**
     * You can modify the container here before it is dumped to PHP code.
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

    /** @noinspection PhpUnusedParameterInspection */
    /**
     * Configures the container.
     *
     * You can register extensions:
     *
     * $c->loadFromExtension('framework', array(
     *     'secret' => '%secret%'
     * ));
     *
     * Or services:
     *
     * $c->register('halloween', 'FooBundle\HalloweenProvider');
     *
     * Or parameters:
     *
     * $c->setParameter('halloween', 'lot of fun');
     *
     * @param ContainerBuilder $container
     * @param LoaderInterface  $loader
     *
     * @throws \Exception
     */
    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $confDir = \dirname(__DIR__) . '/config';

        $loader->load($confDir . '/packages/*' . self::CONFIG_EXTS, 'glob');

        if (is_dir($confDir . '/packages/' . $this->environment)) {
            $loader->load($confDir . '/packages/' . $this->environment . '/**/*' . self::CONFIG_EXTS, 'glob');
        }

        $loader->load($confDir . '/services' . self::CONFIG_EXTS, 'glob');
        $loader->load($confDir . '/services_' . $this->environment . self::CONFIG_EXTS, 'glob');
    }

    /**
     * Add or import routes into your application.
     *
     *     $routes->import('config/routing.yml');
     *     $routes->add('/admin', 'AppBundle:Admin:dashboard', 'admin_dashboard');
     *
     * @param RouteCollectionBuilder $routes
     *
     * @throws \Symfony\Component\Config\Exception\FileLoaderLoadException
     */
    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $confDir = \dirname(__DIR__) . '/config';

        if (is_dir($confDir . '/routes/')) {
            $routes->import($confDir . '/routes/*' . self::CONFIG_EXTS, '/', 'glob');
        }

        if (is_dir($confDir . '/routes/' . $this->environment)) {
            $routes->import($confDir . '/routes/' . $this->environment . '/**/*' . self::CONFIG_EXTS, '/', 'glob');
        }

        $routes->import($confDir . '/routes' . self::CONFIG_EXTS, '/', 'glob');
    }
}
