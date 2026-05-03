<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Form/DataTransformer/UserGroupTransformerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Form\DataTransformer;

use App\Entity\UserGroup;
use App\Form\DataTransformer\UserGroupTransformer;
use App\Resource\UserGroupResource;
use App\Tests\Utils\StringableArrayObject;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Throwable;

/**
 * @package App\Tests\Integration\Form\Console\DataTransformer
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class UserGroupTransformerTest extends KernelTestCase
{
    /**
     * @phpstan-param StringableArrayObject<mixed> $expected
     * @phpstan-param StringableArrayObject<mixed>|null $input
     * @psalm-param StringableArrayObject $expected
     * @psalm-param StringableArrayObject|null $input
     */
    #[DataProvider('dataProviderTestThatTransformReturnsExpected')]
    #[TestDox('Test that `transform` method returns `$expected` when using `$input` as input')]
    public function testThatTransformReturnsExpected(
        StringableArrayObject $expected,
        ?StringableArrayObject $input
    ): void {
        $resource = $this->getUserGroupResource();

        $transformer = new UserGroupTransformer($resource);

        self::assertSame(
            $expected->getArrayCopy(),
            $transformer->transform($input?->getArrayCopy())
        );
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `reverseTransform` method calls expected resource methods')]
    public function testThatReverseTransformCallsExpectedResourceMethods(): void
    {
        $resource = $this->getUserGroupResource();

        $entity1 = new UserGroup();
        $entity2 = new UserGroup();

        $resource
            ->expects($this->exactly(2))
            ->method('findOne')
            ->willReturnOnConsecutiveCalls($entity1, $entity2);

        new UserGroupTransformer($resource)
            ->reverseTransform(['1', '2']);
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `reverseTransform` throws an exception for non-existing user group id')]
    public function testThatReverseTransformThrowsAnException(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('User group with id "2" does not exist!');

        $resource = $this->getUserGroupResource();

        $entity = new UserGroup();

        $resource
            ->expects($this->exactly(2))
            ->method('findOne')
            ->willReturnOnConsecutiveCalls($entity, null);

        new UserGroupTransformer($resource)
            ->reverseTransform(['1', '2']);
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `reverseTransform` method returns expected `UserGroup` entities')]
    public function testThatReverseTransformReturnsExpected(): void
    {
        $resource = $this->getUserGroupResource();

        $entity1 = new UserGroup();
        $entity2 = new UserGroup();

        $resource
            ->expects($this->exactly(2))
            ->method('findOne')
            ->willReturnOnConsecutiveCalls($entity1, $entity2);

        $transformer = new UserGroupTransformer($resource);

        self::assertSame([$entity1, $entity2], $transformer->reverseTransform(['1', '2']));
    }

    /**
     * @psalm-return Generator<array{0: StringableArrayObject, 1: ?StringableArrayObject}>
     * @phpstan-return Generator<array{0: StringableArrayObject<mixed>, 1: ?StringableArrayObject<mixed>}>
     */
    public static function dataProviderTestThatTransformReturnsExpected(): Generator
    {
        yield [new StringableArrayObject([]), null];

        $entity = new UserGroup();

        yield [new StringableArrayObject([$entity->getId()]), new StringableArrayObject([$entity])];
    }

    /**
     * @phpstan-return  MockObject&UserGroupResource
     */
    private function getUserGroupResource(): MockObject
    {
        return $this
            ->getMockBuilder(UserGroupResource::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
