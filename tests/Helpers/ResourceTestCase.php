<?php
declare(strict_types=1);
/**
 * /tests/Helpers/ResourceTestCase.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Helpers;

use App\Rest\Interfaces\Resource as ResourceInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class ResourceTestCase
 *
 * @package App\Tests\Helpers
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
abstract class ResourceTestCase extends KernelTestCase
{
    /**
     * @var string
     */
    protected $resourceClass;

    /**
     * @var string
     */
    protected $repositoryClass;

    /**
     * @var string
     */
    protected $entityClass;

    /**
     * @var ResourceInterface
     */
    protected $resource;

    public function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $repository = static::$kernel->getContainer()->get($this->repositoryClass);
        $validator = static::$kernel->getContainer()->get('validator');

        $this->resource = new $this->resourceClass($repository, $validator);
    }

    public function testThatGetRepositoryReturnsExpected(): void
    {
        $message = \sprintf(
            'getRepository() method did not return expected repository \'%s\'.',
            $this->repositoryClass
        );

        self::assertInstanceOf($this->repositoryClass, $this->resource->getRepository(), $message);
    }

    public function testThatGetEntityNameReturnsExpected(): void
    {
        $message = \sprintf(
            'getEntityName() method did not return expected entity \'%s\'.',
            $this->entityClass
        );

        self::assertSame($this->entityClass, $this->resource->getEntityName(), $message);
    }
}
