<?php
declare(strict_types=1);
/**
 * /tests/RepositoryTestCase.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Helpers;

use App\Entity\Interfaces\EntityInterface;
use App\Rest\Interfaces\Repository;
use Doctrine\Common\Proxy\Proxy;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class RepositoryTestCase
 *
 * @package App\Tests
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RepositoryTestCase extends KernelTestCase
{
    /**
     * @var \App\Rest\Repository
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
     * @var array
     */
    protected $associations = [];

    /**
     * @var array
     */
    protected $searchColumns = [];

    public function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->repository = static::$kernel->getContainer()->get($this->repositoryName);
    }

    public function testThatGetEntityNameReturnsExpected(): void
    {
        static::assertSame($this->entityName, $this->repository->getEntityName());
    }

    public function testThatGetReferenceReturnsExpected(): void
    {
        /** @var EntityInterface $entity */
        $entity = new $this->entityName();

        static::assertInstanceOf(
            Proxy::class,
            $this->repository->getReference($entity->getId())
        );
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

    public function testThatSaveMethodCallsExpectedServices(): void
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityInterface $entityInterface */
        $entityInterface = $this->createMock($this->entityName);

        // Create mock for entity manager
        $entityManager = $this
            ->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Check that 'persist' method is called
        $entityManager
            ->expects(static::once())
            ->method('persist')
            ->with($entityInterface);

        // Check that 'flush' method is called
        $entityManager
            ->expects(static::once())
            ->method('flush');

        $repositoryClass = \get_class($this->repository);

        /** @var Repository $repository */
        $repository = new $repositoryClass($entityManager, new ClassMetadata($this->entityName));

        // Call save method
        $repository->save($entityInterface);
    }

    public function testThatRemoveMethodCallsExpectedServices(): void
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityInterface $entityInterface */
        $entityInterface = $this->createMock($this->entityName);

        // Create mock for entity manager
        $entityManager = $this
            ->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Check that 'persist' method is called
        $entityManager
            ->expects(static::once())
            ->method('remove')
            ->with($entityInterface);

        // Check that 'flush' method is called
        $entityManager
            ->expects(static::once())
            ->method('flush');

        $repositoryClass = \get_class($this->repository);

        /** @var Repository $repository */
        $repository = new $repositoryClass($entityManager, new ClassMetadata($this->entityName));

        // Call remove method
        $repository->remove($entityInterface);
    }
}
