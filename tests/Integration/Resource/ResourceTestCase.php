<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Resource/ResourceTestCase.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Resource;

use App\Entity\Interfaces\EntityInterface;
use App\Repository\BaseRepository;
use App\Rest\Interfaces\RestResourceInterface;
use App\Rest\RestResource;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;
use function sprintf;

/**
 * Class ResourceTestCase
 *
 * @package App\Tests\Integration\Resource
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
abstract class ResourceTestCase extends KernelTestCase
{
    /**
     * @var class-string<EntityInterface>
     */
    protected string $entityClass;

    /**
     * @var class-string<BaseRepository>
     */
    protected string $repositoryClass;

    /**
     * @var class-string<RestResource>
     */
    protected string $resourceClass;

    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel();
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `getRepository` method returns expected repository service
     */
    public function testThatGetRepositoryReturnsExpected(): void
    {
        $message = sprintf(
            'getRepository() method did not return expected repository \'%s\'.',
            $this->repositoryClass
        );

        /** @noinspection UnnecessaryAssertionInspection */
        static::assertInstanceOf($this->repositoryClass, $this->getResource()->getRepository(), $message);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `getEntityName` returns expected entity name
     */
    public function testThatGetEntityNameReturnsExpected(): void
    {
        $message = sprintf(
            'getEntityName() method did not return expected entity \'%s\'.',
            $this->entityClass
        );

        static::assertSame($this->entityClass, $this->getResource()->getEntityName(), $message);
    }

    private function getResource(): RestResourceInterface
    {
        /** @var RestResourceInterface $resource */
        $resource = static::$container->get($this->resourceClass);

        return $resource;
    }
}
