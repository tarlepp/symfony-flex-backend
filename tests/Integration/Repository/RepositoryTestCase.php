<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Repository/RepositoryTestCase.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Repository;

use App\Entity\Interfaces\EntityInterface;
use App\Repository\Interfaces\BaseRepositoryInterface;
use App\Rest\Interfaces\RestResourceInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;
use function array_keys;
use function sort;

/**
 * Class RepositoryTestCase
 *
 * @package App\Tests\Integration\Repository
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
abstract class RepositoryTestCase extends KernelTestCase
{
    /**
     * @var class-string<EntityInterface>
     */
    protected string $entityName;

    /**
     * @var class-string<BaseRepositoryInterface>
     */
    protected string $repositoryName;

    /**
     * @var class-string<RestResourceInterface>
     */
    protected string $resourceName;

    /**
     * @var array<int, string>
     */
    protected array $associations = [];

    /**
     * @var array<int, string>
     */
    protected array $searchColumns = [];

    /**
     * @throws Throwable
     */
    public function testThatGetEntityNameReturnsExpected(): void
    {
        self::assertSame($this->entityName, $this->getRepository()->getEntityName());
    }

    /**
     * @throws Throwable
     */
    public function testThatGetAssociationsReturnsExpected(): void
    {
        $expected = $this->associations;
        $actual = array_keys($this->getRepository()->getAssociations());
        $message = 'Repository did not return expected associations for entity.';

        sort($expected);
        sort($actual);

        self::assertSame($expected, $actual, $message);
    }

    /**
     * @throws Throwable
     */
    public function testThatGetSearchColumnsReturnsExpected(): void
    {
        $expected = $this->searchColumns;
        $actual = $this->getRepository()->getSearchColumns();
        $message = 'Repository did not return expected search columns.';

        sort($expected);
        sort($actual);

        self::assertSame($expected, $actual, $message);
    }

    /**
     * @throws Throwable
     */
    protected function getRepository(): BaseRepositoryInterface
    {
        self::bootKernel();

        /** @var RestResourceInterface $resource */
        $resource = self::getContainer()->get($this->resourceName);

        return $resource->getRepository();
    }
}
