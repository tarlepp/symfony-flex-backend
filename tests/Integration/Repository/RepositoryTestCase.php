<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Repository/RepositoryTestCase.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Repository;

use App\Repository\BaseRepository;
use App\Rest\RestResource;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use function array_keys;
use function sort;

/**
 * Class RepositoryTestCase
 *
 * @package App\Tests\Integration\Repository
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @property BaseRepository $repository
 */
abstract class RepositoryTestCase extends KernelTestCase
{
    protected string $entityName;
    protected string $repositoryName;
    protected string $resourceName;
    protected array $associations = [];
    protected array $searchColumns = [];
    protected RestResource $resource;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        /** @var RestResource $resource */
        $resource = self::$container->get($this->resourceName);

        $this->resource = $resource;
        $this->repository = $this->resource->getRepository();
    }

    public function testThatGetEntityNameReturnsExpected(): void
    {
        static::assertSame($this->entityName, $this->repository->getEntityName());
    }

    public function testThatGetAssociationsReturnsExpected(): void
    {
        $expected = $this->associations;
        $actual = array_keys($this->repository->getAssociations());
        $message = 'Repository did not return expected associations for entity.';

        sort($expected);
        sort($actual);

        static::assertSame($expected, $actual, $message);
    }

    public function testThatGetSearchColumnsReturnsExpected(): void
    {
        $expected = $this->searchColumns;
        $actual = $this->repository->getSearchColumns();
        $message = 'Repository did not return expected search columns.';

        sort($expected);
        sort($actual);

        static::assertSame($expected, $actual, $message);
    }
}
