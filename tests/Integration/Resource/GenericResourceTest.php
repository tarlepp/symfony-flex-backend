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
use App\Tests\Utils\StringableArrayObject;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\Mapping\ClassMetadata;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;
use UnexpectedValueException;

/**
 * @package App\Tests\Integration\Resource
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class GenericResourceTest extends KernelTestCase
{
    #[TestDox('Test without DTO class `getDtoClass` method call throws an exception')]
    public function testThatGetDtoClassThrowsAnExceptionWithoutDto(): void
    {
        [$resource] = $this->getResourceAndRepository();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessageMatches('/DTO class not specified for \'.*\' resource/');

        $resource->setDtoClass('')->getDtoClass();
    }

    #[TestDox('Test that `getDtoClass` returns expected value when custom DTO is set')]
    public function testThatGetDtoClassReturnsExpectedDto(): void
    {
        [$resource] = $this->getResourceAndRepository();

        $resource->setDtoClass('foobar');

        self::assertSame('foobar', $resource->getDtoClass());
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that calling `getEntityName` method calls expected repository methods and returns expected value')]
    public function testThatGetEntityNameCallsExpectedRepositoryMethod(): void
    {
        [$resource, $repository] = $this->getResourceAndRepository();

        $repository
            ->expects($this->once())
            ->method('getEntityName')
            ->willReturn('someEntity');

        self::assertSame('someEntity', $resource->getEntityName());
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that calling `getReference` method calls expected repository methods and returns expected value')]
    public function testThatGetReferenceCallsExpectedRepositoryMethod(): void
    {
        [$resource, $repository] = $this->getResourceAndRepository();

        $entity = new UserEntity();

        $repository
            ->expects($this->once())
            ->method('getReference')
            ->with($entity->getId())
            ->willReturn($entity);

        self::assertSame($entity, $resource->getReference($entity->getId()));
    }

    /**
     * @throws Throwable
     */
    #[TestDox(
        'Test that calling `getAssociations` method calls expected repository methods and returns expected value'
    )]
    public function testThatGetAssociationsCallsExpectedRepositoryMethod(): void
    {
        [$resource, $repository] = $this->getResourceAndRepository();

        $repository
            ->expects($this->once())
            ->method('getAssociations')
            ->willReturn([
                'entity1' => 'foo',
                'entity2' => 'bar',
            ]);

        self::assertSame(['entity1', 'entity2'], $resource->getAssociations());
    }

    /**
     * @throws Throwable
     */
    #[TestDox(
        'Test that calling `getDtoForEntity` method calls expected repository methods and returns expected value'
    )]
    public function testThatGetDtoForEntityCallsExpectedRepositoryMethod(): void
    {
        [$resource, $repository] = $this->getResourceAndRepository();

        $entity = new UserEntity();

        $repository
            ->expects($this->once())
            ->method('find')
            ->with($entity->getId())
            ->willReturn($entity);

        $newDto = $resource->getDtoForEntity($entity->getId(), UserDto::class, new UserDto());

        self::assertInstanceOf(UserDto::class, $newDto);
        self::assertSame($entity->getId(), $newDto->getId());
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that calling `getDtoForEntity` method throws an exception if entity is not found')]
    public function testThatGetDtoForEntityThrowsAnExceptionIfEntityWasNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Not found');

        [$resource, $repository] = $this->getResourceAndRepository();

        $repository
            ->expects($this->once())
            ->method('find')
            ->with('some id')
            ->willReturn(null);

        $resource->getDtoForEntity('some id', UserDto::class, new UserDto());
    }

    /**
     * @phpstan-param StringableArrayObject<mixed> $expectedArguments
     * @phpstan-param StringableArrayObject<mixed> $arguments
     * @psalm-param StringableArrayObject $expectedArguments
     * @psalm-param StringableArrayObject $arguments
     *
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatFindCallsExpectedRepositoryMethodWithCorrectParameters')]
    #[TestDox(
        'Test that `findByAdvanced` method is called with `$expectedArguments` when using `$arguments` arguments'
    )]
    public function testThatFindCallsExpectedRepositoryMethodWithCorrectParameters(
        StringableArrayObject $expectedArguments,
        StringableArrayObject $arguments,
    ): void {
        [$resource, $repository] = $this->getResourceAndRepository();

        $results = [
            new UserEntity(),
            new UserEntity(),
            new UserEntity(),
        ];

        $repository
            ->expects($this->once())
            ->method('findByAdvanced')
            ->with(...$expectedArguments->getArrayCopy())
            ->willReturn($results);

        self::assertSame($results, $resource->find(...$arguments->getArrayCopy()));
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that calling `findOne` method calls expected repository methods and returns expected value')]
    public function testThatFindOneCallsExpectedRepositoryMethod(): void
    {
        [$resource, $repository] = $this->getResourceAndRepository();

        $entity = new UserEntity();

        $repository
            ->expects($this->once())
            ->method('findAdvanced')
            ->with($entity->getId())
            ->willReturn($entity);

        self::assertSame($entity, $resource->findOne($entity->getId()));
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that calling `findOne` method returns null if entity is not found and exception bit is not set')]
    public function testThatFindOneReturnsNullIfEntityIsNotFound(): void
    {
        [$resource, $repository] = $this->getResourceAndRepository();

        $repository
            ->expects($this->once())
            ->method('findAdvanced')
            ->with('some id')
            ->willReturn(null);

        self::assertNull($resource->findOne('some id'));
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that calling `findOne` method throws an exception if entity is not found and exception bit is set')]
    public function testThatFindOneThrowsAnExceptionIfEntityWasNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Not found');

        [$resource, $repository] = $this->getResourceAndRepository();

        $repository
            ->expects($this->once())
            ->method('findAdvanced')
            ->with('some id')
            ->willReturn(null);

        $resource->findOne('some id', true);
    }

    /**
     * @phpstan-param StringableArrayObject<mixed> $expectedArguments
     * @phpstan-param StringableArrayObject<mixed> $arguments
     * @psalm-param StringableArrayObject $expectedArguments
     * @psalm-param StringableArrayObject $arguments
     *
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatFindOneByCallsExpectedRepositoryMethodWithCorrectParameters')]
    #[TestDox('Test that `findOneBy` method is called with `$expectedArguments` when using `$arguments` arguments')]
    public function testThatFindOneByCallsExpectedRepositoryMethodWithCorrectParameters(
        StringableArrayObject $expectedArguments,
        StringableArrayObject $arguments,
    ): void {
        [$resource, $repository] = $this->getResourceAndRepository();

        $entity = new UserEntity();

        $repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(...$expectedArguments->getArrayCopy())
            ->willReturn($entity);

        self::assertSame($entity, $resource->findOneBy(...$arguments->getArrayCopy()));
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `findOneBy` method throws an exception if entity not found and exception bit is set')]
    public function testThatFindOneByThrowsAnExceptionIfEntityWasNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Not found');

        [$resource, $repository] = $this->getResourceAndRepository();

        $repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with([], [])
            ->willReturn(null);

        $resource->findOneBy([], throwExceptionIfNotFound: true);
    }

    /**
     * @throws Throwable
     */
    #[TestDox(
        'Test that `findOneBy` method doesn\'t throw an exception if entity not found and exception bit is not set'
    )]
    public function testThatFindOneByDoesNotThrowAnExceptionIfEntityWasNotFound(): void
    {
        [$resource, $repository] = $this->getResourceAndRepository();

        $repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with([], [])
            ->willReturn(null);

        self::assertNull($resource->findOneBy([]));
    }

    /**
     * @throws Throwable
     */
    #[TestDox("Test that `findOneBy` method doesn't throw an exception if entity is found and exception bit is set")]
    public function testThatFindOneByWontThrowAnExceptionIfEntityWasFound(): void
    {
        [$resource, $repository] = $this->getResourceAndRepository();

        $entity = new UserEntity();

        $repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with([], [])
            ->willReturn($entity);

        self::assertSame($entity, $resource->findOneBy([], throwExceptionIfNotFound: true));
    }

    /**
     * @phpstan-param StringableArrayObject<mixed> $expectedArguments
     * @phpstan-param StringableArrayObject<mixed> $arguments
     * @psalm-param StringableArrayObject $expectedArguments
     * @psalm-param StringableArrayObject $arguments
     *
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatCountCallsExpectedRepositoryMethodWithCorrectParameters')]
    #[TestDox(
        'Test that `countAdvanced` method is called with `$expectedArguments` when using `$arguments` arguments.'
    )]
    public function testThatCountCallsExpectedRepositoryMethodWithCorrectParameters(
        StringableArrayObject $expectedArguments,
        StringableArrayObject $arguments,
    ): void {
        [$resource, $repository] = $this->getResourceAndRepository();

        $repository
            ->expects($this->once())
            ->method('countAdvanced')
            ->with(...$expectedArguments->getArrayCopy())
            ->willReturn(10);

        self::assertSame(10, $resource->count(...$arguments->getArrayCopy()));
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that calling `save` method calls expected repository methods and returns expected value')]
    public function testThatSaveMethodCallsExpectedRepositoryMethod(): void
    {
        [$resource, $repository] = $this->getResourceAndRepository();

        $entity = new UserEntity();

        $repository
            ->expects($this->once())
            ->method('save')
            ->with($entity);

        self::assertSame($entity, $resource->save($entity, skipValidation: true));
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that calling `save` method throws an validation exception with invalid entity')]
    public function testThatSaveMethodThrowsValidationException(): void
    {
        $this->expectException(ValidatorException::class);

        [$resource, $repository] = $this->getResourceAndRepository();

        $entity = new UserEntity();

        $repository
            ->expects(self::never())
            ->method('save');

        $resource->save($entity);
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that calling `create` method throws an validation exception with invalid DTO')]
    public function testThatCreateMethodThrowsAnErrorWithInvalidDto(): void
    {
        $this->expectException(ValidatorException::class);

        [$resource, $repository] = $this->getResourceAndRepository();

        $repository
            ->expects($this->once())
            ->method('getEntityName')
            ->willReturn(UserEntity::class);

        $resource->create(new UserDto());
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that calling `create` method calls expected repository and dto class methods')]
    public function testThatCreateMethodCallsExpectedMethods(): void
    {
        [$resource, $repository] = $this->getResourceAndRepository();

        $repository
            ->expects($this->once())
            ->method('getEntityName')
            ->willReturn(UserEntity::class);

        $repository
            ->expects($this->once())
            ->method('save');

        $validator = $this->getMockBuilder(ValidatorInterface::class)->getMock();
        $constraintViolationList = $this->getMockBuilder(ConstraintViolationListInterface::class)->getMock();

        $constraintViolationList
            ->expects($this->exactly(2))
            ->method('count')
            ->willReturn(0);

        $validator
            ->expects($this->exactly(2))
            ->method('validate')
            ->willReturn($constraintViolationList);

        $dto = $this->getMockBuilder(UserDto::class)->getMock();

        $dto->expects($this->once())
            ->method('update');

        $resource
            ->setValidator($validator)
            ->create($dto);
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `update` method throws an exception if entity was not found')]
    public function testThatUpdateMethodThrowsAnExceptionIfEntityWasNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Not found');

        [$resource, $repository] = $this->getResourceAndRepository();

        $repository
            ->expects($this->once())
            ->method('find')
            ->with('some id')
            ->willReturn(null);

        $resource->update('some id', new UserDto());
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `update` method calls expected repository methods')]
    public function testThatUpdateCallsExpectedRepositoryMethod(): void
    {
        [$resource, $repository] = $this->getResourceAndRepository();

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

        $repository
            ->expects($this->exactly(2))
            ->method('find')
            ->with('some id')
            ->willReturn($entity);

        $repository
            ->expects($this->once())
            ->method('save')
            ->with($dto->update($entity));

        $resource->update('some id', $dto);
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that calling `delete` method calls expected repository methods and returns expected value')]
    public function testThatDeleteMethodCallsExpectedRepositoryMethod(): void
    {
        [$resource, $repository] = $this->getResourceAndRepository();

        $entity = new UserEntity();

        $repository
            ->expects($this->once())
            ->method('find')
            ->with('some id')
            ->willReturn($entity);

        $repository
            ->expects($this->once())
            ->method('remove')
            ->with($entity);

        self::assertSame($entity, $resource->delete('some id'));
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that calling `delete` method throws an exception if entity was not found')]
    public function testThatDeleteMethodThrowsAnExceptionIfEntityWasNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Not found');

        [$resource, $repository] = $this->getResourceAndRepository();

        $repository
            ->expects($this->once())
            ->method('find')
            ->with('some id')
            ->willReturn(null);

        $repository
            ->expects(self::never())
            ->method('remove');

        $resource->delete('some id');
    }

    /**
     * @phpstan-param StringableArrayObject<mixed> $expectedArguments
     * @phpstan-param StringableArrayObject<mixed> $arguments
     * @psalm-param StringableArrayObject $expectedArguments
     * @psalm-param StringableArrayObject $arguments
     *
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatGetIdsCallsExpectedRepositoryMethodWithCorrectParameters')]
    #[TestDox('Test that `findIds` method is called with `$expectedArguments` when using `$arguments` arguments.')]
    public function testThatGetIdsCallsExpectedRepositoryMethodWithCorrectParameters(
        StringableArrayObject $expectedArguments,
        StringableArrayObject $arguments,
    ): void {
        [$resource, $repository] = $this->getResourceAndRepository();

        $repository
            ->expects($this->once())
            ->method('findIds')
            ->with(...$expectedArguments->getArrayCopy());

        $resource->getIds(...$arguments->getArrayCopy());
    }

    /**
     * @psalm-return Generator<array{0: StringableArrayObject, 1: StringableArrayObject}>
     * @phpstan-return Generator<array{0: StringableArrayObject<mixed>, 1: StringableArrayObject<mixed>}>
     */
    public static function dataProviderTestThatCountCallsExpectedRepositoryMethodWithCorrectParameters(): Generator
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
    public static function dataProviderTestThatFindCallsExpectedRepositoryMethodWithCorrectParameters(): Generator
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
    public static function dataProviderTestThatFindOneByCallsExpectedRepositoryMethodWithCorrectParameters(): Generator
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
    public static function dataProviderTestThatGetIdsCallsExpectedRepositoryMethodWithCorrectParameters(): Generator
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
     * @throws Throwable
     *
     * @phpstan-return array{0: UserResource, 1: MockObject&UserRepository}
     */
    private function getResourceAndRepository(): array
    {
        /** @var Registry $doctrine */
        $doctrine = self::getContainer()->get('doctrine');

        $repository = $this
            ->getMockBuilder(UserRepository::class)
            ->setConstructorArgs([$doctrine->getManager(), new ClassMetadata(UserEntity::class)])
            ->disableOriginalConstructor()
            ->getMock();

        $roleHierarchy = $this->getMockBuilder(RoleHierarchyInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $validator = self::getContainer()->get(ValidatorInterface::class);

        $resource = new UserResource($repository, new RolesService($roleHierarchy));
        $resource->setValidator($validator);

        return [
            $resource,
            $repository,
        ];
    }
}
