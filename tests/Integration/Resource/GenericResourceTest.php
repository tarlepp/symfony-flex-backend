<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Resource/GenericResourceTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Resource;

use App\DTO\User\User as UserDto;
use App\Entity\User as UserEntity;
use App\Repository\UserRepository;
use App\Resource\UserResource;
use App\Security\RolesService;
use App\Utils\Tests\StringableArrayObject;
use Doctrine\Bundle\DoctrineBundle\Registry;
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

/**
 * Class GenericResourceTest
 *
 * @package App\Tests\Integration\Resource
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class GenericResourceTest extends KernelTestCase
{
    private ?UserResource $resource = null;
    private ?MockObject $repository = null;

    /**
     * @throws Throwable
     */
    protected function setUp(): void
    {
        parent::setUp();

        $repository = $this->getRepositoryMockBuilder()->disableOriginalConstructor()->getMock();

        $this->resource = new UserResource($repository, new RolesService([]));
        $this->resource->setValidator(static::getContainer()->get(ValidatorInterface::class));
        $this->repository = $repository;
    }

    /**
     * @testdox Test without DTO class `getDtoClass` method call throws an exception
     */
    public function testThatGetDtoClassThrowsAnExceptionWithoutDto(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessageMatches('/DTO class not specified for \'.*\' resource/');

        $this->getResource()->setDtoClass('')->getDtoClass();
    }

    /**
     * @testdox Test that `getDtoClass` returns expected value when custom DTO is set
     */
    public function testThatGetDtoClassReturnsExpectedDto(): void
    {
        $resource = $this->getResource()->setDtoClass('foobar');

        static::assertSame('foobar', $resource->getDtoClass());
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that calling `getEntityName` method calls expected repository methods and returns expected value
     */
    public function testThatGetEntityNameCallsExpectedRepositoryMethod(): void
    {
        $this->getRepository()
            ->expects(static::once())
            ->method('getEntityName')
            ->willReturn('someEntity');

        static::assertSame('someEntity', $this->getResource()->getEntityName());
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that calling `getReference` method calls expected repository methods and returns expected value
     */
    public function testThatGetReferenceCallsExpectedRepositoryMethod(): void
    {
        $entity = new UserEntity();

        $this->getRepository()
            ->expects(static::once())
            ->method('getReference')
            ->with($entity->getId())
            ->willReturn($entity);

        static::assertSame($entity, $this->getResource()->getReference($entity->getId()));
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that calling `getAssociations` method calls expected repository methods and returns expected value
     */
    public function testThatGetAssociationsCallsExpectedRepositoryMethod(): void
    {
        $this->getRepository()
            ->expects(static::once())
            ->method('getAssociations')
            ->willReturn([
                'entity1' => 'foo',
                'entity2' => 'bar',
            ]);

        static::assertSame(['entity1', 'entity2'], $this->getResource()->getAssociations());
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that calling `getDtoForEntity` method calls expected repository methods and returns expected value
     */
    public function testThatGetDtoForEntityCallsExpectedRepositoryMethod(): void
    {
        $entity = new UserEntity();

        $this->getRepository()
            ->expects(static::once())
            ->method('find')
            ->with($entity->getId())
            ->willReturn($entity);

        $newDto = $this->getResource()->getDtoForEntity($entity->getId(), UserDto::class, new UserDto());

        static::assertInstanceOf(UserDto::class, $newDto);
        static::assertSame($entity->getId(), $newDto->getId());
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that calling `getDtoForEntity` method throws an exception if entity is not found
     */
    public function testThatGetDtoForEntityThrowsAnExceptionIfEntityWasNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Not found');

        $this->getRepository()
            ->expects(static::once())
            ->method('find')
            ->with('some id')
            ->willReturn(null);

        $this->getResource()->getDtoForEntity('some id', UserDto::class, new UserDto());
    }

    /**
     * @dataProvider dataProviderTestThatFindCallsExpectedRepositoryMethodWithCorrectParameters
     *
     * @phpstan-param StringableArrayObject<mixed> $expectedArguments
     * @phpstan-param StringableArrayObject<mixed> $arguments
     * @psalm-param StringableArrayObject $expectedArguments
     * @psalm-param StringableArrayObject $arguments
     *
     * @throws Throwable
     *
     * @testdox Test that `findByAdvanced` method is called with `$expectedArguments` when using `$arguments` arguments
     */
    public function testThatFindCallsExpectedRepositoryMethodWithCorrectParameters(
        StringableArrayObject $expectedArguments,
        StringableArrayObject $arguments,
    ): void {
        $results = [
            new UserEntity(),
            new UserEntity(),
            new UserEntity(),
        ];

        $this->getRepository()
            ->expects(static::once())
            ->method('findByAdvanced')
            ->with(...$expectedArguments->getArrayCopy())
            ->willReturn($results);

        static::assertSame($results, $this->getResource()->find(...$arguments->getArrayCopy()));
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that calling `findOne` method calls expected repository methods and returns expected value
     */
    public function testThatFindOneCallsExpectedRepositoryMethod(): void
    {
        $entity = new UserEntity();

        $this->getRepository()
            ->expects(static::once())
            ->method('findAdvanced')
            ->with($entity->getId())
            ->willReturn($entity);

        static::assertSame($entity, $this->getResource()->findOne($entity->getId()));
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that calling `findOne` method returns null if entity is not found and exception bit is not set
     */
    public function testThatFindOneReturnsNullIfEntityIsNotFound(): void
    {
        $this->getRepository()
            ->expects(static::once())
            ->method('findAdvanced')
            ->with('some id')
            ->willReturn(null);

        static::assertNull($this->getResource()->findOne('some id'));
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that calling `findOne` method throws an exception if entity is not found and exception bit is set
     */
    public function testThatFindOneThrowsAnExceptionIfEntityWasNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Not found');

        $this->getRepository()
            ->expects(static::once())
            ->method('findAdvanced')
            ->with('some id')
            ->willReturn(null);

        $this->getResource()->findOne('some id', true);
    }

    /**
     * @dataProvider dataProviderTestThatFindOneByCallsExpectedRepositoryMethodWithCorrectParameters
     *
     * @phpstan-param StringableArrayObject<mixed> $expectedArguments
     * @phpstan-param StringableArrayObject<mixed> $arguments
     * @psalm-param StringableArrayObject $expectedArguments
     * @psalm-param StringableArrayObject $arguments
     *
     * @throws Throwable
     *
     * @testdox Test that `findOneBy` method is called with `$expectedArguments` when using `$arguments` arguments
     */
    public function testThatFindOneByCallsExpectedRepositoryMethodWithCorrectParameters(
        StringableArrayObject $expectedArguments,
        StringableArrayObject $arguments,
    ): void {
        $entity = new UserEntity();

        $this->getRepository()
            ->expects(static::once())
            ->method('findOneBy')
            ->with(...$expectedArguments->getArrayCopy())
            ->willReturn($entity);

        static::assertSame($entity, $this->getResource()->findOneBy(...$arguments->getArrayCopy()));
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `findOneBy` method throws an exception if entity not found and exception bit is set
     */
    public function testThatFindOneByThrowsAnExceptionIfEntityWasNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Not found');

        $this->getRepository()
            ->expects(static::once())
            ->method('findOneBy')
            ->with([], [])
            ->willReturn(null);

        $this->getResource()->findOneBy([], throwExceptionIfNotFound: true);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `findOneBy` method doesn't throw an exception if entity not found and exception bit is not set
     */
    public function testThatFindOneByDoesNotThrowAnExceptionIfEntityWasNotFound(): void
    {
        $this->getRepository()
            ->expects(static::once())
            ->method('findOneBy')
            ->with([], [])
            ->willReturn(null);

        static::assertNull($this->getResource()->findOneBy([]));
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `findOneBy` method doesn't throw an exception if entity is found and exception bit is set
     */
    public function testThatFindOneByWontThrowAnExceptionIfEntityWasFound(): void
    {
        $entity = new UserEntity();

        $this->getRepository()
            ->expects(static::once())
            ->method('findOneBy')
            ->with([], [])
            ->willReturn($entity);

        static::assertSame($entity, $this->getResource()->findOneBy([], throwExceptionIfNotFound: true));
    }

    /**
     * @dataProvider dataProviderTestThatCountCallsExpectedRepositoryMethodWithCorrectParameters
     *
     * @phpstan-param StringableArrayObject<mixed> $expectedArguments
     * @phpstan-param StringableArrayObject<mixed> $arguments
     * @psalm-param StringableArrayObject $expectedArguments
     * @psalm-param StringableArrayObject $arguments
     *
     * @throws Throwable
     *
     * @testdox Test that `countAdvanced` method is called with `$expectedArguments` when using `$arguments` arguments.
     */
    public function testThatCountCallsExpectedRepositoryMethodWithCorrectParameters(
        StringableArrayObject $expectedArguments,
        StringableArrayObject $arguments,
    ): void {
        $this->getRepository()
            ->expects(static::once())
            ->method('countAdvanced')
            ->with(...$expectedArguments->getArrayCopy())
            ->willReturn(10);

        static::assertSame(10, $this->getResource()->count(...$arguments->getArrayCopy()));
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that calling `save` method calls expected repository methods and returns expected value
     */
    public function testThatSaveMethodCallsExpectedRepositoryMethod(): void
    {
        $entity = new UserEntity();

        $this->getRepository()
            ->expects(static::once())
            ->method('save')
            ->with($entity);

        static::assertSame($entity, $this->getResource()->save($entity, skipValidation: true));
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that calling `save` method throws an validation exception with invalid entity
     */
    public function testThatSaveMethodThrowsValidationException(): void
    {
        $this->expectException(ValidatorException::class);

        $entity = new UserEntity();

        $this->getRepository()
            ->expects(static::never())
            ->method('save');

        $this->getResource()->save($entity);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that calling `create` method throws an validation exception with invalid DTO
     */
    public function testThatCreateMethodThrowsAnErrorWithInvalidDto(): void
    {
        $this->expectException(ValidatorException::class);

        $this->getRepository()
            ->expects(static::once())
            ->method('getEntityName')
            ->willReturn(UserEntity::class);

        $this->getResource()->create(new UserDto());
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that calling `create` method calls expected repository and dto class methods
     */
    public function testThatCreateMethodCallsExpectedMethods(): void
    {
        $repository = $this->getRepository();

        $repository
            ->expects(static::once())
            ->method('getEntityName')
            ->willReturn(UserEntity::class);

        $repository
            ->expects(static::once())
            ->method('save');

        $validator = $this->getMockBuilder(ValidatorInterface::class)->getMock();
        $constraintViolationList = $this->getMockBuilder(ConstraintViolationListInterface::class)->getMock();

        $constraintViolationList
            ->expects(static::exactly(2))
            ->method('count')
            ->willReturn(0);

        $validator
            ->expects(static::exactly(2))
            ->method('validate')
            ->willReturn($constraintViolationList);

        $dto = $this->getMockBuilder(UserDto::class)->getMock();

        $dto->expects(static::once())
            ->method('update');

        $this->getResource()
            ->setValidator($validator)
            ->create($dto);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `update` method throws an exception if entity was not found
     */
    public function testThatUpdateMethodThrowsAnExceptionIfEntityWasNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Not found');

        $this->getRepository()
            ->expects(static::once())
            ->method('find')
            ->with('some id')
            ->willReturn(null);

        $this->getResource()->update('some id', new UserDto());
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `update` method calls expected repository methods
     */
    public function testThatUpdateCallsExpectedRepositoryMethod(): void
    {
        $dto = new UserDto();
        $entity = new UserEntity();

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

        $repository = $this->getRepository();

        $repository
            ->expects(static::exactly(2))
            ->method('find')
            ->with('some id')
            ->willReturn($entity);

        $repository
            ->expects(static::once())
            ->method('save')
            ->with($dto->update($entity));

        $this->getResource()->update('some id', $dto);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that calling `delete` method calls expected repository methods and returns expected value
     */
    public function testThatDeleteMethodCallsExpectedRepositoryMethod(): void
    {
        $entity = new UserEntity();

        $repository = $this->getRepository();

        $repository
            ->expects(static::once())
            ->method('find')
            ->with('some id')
            ->willReturn($entity);

        $repository
            ->expects(static::once())
            ->method('remove')
            ->with($entity);

        static::assertSame($entity, $this->getResource()->delete('some id'));
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that calling `delete` method throws an exception if entity was not found
     */
    public function testThatDeleteMethodThrowsAnExceptionIfEntityWasNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Not found');

        $repository = $this->getRepository();

        $repository
            ->expects(static::once())
            ->method('find')
            ->with('some id')
            ->willReturn(null);

        $repository
            ->expects(static::never())
            ->method('remove');

        $this->getResource()->delete('some id');
    }

    /**
     * @dataProvider dataProviderTestThatGetIdsCallsExpectedRepositoryMethodWithCorrectParameters
     *
     * @phpstan-param StringableArrayObject<mixed> $expectedArguments
     * @phpstan-param StringableArrayObject<mixed> $arguments
     * @psalm-param StringableArrayObject $expectedArguments
     * @psalm-param StringableArrayObject $arguments
     *
     * @throws Throwable
     *
     * @testdox Test that `findIds` method is called with `$expectedArguments` when using `$arguments` arguments.
     */
    public function testThatGetIdsCallsExpectedRepositoryMethodWithCorrectParameters(
        StringableArrayObject $expectedArguments,
        StringableArrayObject $arguments,
    ): void {
        $this->getRepository()
            ->expects(static::once())
            ->method('findIds')
            ->with(...$expectedArguments->getArrayCopy());

        $this->getResource()->getIds(...$arguments->getArrayCopy());
    }

    /**
     * @psalm-return Generator<array{0: StringableArrayObject, 1: StringableArrayObject}>
     * @phpstan-return Generator<array{0: StringableArrayObject<mixed>, 1: StringableArrayObject<mixed>}>
     */
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

    /**
     * @psalm-return Generator<array{0: StringableArrayObject, 1: StringableArrayObject}>
     * @phpstan-return Generator<array{0: StringableArrayObject<mixed>, 1: StringableArrayObject<mixed>}>
     */
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

    /**
     * @psalm-return Generator<array{0: StringableArrayObject, 1: StringableArrayObject}>
     * @phpstan-return Generator<array{0: StringableArrayObject<mixed>, 1: StringableArrayObject<mixed>}>
     */
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

    /**
     * @psalm-return Generator<array{0: StringableArrayObject, 1: StringableArrayObject}>
     * @phpstan-return Generator<array{0: StringableArrayObject<mixed>, 1: StringableArrayObject<mixed>}>
     */
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

    protected function getResource(): UserResource
    {
        return $this->resource ?? throw new UnexpectedValueException('Resource not found...');
    }

    protected function getRepository(): MockObject
    {
        return !$this->repository instanceof MockObject
            ? throw new UnexpectedValueException('Repository not found...')
            : $this->repository;
    }

    /**
     * @return MockBuilder<UserRepository>
     */
    private function getRepositoryMockBuilder(): MockBuilder
    {
        /** @var Registry $doctrine */
        $doctrine = static::getContainer()->get('doctrine');

        if (method_exists($doctrine, 'getManager')
            && ($doctrine->getManager() instanceof EntityManagerInterface)
        ) {
            return $this
                ->getMockBuilder(UserRepository::class)
                ->setConstructorArgs([$doctrine->getManager(), new ClassMetadata(UserEntity::class)]);
        }

        throw new UnexpectedValueException('....');
    }
}
