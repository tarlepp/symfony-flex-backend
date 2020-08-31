<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Resource/GenericResourceTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Resource;

use App\DTO\RestDtoInterface;
use App\DTO\User\User as UserDto;
use App\Entity\ApiKey as ApiKeyEntity;
use App\Entity\User as UserEntity;
use App\Repository\UserRepository;
use App\Resource\UserResource;
use App\Utils\Tests\StringableArrayObject;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Generator;
use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;
use UnexpectedValueException;
use function get_class;

/**
 * Class GenericResourceTest
 *
 * @package App\Tests\Integration\Resource
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class GenericResourceTest extends KernelTestCase
{
    private string $dtoClass = UserDto::class;
    private string $resourceClass = UserResource::class;
    private string $entityClass = UserEntity::class;
    private UserResource $resource;

    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel();

        /* @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->resource = static::$container->get($this->resourceClass);
    }

    public function testThatGetDtoClassThrowsAnExceptionWithoutDto(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessageMatches('/DTO class not specified for \'.*\' resource/');

        $this->resource
            ->setDtoClass('')
            ->getDtoClass();
    }

    public function testThatGetDtoClassReturnsExpectedDto(): void
    {
        $this->resource->setDtoClass('foobar');

        static::assertSame('foobar', $this->resource->getDtoClass());
    }

    /**
     * @throws Throwable
     */
    public function testThatGetEntityNameCallsExpectedRepositoryMethod(): void
    {
        /** @var MockObject|UserRepository $repository */
        $repository = $this->getRepositoryMockBuilder()->disableOriginalConstructor()->getMock();

        $repository
            ->expects(static::once())
            ->method('getEntityName');

        $this->resource
            ->setRepository($repository)
            ->getEntityName();
    }

    /**
     * @throws Throwable
     */
    public function testThatGetReferenceCallsExpectedRepositoryMethod(): void
    {
        /** @var MockObject|UserRepository $repository */
        $repository = $this->getRepositoryMockBuilder()->disableOriginalConstructor()->getMock();

        $repository
            ->expects(static::once())
            ->method('getReference');

        $this->resource
            ->setRepository($repository)
            ->getReference('some id');
    }

    /**
     * @throws Throwable
     */
    public function testThatGetAssociationsCallsExpectedRepositoryMethod(): void
    {
        /** @var MockObject|UserRepository $repository */
        $repository = $this->getRepositoryMockBuilder()->disableOriginalConstructor()->getMock();

        $repository
            ->expects(static::once())
            ->method('getAssociations');

        $this->resource
            ->setRepository($repository)
            ->getAssociations();
    }

    /**
     * @throws Throwable
     */
    public function testThatGetDtoForEntityCallsExpectedRepositoryMethod(): void
    {
        $entity = $this->getEntityMock();

        /** @var MockObject|UserRepository $repository */
        $repository = $this->getRepositoryMockBuilder()->disableOriginalConstructor()->getMock();

        $repository
            ->expects(static::once())
            ->method('find')
            ->with('some id')
            ->willReturn($entity);

        /** @var MockObject|RestDtoInterface $dto */
        $dto = $this->getDtoMockBuilder()->getMock();

        $this->resource->setRepository($repository);

        /** @noinspection UnnecessaryAssertionInspection */
        static::assertInstanceOf(
            RestDtoInterface::class,
            $this->resource->getDtoForEntity('some id', get_class($dto), $dto)
        );
    }

    /**
     * @throws Throwable
     */
    public function testThatGetDtoForEntityThrowsAnExceptionIfEntityWasNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Not found');

        /** @var MockObject|UserRepository $repository */
        $repository = $this->getRepositoryMockBuilder()->disableOriginalConstructor()->getMock();

        $repository
            ->expects(static::once())
            ->method('find')
            ->with('some id')
            ->willReturn(null);

        /** @var MockObject|RestDtoInterface $dto */
        $dto = $this->getDtoMockBuilder()->getMock();

        $this->resource
            ->setRepository($repository)
            ->getDtoForEntity('some id', get_class($dto), $dto);
    }

    /**
     * @dataProvider dataProviderTestThatFindCallsExpectedRepositoryMethodWithCorrectParameters
     *
     * @throws Throwable
     *
     * @testdox Test that `findByAdvanced` method is called with `$expectedArguments` when using `$arguments` arguments.
     */
    public function testThatFindCallsExpectedRepositoryMethodWithCorrectParameters(
        StringableArrayObject $expectedArguments,
        StringableArrayObject $arguments
    ): void {
        /** @var MockObject|UserRepository $repository */
        $repository = $this->getRepositoryMockBuilder()->disableOriginalConstructor()->getMock();

        $repository
            ->expects(static::once())
            ->method('findByAdvanced')
            ->with(...$expectedArguments->getArrayCopy());

        $this->resource
            ->setRepository($repository)
            ->find(...$arguments->getArrayCopy());
    }

    /**
     * @throws Throwable
     */
    public function testThatFindOneCallsExpectedRepositoryMethod(): void
    {
        /** @var MockObject|UserRepository $repository */
        $repository = $this->getRepositoryMockBuilder()->disableOriginalConstructor()->getMock();

        $repository
            ->expects(static::once())
            ->method('findAdvanced')
            ->with('some id');

        $this->resource
            ->setRepository($repository)
            ->findOne('some id');
    }

    /**
     * @throws Throwable
     */
    public function testThatFindOneThrowsAnExceptionIfEntityWasNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Not found');

        /** @var MockObject|UserRepository $repository */
        $repository = $this->getRepositoryMockBuilder()->disableOriginalConstructor()->getMock();

        $repository
            ->expects(static::once())
            ->method('findAdvanced')
            ->with('some id')
            ->willReturn(null);

        $this->resource
            ->setRepository($repository)
            ->findOne('some id', true);
    }

    /**
     * @throws Throwable
     */
    public function testThatFindOneWontThrowAnExceptionIfEntityWasFound(): void
    {
        $entity = $this->getEntityMock();

        /** @var MockObject|UserRepository $repository */
        $repository = $this->getRepositoryMockBuilder()->disableOriginalConstructor()->getMock();

        $repository
            ->expects(static::once())
            ->method('findAdvanced')
            ->with('some id')
            ->willReturn($entity);

        $this->resource->setRepository($repository);

        static::assertSame($entity, $this->resource->findOne('some id', true));
    }

    /**
     * @dataProvider dataProviderTestThatFindOneByCallsExpectedRepositoryMethodWithCorrectParameters
     *
     * @throws Throwable
     *
     * @testdox Test that `findOneBy` method is called with `$expectedArguments` when using `$arguments` arguments.
     */
    public function testThatFindOneByCallsExpectedRepositoryMethodWithCorrectParameters(
        StringableArrayObject $expectedArguments,
        StringableArrayObject $arguments
    ): void {
        /** @var MockObject|UserRepository $repository */
        $repository = $this->getRepositoryMockBuilder()->disableOriginalConstructor()->getMock();

        $repository
            ->expects(static::once())
            ->method('findOneBy')
            ->with(...$expectedArguments->getArrayCopy());

        $this->resource
            ->setRepository($repository)
            ->findOneBy(...$arguments->getArrayCopy());
    }

    /**
     * @throws Throwable
     */
    public function testThatFindOneByThrowsAnExceptionIfEntityWasNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Not found');

        /** @var MockObject|UserRepository $repository */
        $repository = $this->getRepositoryMockBuilder()->disableOriginalConstructor()->getMock();

        $repository
            ->expects(static::once())
            ->method('findOneBy')
            ->with([], [])
            ->willReturn(null);

        $this->resource
            ->setRepository($repository)
            ->findOneBy([], null, true);
    }

    /**
     * @throws Throwable
     */
    public function testThatFindOneByWontThrowAnExceptionIfEntityWasFound(): void
    {
        $entity = $this->getEntityMock();

        /** @var MockObject|UserRepository $repository */
        $repository = $this->getRepositoryMockBuilder()->disableOriginalConstructor()->getMock();

        $repository
            ->expects(static::once())
            ->method('findOneBy')
            ->with([], [])
            ->willReturn($entity);

        $this->resource->setRepository($repository);

        static::assertSame($entity, $this->resource->findOneBy([], null, true));
    }

    /**
     * @dataProvider dataProviderTestThatCountCallsExpectedRepositoryMethodWithCorrectParameters
     *
     * @throws Throwable
     *
     * @testdox Test that `countAdvanced` method is called with `$expectedArguments` when using `$arguments` arguments.
     */
    public function testThatCountCallsExpectedRepositoryMethodWithCorrectParameters(
        StringableArrayObject $expectedArguments,
        StringableArrayObject $arguments
    ): void {
        /** @var MockObject|UserRepository $repository */
        $repository = $this->getRepositoryMockBuilder()->disableOriginalConstructor()->getMock();

        $repository
            ->expects(static::once())
            ->method('countAdvanced')
            ->with(...$expectedArguments->getArrayCopy());

        $this->resource
            ->setRepository($repository)
            ->count(...$arguments->getArrayCopy());
    }

    /**
     * @throws Throwable
     */
    public function testThatSaveMethodCallsExpectedRepositoryMethod(): void
    {
        $entity = new ApiKeyEntity();

        /** @var MockObject|UserRepository $repository */
        $repository = $this->getRepositoryMockBuilder()->disableOriginalConstructor()->getMock();

        $repository
            ->expects(static::once())
            ->method('save')
            ->with($entity);

        $this->resource->setRepository($repository);

        static::assertSame($entity, $this->resource->save($entity));
    }

    /**
     * @throws Throwable
     */
    public function testThatCreateMethodThrowsAnErrorWithInvalidDto(): void
    {
        $this->expectException(ValidatorException::class);

        /** @var MockObject|UserRepository $repository */
        $repository = $this->getRepositoryMockBuilder()->disableOriginalConstructor()->getMock();

        $repository
            ->expects(static::once())
            ->method('getEntityName')
            ->willReturn(UserEntity::class);

        $dto = new $this->dtoClass();

        $this->resource
            ->setRepository($repository)
            ->create($dto);
    }

    /**
     * @throws Throwable
     */
    public function testThatCreateMethodCallsExpectedMethods(): void
    {
        /** @var MockObject|UserRepository $repository */
        $repository = $this->getRepositoryMockBuilder()->disableOriginalConstructor()->getMock();

        $repository
            ->expects(static::once())
            ->method('getEntityName')
            ->willReturn($this->entityClass);

        $repository
            ->expects(static::once())
            ->method('save');

        /** @var MockObject|ValidatorInterface $validator */
        $validator = $this->getMockBuilder(ValidatorInterface::class)->getMock();

        /** @var MockObject|UserRepository|ConstraintViolationListInterface $repository */
        $constraintViolationList = $this->getMockBuilder(ConstraintViolationListInterface::class)->getMock();

        $constraintViolationList
            ->expects(static::exactly(2))
            ->method('count')
            ->willReturn(0);

        $validator
            ->expects(static::exactly(2))
            ->method('validate')
            ->willReturn($constraintViolationList);

        /** @var MockObject|RestDtoInterface $dto */
        $dto = $this->getDtoMockBuilder()->getMock();

        $dto
            ->expects(static::once())
            ->method('update');

        $this->resource
            ->setRepository($repository)
            ->setValidator($validator)
            ->create($dto);
    }

    /**
     * @throws Throwable
     */
    public function testThatUpdateMethodThrowsAnExceptionIfEntityWasNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Not found');

        /** @var MockObject|UserRepository $repository */
        $repository = $this->getRepositoryMockBuilder()->disableOriginalConstructor()->getMock();

        $repository
            ->expects(static::once())
            ->method('find')
            ->with('some id')
            ->willReturn(null);

        $dto = new $this->dtoClass();

        $this->resource
            ->setRepository($repository)
            ->update('some id', $dto);
    }

    /**
     * @throws Throwable
     */
    public function testThatUpdateCallsExpectedRepositoryMethod(): void
    {
        $dto = new $this->dtoClass();
        $entity = new $this->entityClass();

        $methods = [
            'setUsername' => 'username',
            'setFirstName' => 'first name',
            'setLastName' => 'last name',
            'setEmail' => 'test@test.com',
        ];

        foreach ($methods as $method => $value) {
            $dto->{$method}($value);
            $entity->{$method}($value);
        }

        /** @var MockObject|UserRepository $repository */
        $repository = $this->getRepositoryMockBuilder()->disableOriginalConstructor()->getMock();

        $repository
            ->expects(static::exactly(2))
            ->method('find')
            ->with('some id')
            ->willReturn($entity);

        $repository
            ->expects(static::once())
            ->method('save')
            ->with($entity);

        $this->resource
            ->setRepository($repository)
            ->update('some id', $dto);
    }

    /**
     * @throws Throwable
     */
    public function testThatDeleteMethodCallsExpectedRepositoryMethod(): void
    {
        $entity = $this->getEntityMock();

        /** @var MockObject|UserRepository $repository */
        $repository = $this->getRepositoryMockBuilder()->disableOriginalConstructor()->getMock();

        $repository
            ->expects(static::once())
            ->method('find')
            ->with('some id')
            ->willReturn($entity);

        $repository
            ->expects(static::once())
            ->method('remove')
            ->with($entity);

        $this->resource->setRepository($repository);

        static::assertSame($entity, $this->resource->delete('some id'));
    }

    /**
     * @dataProvider dataProviderTestThatGetIdsCallsExpectedRepositoryMethodWithCorrectParameters
     *
     * @throws Throwable
     *
     * @testdox Test that `findIds` method is called with `$expectedArguments` when using `$arguments` arguments.
     */
    public function testThatGetIdsCallsExpectedRepositoryMethodWithCorrectParameters(
        StringableArrayObject $expectedArguments,
        StringableArrayObject $arguments
    ): void {
        /** @var MockObject|UserRepository $repository */
        $repository = $this->getRepositoryMockBuilder()->disableOriginalConstructor()->getMock();

        $repository
            ->expects(static::once())
            ->method('findIds')
            ->with(...$expectedArguments->getArrayCopy());

        $this->resource
            ->setRepository($repository)
            ->getIds(...$arguments->getArrayCopy());
    }

    /**
     * @throws Throwable
     */
    public function testThatSaveMethodThrowsAnExceptionWithInvalidEntity(): void
    {
        $this->expectException(ValidatorException::class);

        $entity = new $this->entityClass();

        /** @var MockObject|UserRepository $repository */
        $repository = $this->getRepositoryMockBuilder()->disableOriginalConstructor()->getMock();

        $repository
            ->expects(static::never())
            ->method('save')
            ->with($entity);

        $this->resource
            ->setRepository($repository)
            ->save($entity);
    }

    public function dataProviderTestThatCountCallsExpectedRepositoryMethodWithCorrectParameters(): Generator
    {
        yield [
            new StringableArrayObject([[], []]),
            new StringableArrayObject([null, null]),
        ];

        yield [
            new StringableArrayObject([['foo'], []]),
            new StringableArrayObject([['foo'], null]),
        ];

        yield [
            new StringableArrayObject([['foo'], ['bar']]),
            new StringableArrayObject([['foo'], ['bar']]),
        ];
    }

    public function dataProviderTestThatFindCallsExpectedRepositoryMethodWithCorrectParameters(): Generator
    {
        yield [
            new StringableArrayObject([[], [], 0, 0, []]),
            new StringableArrayObject([null, null, null, null, null]),
        ];

        yield [
            new StringableArrayObject([['foo'], [], 0, 0, []]),
            new StringableArrayObject([['foo'], null, null, null, null]),
        ];

        yield [
            new StringableArrayObject([['foo'], ['foo'], 0, 0, []]),
            new StringableArrayObject([['foo'], ['foo'], null, null, null]),
        ];

        yield [
            new StringableArrayObject([['foo'], ['foo'], 1, 0, []]),
            new StringableArrayObject([['foo'], ['foo'], 1, null, null]),
        ];

        yield [
            new StringableArrayObject([['foo'], ['foo'], 1, 2, []]),
            new StringableArrayObject([['foo'], ['foo'], 1, 2, null]),
        ];

        yield [
            new StringableArrayObject([['foo'], ['foo'], 1, 2, ['foo']]),
            new StringableArrayObject([['foo'], ['foo'], 1, 2, ['foo']]),
        ];
    }

    public function dataProviderTestThatFindOneByCallsExpectedRepositoryMethodWithCorrectParameters(): Generator
    {
        yield [
            new StringableArrayObject([[], []]),
            new StringableArrayObject([[], null]),
        ];

        yield [
            new StringableArrayObject([['foo'], []]),
            new StringableArrayObject([['foo'], null]),
        ];

        yield [
            new StringableArrayObject([['foo'], ['bar']]),
            new StringableArrayObject([['foo'], ['bar']]),
        ];
    }

    public function dataProviderTestThatGetIdsCallsExpectedRepositoryMethodWithCorrectParameters(): Generator
    {
        yield [
            new StringableArrayObject([[], []]),
            new StringableArrayObject([null, null]),
        ];

        yield [
            new StringableArrayObject([['foo'], []]),
            new StringableArrayObject([['foo'], null]),
        ];

        yield [
            new StringableArrayObject([['foo'], ['bar']]),
            new StringableArrayObject([['foo'], ['bar']]),
        ];
    }

    /**
     * @return EntityManagerInterface|object
     */
    private static function getEntityManager(): EntityManagerInterface
    {
        return static::$container->get('doctrine')->getManager();
    }

    private function getRepositoryMockBuilder(): MockBuilder
    {
        return $this
            ->getMockBuilder(UserRepository::class)
            ->setConstructorArgs([static::getEntityManager(), new ClassMetadata($this->entityClass)]);
    }

    /**
     * @return MockObject|UserEntity
     *
     * @throws Throwable
     */
    private function getEntityMock(): MockObject
    {
        return $this->createMock($this->entityClass);
    }

    private function getDtoMockBuilder(): MockBuilder
    {
        return $this->getMockBuilder($this->dtoClass);
    }
}
