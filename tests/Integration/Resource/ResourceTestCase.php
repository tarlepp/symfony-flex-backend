<?php
declare(strict_types=1);
/**
 * /tests/Integration/Resource/ResourceTestCase.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Resource;

use App\Rest\RepositoryInterface;
use App\Rest\RestResourceInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class ResourceTestCase
 *
 * @package App\Tests\Integration\Resource
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
     * @var RestResourceInterface
     */
    protected $resource;

    public function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $repository = static::$kernel->getContainer()->get($this->repositoryClass);
        $validator = static::$kernel->getContainer()->get('validator');

        $this->resource = $this->getResource($repository, $validator);
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

    /**
     * @param RepositoryInterface $repository
     * @param ValidatorInterface  $validator
     *
     * @return RestResourceInterface
     */
    protected function getResource(RepositoryInterface $repository, ValidatorInterface $validator): RestResourceInterface
    {
        return new $this->resourceClass($repository, $validator);
    }
}
