<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Resource/ResourceTestCase.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Resource;

use App\Rest\Interfaces\RestResourceInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use function sprintf;

/**
 * Class ResourceTestCase
 *
 * @package App\Tests\Integration\Resource
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
abstract class ResourceTestCase extends KernelTestCase
{
    protected string $resourceClass;
    protected string $repositoryClass;
    protected string $entityClass;
    protected RestResourceInterface $resource;

    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel();

        /* @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->resource = static::$container->get($this->resourceClass);
    }

    public function testThatGetRepositoryReturnsExpected(): void
    {
        $message = sprintf(
            'getRepository() method did not return expected repository \'%s\'.',
            $this->repositoryClass
        );

        /** @noinspection UnnecessaryAssertionInspection */
        static::assertInstanceOf($this->repositoryClass, $this->resource->getRepository(), $message);
    }

    public function testThatGetEntityNameReturnsExpected(): void
    {
        $message = sprintf(
            'getEntityName() method did not return expected entity \'%s\'.',
            $this->entityClass
        );

        static::assertSame($this->entityClass, $this->resource->getEntityName(), $message);
    }
}
