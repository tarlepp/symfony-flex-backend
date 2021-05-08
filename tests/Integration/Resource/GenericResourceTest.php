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
use Doctrine\ORM\Mapping\ClassMetadata;
use Generator;
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
    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel();
    }

    /**
     * @testdox Test without DTO class `getDtoClass` method call throws an exception
     */
    public function testThatGetDtoClassThrowsAnExceptionWithoutDto(): void
    {
        [, $userResource] = $this->getMocks();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessageMatches('/DTO class not specified for \'.*\' resource/');

        $userResource->setDtoClass('')->getDtoClass();
    }

    /**
     * @testdox Test that `getDtoClass` returns expected value when custom DTO is set
     */
    public function testThatGetDtoClassReturnsExpectedDto(): void
    {
        [, $userResource] = $this->getMocks();

        $resource = $userResource->setDtoClass('foobar');

        static::assertSame('foobar', $resource->getDtoClass());
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that calling `getEntityName` method calls expected repository methods and returns expected value
     */
    public function testThatGetEntityNameCallsExpectedRepositoryMethod(): void
    {
        [$userRepositoryMock, $userResource] = $this->getMocks();

        $userRepositoryMock
            ->expects(static::once())
            ->method('getEntityName')
            ->willReturn('someEntity');

        static::assertSame('someEntity', $userResource->getEntityName());
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that calling `getReference` method calls expected repository methods and returns expected value
     */
    public function testThatGetReferenceCallsExpectedRepositoryMethod(): void
    {
        $entity = new UserEntity();

        [$userRepositoryMock, $userResource] = $this->getMocks();

        $userRepositoryMock
            ->expects(static::once())
            ->method('getReference')
            ->with($entity->getId())
            ->willReturn($entity);

        static::assertSame($entity, $userResource->getReference($entity->getId()));
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that calling `getAssociations` method calls expected repository methods and returns expected value
     */
    public function testThatGetAssociationsCallsExpectedRepositoryMethod(): void
    {
        [$userRepositoryMock, $userResource] = $this->getMocks();

        $userRepositoryMock
            ->expects(static::once())
            ->method('getAssociations')
            ->willReturn([
                'entity1' => 'foo',
                'entity2' => 'bar',
            ]);

        static::assertSame(['entity1', 'entity2'], $userResource->getAssociations());
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that calling `getDtoForEntity` method calls expected repository methods and returns expected value
     */
    public function testThatGetDtoForEntityCallsExpectedRepositoryMethod(): void
    {
        $entity = new UserEntity();

        [$userRepositoryMock, $userResource] = $this->getMocks();

        $userRepositoryMock
            ->expects(static::once())
            ->method('find')
            ->with($entity->getId())
            ->willReturn($entity);

        /** @var UserDto $newDto */
        $newDto = $userResource->getDtoForEntity($entity->getId(), UserDto::class, new UserDto());

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
        [$userRepositoryMock, $userResource] = $this->getMocks();

        $userRepositoryMock
            ->expects(static::once())
            ->method('find')
            ->with('some id')
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Not found');

        $userResource->getDtoForEntity('some id', UserDto::class, new UserDto());
    }

    /**
     * @dataProvider dataProviderTestThatFindCallsExpectedRepositoryMethodWithCorrectParameters
     *
     * @phpstan-param StringableArrayObject<array> $expectedArguments
     * @phpstan-param StringableArrayObject<array> $arguments
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

        [$userRepositoryMock, $userResource] = $this->getMocks();

        $userRepositoryMock
            ->expects(static::once())
            ->method('findByAdvanced')
            ->with(...$expectedArguments->getArrayCopy())
            ->willReturn($results);

        static::assertSame($results, $userResource->find(...$arguments->getArrayCopy()));
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that calling `findOne` method calls expected repository methods and returns expected value
     */
    public function testThatFindOneCallsExpectedRepositoryMethod(): void
    {
        $entity = new UserEntity();

        [$userRepositoryMock, $userResource] = $this->getMocks();

        $userRepositoryMock
            ->expects(static::once())
            ->method('findAdvanced')
            ->with($entity->getId())
            ->willReturn($entity);

        static::assertSame($entity, $userResource->findOne($entity->getId()));
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that calling `findOne` method returns null if entity is not found and exception bit is not set
     */
    public function testThatFindOneReturnsNullIfEntityIsNotFound(): void
    {
        [$userRepositoryMock, $userResource] = $this->getMocks();

        $userRepositoryMock
            ->expects(static::once())
            ->method('findAdvanced')
            ->with('some id')
            ->willReturn(null);

        static::assertNull($userResource->findOne('some id'));
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that calling `findOne` method throws an exception if entity is not found and exception bit is set
     */
    public function testThatFindOneThrowsAnExceptionIfEntityWasNotFound(): void
    {
        [$userRepositoryMock, $userResource] = $this->getMocks();

        $userRepositoryMock
            ->expects(static::once())
            ->method('findAdvanced')
            ->with('some id')
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Not found');

        $userResource->findOne('some id', true);
    }

    /**
     * @dataProvider dataProviderTestThatFindOneByCallsExpectedRepositoryMethodWithCorrectParameters
     *
     * @phpstan-param StringableArrayObject<array> $expectedArguments
     * @phpstan-param StringableArrayObject<array> $arguments
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

        [$userRepositoryMock, $userResource] = $this->getMocks();

        $userRepositoryMock
            ->expects(static::once())
            ->method('findOneBy')
            ->with(...$expectedArguments->getArrayCopy())
            ->willReturn($entity);

        static::assertSame($entity, $userResource->findOneBy(...$arguments->getArrayCopy()));
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `findOneBy` method throws an exception if entity not found and exception bit is set
     */
    public function testThatFindOneByThrowsAnExceptionIfEntityWasNotFound(): void
    {
        [$userRepositoryMock, $userResource] = $this->getMocks();

        $userRepositoryMock
            ->expects(static::once())
            ->method('findOneBy')
            ->with([], [])
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Not found');

        $userResource->findOneBy([], throwExceptionIfNotFound: true);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `findOneBy` method doesn't throw an exception if entity not found and exception bit is not set
     */
    public function testThatFindOneByDoesNotThrowAnExceptionIfEntityWasNotFound(): void
    {
        [$userRepositoryMock, $userResource] = $this->getMocks();

        $userRepositoryMock
            ->expects(static::once())
            ->method('findOneBy')
            ->with([], [])
            ->willReturn(null);

        static::assertNull($userResource->findOneBy([]));
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `findOneBy` method doesn't throw an exception if entity is found and exception bit is set
     */
    public function testThatFindOneByWontThrowAnExceptionIfEntityWasFound(): void
    {
        $entity = new UserEntity();

        [$userRepositoryMock, $userResource] = $this->getMocks();

        $userRepositoryMock
            ->expects(static::once())
            ->method('findOneBy')
            ->with([], [])
            ->willReturn($entity);

        static::assertSame($entity, $userResource->findOneBy([], throwExceptionIfNotFound: true));
    }

    /**
     * @dataProvider dataProviderTestThatCountCallsExpectedRepositoryMethodWithCorrectParameters
     *
     * @phpstan-param StringableArrayObject<array> $expectedArguments
     * @phpstan-param StringableArrayObject<array> $arguments
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
        [$userRepositoryMock, $userResource] = $this->getMocks();

        $userRepositoryMock
            ->expects(static::once())
            ->method('countAdvanced')
            ->with(...$expectedArguments->getArrayCopy())
            ->willReturn(10);

        static::assertSame(10, $userResource->count(...$arguments->getArrayCopy()));
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that calling `save` method calls expected repository methods and returns expected value
     */
    public function testThatSaveMethodCallsExpectedRepositoryMethod(): void
    {
        $entity = new UserEntity();

        [$userRepositoryMock, $userResource] = $this->getMocks();

        $userRepositoryMock
            ->expects(static::once())
            ->method('save')
            ->with($entity);

        static::assertSame($entity, $userResource->save($entity, skipValidation: true));
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that calling `save` method throws an validation exception with invalid entity
     */
    public function testThatSaveMethodThrowsValidationException(): void
    {
        $entity = new UserEntity();

        [$userRepositoryMock, $userResource] = $this->getMocks();

        $userRepositoryMock
            ->expects(static::never())
            ->method('save');

        $this->expectException(ValidatorException::class);

        $userResource->save($entity);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that calling `create` method throws an validation exception with invalid DTO
     */
    public function testThatCreateMethodThrowsAnErrorWithInvalidDto(): void
    {
        [$userRepositoryMock, $userResource] = $this->getMocks();

        $userRepositoryMock
            ->expects(static::once())
            ->method('getEntityName')
            ->willReturn(UserEntity::class);

        $this->expectException(ValidatorException::class);

        $userResource->create(new UserDto());
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that calling `create` method calls expected repository and dto class methods
     */
    public function testThatCreateMethodCallsExpectedMethods(): void
    {
        [$userRepositoryMock, $userResource] = $this->getMocks();
        $validatorMock = $this->getMockBuilder(ValidatorInterface::class)->getMock();
        $constraintViolationListMock = $this->getMockBuilder(ConstraintViolationListInterface::class)->getMock();
        $dtoMock = $this->getMockBuilder(UserDto::class)->getMock();

        $userRepositoryMock
            ->expects(static::once())
            ->method('getEntityName')
            ->willReturn(UserEntity::class);

        $userRepositoryMock
            ->expects(static::once())
            ->method('save');

        $constraintViolationListMock
            ->expects(static::exactly(2))
            ->method('count')
            ->willReturn(0);

        $validatorMock
            ->expects(static::exactly(2))
            ->method('validate')
            ->willReturn($constraintViolationListMock);

        $dtoMock->expects(static::once())
            ->method('update');

        $userResource
            ->setValidator($validatorMock)
            ->create($dtoMock);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `update` method throws an exception if entity was not found
     */
    public function testThatUpdateMethodThrowsAnExceptionIfEntityWasNotFound(): void
    {
        [$userRepositoryMock, $userResource] = $this->getMocks();

        $userRepositoryMock
            ->expects(static::once())
            ->method('find')
            ->with('some id')
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Not found');

        $userResource->update('some id', new UserDto());
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

        [$userRepositoryMock, $userResource] = $this->getMocks();

        $userRepositoryMock
            ->expects(static::exactly(2))
            ->method('find')
            ->with('some id')
            ->willReturn($entity);

        $userRepositoryMock
            ->expects(static::once())
            ->method('save')
            ->with($dto->update($entity));

        $userResource->update('some id', $dto);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that calling `delete` method calls expected repository methods and returns expected value
     */
    public function testThatDeleteMethodCallsExpectedRepositoryMethod(): void
    {
        $entity = new UserEntity();

        [$userRepositoryMock, $userResource] = $this->getMocks();

        $userRepositoryMock
            ->expects(static::once())
            ->method('find')
            ->with('some id')
            ->willReturn($entity);

        $userRepositoryMock
            ->expects(static::once())
            ->method('remove')
            ->with($entity);

        static::assertSame($entity, $userResource->delete('some id'));
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that calling `delete` method throws an exception if entity was not found
     */
    public function testThatDeleteMethodThrowsAnExceptionIfEntityWasNotFound(): void
    {
        [$userRepositoryMock, $userResource] = $this->getMocks();

        $userRepositoryMock
            ->expects(static::once())
            ->method('find')
            ->with('some id')
            ->willReturn(null);

        $userRepositoryMock
            ->expects(static::never())
            ->method('remove');

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Not found');

        $userResource->delete('some id');
    }

    /**
     * @dataProvider dataProviderTestThatGetIdsCallsExpectedRepositoryMethodWithCorrectParameters
     *
     * @phpstan-param StringableArrayObject<array> $expectedArguments
     * @phpstan-param StringableArrayObject<array> $arguments
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
        [$userRepositoryMock, $userResource] = $this->getMocks();

        $userRepositoryMock
            ->expects(static::once())
            ->method('findIds')
            ->with(...$expectedArguments->getArrayCopy());

        $userResource->getIds(...$arguments->getArrayCopy());
    }

    /**
     * @return Generator<array{0: StringableArrayObject, 1: StringableArrayObject}>
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
     * @return Generator<array{0: StringableArrayObject, 1: StringableArrayObject}>
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
     * @return Generator<array{0: StringableArrayObject, 1: StringableArrayObject}>
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
     * @return Generator<array{0: StringableArrayObject, 1: StringableArrayObject}>
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

    /**
     * @return array{
     *      0: \PHPUnit\Framework\MockObject\MockObject&UserRepository,
     *      1: UserResource,
     *  }
     */
    private function getMocks(): array
    {
        /** @var \Doctrine\Bundle\DoctrineBundle\Registry $doctrine */
        $doctrine = static::$container->get('doctrine');

        /** @var ValidatorInterface $validator */
        $validator = static::$container->get(ValidatorInterface::class);

        /** @var \PHPUnit\Framework\MockObject\MockObject&UserRepository $userRepositoryMock */
        $userRepositoryMock = $this
            ->getMockBuilder(UserRepository::class)
            ->setConstructorArgs([$doctrine->getManager(), new ClassMetadata(UserEntity::class)])
            ->disableOriginalConstructor()
            ->getMock();

        /** @var UserResource $userResource */
        $userResource = (new UserResource($userRepositoryMock, new RolesService([])))
            ->setValidator($validator);

        return [
            $userRepositoryMock,
            $userResource,
        ];
    }
}
