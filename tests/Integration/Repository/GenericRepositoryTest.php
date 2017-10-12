<?php
declare(strict_types=1);
/**
 * /tests/Integration/Integration/GenericRepositoryTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Repository;

use App\Entity\EntityInterface;
use App\Entity\User as UserEntity;
use App\Repository\BaseRepositoryInterface;
use App\Resource\UserResource;
use Doctrine\Common\Proxy\Proxy;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class GenericRepositoryTest
 *
 * @package App\Tests\Integration\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class GenericRepositoryTest extends KernelTestCase
{
    private $entityClass = UserEntity::class;
    private $resourceClass = UserResource::class;

    public function setUp(): void
    {
        parent::setUp();

        static::bootKernel();
    }

    public function testThatGetReferenceReturnsExpected(): void
    {
        /** @var EntityInterface $entity */
        $entity = new $this->entityClass();

        /** @var BaseRepositoryInterface $repository */
        $repository = static::$kernel->getContainer()->get($this->resourceClass)->getRepository();

        static::assertInstanceOf(Proxy::class, $repository->getReference($entity->getId()));
    }
}
