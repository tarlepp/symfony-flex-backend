<?php
declare(strict_types=1);
/**
 * /tests/Integration/Rest/RepositoryTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Rest;

use App\Entity\User as UserEntity;
use App\Repository\UserRepository;
use App\Utils\Tests\PHPUnitUtil;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Container;

/**
 * Class RepositoryTest
 *
 * @package App\Tests\Integration\Rest
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RepositoryTest extends KernelTestCase
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var UserEntity
     */
    protected $entity;

    /**
     * @var string
     */
    protected $entityName = UserEntity::class;

    /**
     * @var UserRepository
     */
    protected $repository;

    public function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->container = static::$kernel->getContainer();
        $this->entityManager = $this->container->get('doctrine.orm.default_entity_manager');
        $this->repository = $this->entityManager->getRepository($this->entityName);
    }

    public function testThatGetEntityManagerWorksIfPreviousOneIsClosed(): void
    {
        $em = $this->repository->getEntityManager();
        $em->close();
        $em = $this->repository->getEntityManager();

        static::assertTrue($em->isOpen());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Join type 'invalid type' is not supported.
     */
    public function testThatAddJoinToQueryThrowsAnExceptionWithInvalidType(): void
    {
        PHPUnitUtil::callMethod($this->repository, 'addJoinToQuery', ['invalid type', []]);
    }

    /**
     * @dataProvider dataProviderTestThatAddLeftJoinWorksAsExpected
     *
     * @param string $expected
     * @param string $method
     * @param array  $joins
     */
    public function testThatAddJoinMethodsWorksLikeExpected(string $expected, string $method, array $joins): void
    {
        foreach ($joins as $parameters) {
            $this->repository->$method($parameters);
        }

        $qb = $this->repository->createQueryBuilder('entity');

        $this->repository->processQueryBuilder($qb);

        $message = \sprintf(
            'Method \'%s\' did not return expected DQL.',
            $method
        );

        static::assertSame($expected, $qb->getDQL(), $message);
    }

    public function testThatAddCallbackWorksLikeExpectedWithoutArgs(): void
    {
        $callback = function ($queryBuilder) {
            static::assertInstanceOf(QueryBuilder::class, $queryBuilder);
        };

        $qb = $this->repository->createQueryBuilder('entity');

        $this->repository->addCallback($callback);
        $this->repository->processQueryBuilder($qb);
    }

    /**
     * @dataProvider dataProviderTestThatAddCallbackWorksLikeExpectedWithArgs
     *
     * @param int   $expectedArgsCount
     * @param array $expectedArgs
     */
    public function testThatAddCallbackWorksLikeExpectedWithArgs(int $expectedArgsCount, array $expectedArgs): void
    {
        $callback = function (...$args) use ($expectedArgsCount, $expectedArgs) {
            static::assertCount($expectedArgsCount, $args);
            static::assertInstanceOf(QueryBuilder::class, \array_shift($args));
            static::assertSame($expectedArgs, $args);
        };

        $qb = $this->repository->createQueryBuilder('entity');

        $this->repository->addCallback($callback, $expectedArgs);
        $this->repository->processQueryBuilder($qb);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatAddLeftJoinWorksAsExpected(): array
    {
        return [
            [
                /** @lang text */
                'SELECT entity FROM App\Entity\User entity LEFT JOIN entity.userGroups ug',
                'addLeftJoin',
                [
                    ['entity.userGroups', 'ug'],
                ],
            ],
            [
                /** @lang text */
                'SELECT entity FROM App\Entity\User entity LEFT JOIN entity.userGroups ug',
                'addLeftJoin',
                [
                    ['entity.userGroups', 'ug'],
                    ['entity.userGroups', 'ug'],
                ],
            ],
            [
                /** @lang text */
                'SELECT entity FROM App\Entity\User entity LEFT JOIN entity.userGroups ug LEFT JOIN ug.role r',
                'addLeftJoin',
                [
                    ['entity.userGroups', 'ug'],
                    ['ug.role', 'r'],
                ],
            ],
            [
                /** @lang text */
                'SELECT entity FROM App\Entity\User entity LEFT JOIN entity.userGroups ug LEFT JOIN ug.role r',
                'addLeftJoin',
                [
                    ['entity.userGroups', 'ug'],
                    ['entity.userGroups', 'ug'],
                    ['ug.role', 'r'],
                    ['ug.role', 'r'],
                ],
            ],
            [
                /** @lang text */
                'SELECT entity FROM App\Entity\User entity INNER JOIN entity.userGroups ug',
                'addInnerJoin',
                [
                    ['entity.userGroups', 'ug'],
                ],
            ],
            [
                /** @lang text */
                'SELECT entity FROM App\Entity\User entity INNER JOIN entity.userGroups ug',
                'addInnerJoin',
                [
                    ['entity.userGroups', 'ug'],
                    ['entity.userGroups', 'ug'],
                ],
            ],
            [
                /** @lang text */
                'SELECT entity FROM App\Entity\User entity INNER JOIN entity.userGroups ug INNER JOIN ug.role r',
                'addInnerJoin',
                [
                    ['entity.userGroups', 'ug'],
                    ['ug.role', 'r'],
                ],
            ],
            [
                /** @lang text */
                'SELECT entity FROM App\Entity\User entity INNER JOIN entity.userGroups ug INNER JOIN ug.role r',
                'addInnerJoin',
                [
                    ['entity.userGroups', 'ug'],
                    ['entity.userGroups', 'ug'],
                    ['ug.role', 'r'],
                    ['ug.role', 'r'],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderTestThatAddCallbackWorksLikeExpectedWithArgs(): array
    {
        return [
            [1, []],
            [2, ['foo']],
            [3, ['foo', new \stdClass()]],
        ];
    }
}
