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
use UnexpectedValueException;
use function assert;
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

    protected ?RestResourceInterface $resource = null;

    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel();

        $resource = static::$container->get($this->resourceClass);

        assert($resource instanceof RestResourceInterface);

        $this->resource = $resource;
    }

    public function getResource(): RestResourceInterface
    {
        return $this->resource instanceof RestResourceInterface
            ? $this->resource
            : throw new UnexpectedValueException('Resource not set');
    }

    /**
     * @throws Throwable
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
     */
    public function testThatGetEntityNameReturnsExpected(): void
    {
        $message = sprintf(
            'getEntityName() method did not return expected entity \'%s\'.',
            $this->entityClass
        );

        static::assertSame($this->entityClass, $this->getResource()->getEntityName(), $message);
    }
}
