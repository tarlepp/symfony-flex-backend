<?php
declare(strict_types=1);
/**
 * /tests/Integration/Repository/RepositoryTestCase.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Repository;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class RepositoryTestCase
 *
 * @package App\Tests\Integration\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RepositoryTestCase extends KernelTestCase
{
    /**
     * @var \App\Rest\RestResource
     */
    protected $resource;

    /**
     * @var \App\Repository\BaseRepository
     */
    protected $repository;

    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var string
     */
    protected $repositoryName;

    /**
     * @var string
     */
    protected $resourceName;

    /**
     * @var array
     */
    protected $associations = [];

    /**
     * @var array
     */
    protected $searchColumns = [];

    public function testThatGetEntityNameReturnsExpected(): void
    {
        static::assertSame($this->entityName, $this->repository->getEntityName());
    }

    public function testThatGetAssociationsReturnsExpected(): void
    {
        $message = 'Repository did not return expected associations for entity.';

        static::assertSame(
            $this->associations,
            \array_keys($this->repository->getAssociations()),
            $message
        );
    }

    public function testThatGetSearchColumnsReturnsExpected(): void
    {
        $message = 'Repository did not return expected search columns.';

        static::assertSame(
            $this->searchColumns,
            $this->repository->getSearchColumns(),
            $message
        );
    }

    protected function setUp(): void
    {
        gc_enable();

        parent::setUp();

        self::bootKernel();

        $this->resource = static::$kernel->getContainer()->get($this->resourceName);
        $this->repository = $this->resource->getRepository();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->resource, $this->repository);

        gc_collect_cycles();
    }
}
