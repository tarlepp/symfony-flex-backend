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
use App\Utils\Tests\StringableArrayObject;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Throwable;

/**
 * Class UserGroupTransformerTest
 *
 * @package App\Tests\Integration\Form\Console\DataTransformer
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UserGroupTransformerTest extends KernelTestCase
{
    /**
     * @dataProvider dataProviderTestThatTransformReturnsExpected
     *
     * @phpstan-param StringableArrayObject<array> $expected
     * @phpstan-param StringableArrayObject<array>|null $input
     * @psalm-param StringableArrayObject $expected
     * @psalm-param StringableArrayObject|null $input
     *
     * @testdox Test that `transform` method returns `$expected` when using `$input` input.
     */
    public function testThatTransformReturnsExpected(
        StringableArrayObject $expected,
        ?StringableArrayObject $input
    ): void {
        $userGroupResourceMock = $this->createMock(UserGroupResource::class);

        $transformer = new UserGroupTransformer($userGroupResourceMock);

        static::assertSame(
            $expected->getArrayCopy(),
            $transformer->transform($input === null ? null : $input->getArrayCopy())
        );
    }

    /**
     * @throws Throwable
     */
    public function testThatReverseTransformCallsExpectedObjectManagerMethods(): void
    {
        $entity1 = new UserGroup();
        $entity2 = new UserGroup();

        $userGroupResourceMock = $this->createMock(UserGroupResource::class);

        $userGroupResourceMock
            ->expects(static::exactly(2))
            ->method('findOne')
            ->withConsecutive(['1'], ['2'])
            ->willReturnOnConsecutiveCalls($entity1, $entity2);

        (new UserGroupTransformer($userGroupResourceMock))
            ->reverseTransform(['1', '2']);
    }

    /**
     * @throws Throwable
     */
    public function testThatReverseTransformThrowsAnException(): void
    {
        $entity = new UserGroup();

        $userGroupResourceMock = $this->createMock(UserGroupResource::class);

        $userGroupResourceMock
            ->expects(static::exactly(2))
            ->method('findOne')
            ->withConsecutive(['1'], ['2'])
            ->willReturnOnConsecutiveCalls($entity, null);

        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('User group with id "2" does not exist!');

        (new UserGroupTransformer($userGroupResourceMock))
            ->reverseTransform(['1', '2']);
    }

    /**
     * @throws Throwable
     */
    public function testThatReverseTransformReturnsExpected(): void
    {
        $entity1 = new UserGroup();
        $entity2 = new UserGroup();

        $userGroupResourceMock = $this->createMock(UserGroupResource::class);

        $userGroupResourceMock
            ->expects(static::exactly(2))
            ->method('findOne')
            ->withConsecutive(['1'], ['2'])
            ->willReturnOnConsecutiveCalls($entity1, $entity2);

        $transformer = new UserGroupTransformer($userGroupResourceMock);

        static::assertSame([$entity1, $entity2], $transformer->reverseTransform(['1', '2']));
    }

    /**
     * @return Generator<array{0: StringableArrayObject, 1: ?StringableArrayObject}>
     */
    public function dataProviderTestThatTransformReturnsExpected(): Generator
    {
        yield [new StringableArrayObject([]), null];

        $entity = new UserGroup();

        yield [new StringableArrayObject([$entity->getId()]), new StringableArrayObject([$entity])];
    }
}
