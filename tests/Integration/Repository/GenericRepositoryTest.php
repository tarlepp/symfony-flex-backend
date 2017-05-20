<?php
declare(strict_types=1);
/**
 * /tests/Integration/Integration/GenericRepositoryTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Repository;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\User as UserEntity;
use App\Repository\UserRepository;
use App\Resource\UserResource;
use App\Rest\Repository;
use Doctrine\Common\Proxy\Proxy;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class GenericRepositoryTest
 *
 * @package App\Tests\Integration\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class GenericRepositoryTest extends KernelTestCase
{
    protected $entityClass = UserEntity::class;
    protected $resourceClass = UserResource::class;
    protected $repositoryClass = UserRepository::class;

    /**
     * @return EntityManagerInterface|Object
     */
    private static function getEntityManager(): EntityManagerInterface
    {
        return static::$kernel->getContainer()->get('doctrine.orm.entity_manager');
    }

    public function setUp(): void
    {
        parent::setUp();

        static::bootKernel();
    }

    public function testThatGetReferenceReturnsExpected(): void
    {
        /** @var EntityInterface $entity */
        $entity = new $this->entityClass();

        /** @var Repository $repository */
        $repository = new $this->repositoryClass(static::getEntityManager(), new ClassMetadata($this->entityClass));

        static::assertInstanceOf(
            Proxy::class,
            $repository->getReference($entity->getId())
        );
    }

    public function testThatSaveMethodCallsExpectedServices(): void
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityInterface $entityInterface */
        $entityInterface = $this->createMock($this->entityClass);

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

        /** @var Repository $repository */
        $repository = new $this->repositoryClass($entityManager, new ClassMetadata($this->entityClass));

        // Call save method
        $repository->save($entityInterface);
    }

    public function testThatRemoveMethodCallsExpectedServices(): void
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityInterface $entityInterface */
        $entityInterface = $this->createMock($this->entityClass);

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

        /** @var Repository $repository */
        $repository = new $this->repositoryClass($entityManager, new ClassMetadata($this->entityClass));

        // Call remove method
        $repository->remove($entityInterface);
    }
}
