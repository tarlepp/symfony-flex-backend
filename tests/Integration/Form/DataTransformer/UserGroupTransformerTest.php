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
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Throwable;
use UnexpectedValueException;

/**
 * Class UserGroupTransformerTest
 *
 * @package App\Tests\Integration\Form\Console\DataTransformer
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UserGroupTransformerTest extends KernelTestCase
{
    private MockObject | UserGroupResource | null $userGroupResource = null;

    /**
     * @throws Throwable
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->userGroupResource = $this
            ->getMockBuilder(UserGroupResource::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @dataProvider dataProviderTestThatTransformReturnsExpected
     *
     * @phpstan-param StringableArrayObject<mixed> $expected
     * @phpstan-param StringableArrayObject<mixed>|null $input
     * @psalm-param StringableArrayObject $expected
     * @psalm-param StringableArrayObject|null $input
     *
     * @testdox Test that `transform` method returns `$expected` when using `$input` input.
     */
    public function testThatTransformReturnsExpected(
        StringableArrayObject $expected,
        ?StringableArrayObject $input
    ): void {
        $transformer = new UserGroupTransformer($this->getUserGroupResource());

        self::assertSame(
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

        $this->getUserGroupResourceMock()
            ->expects(self::exactly(2))
            ->method('findOne')
            ->withConsecutive(['1'], ['2'])
            ->willReturnOnConsecutiveCalls($entity1, $entity2);

        (new UserGroupTransformer($this->getUserGroupResource()))
            ->reverseTransform(['1', '2']);
    }

    /**
     * @throws Throwable
     */
    public function testThatReverseTransformThrowsAnException(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('User group with id "2" does not exist!');

        $entity = new UserGroup();

        $this->getUserGroupResourceMock()
            ->expects(self::exactly(2))
            ->method('findOne')
            ->withConsecutive(['1'], ['2'])
            ->willReturnOnConsecutiveCalls($entity, null);

        (new UserGroupTransformer($this->getUserGroupResource()))
            ->reverseTransform(['1', '2']);
    }

    /**
     * @throws Throwable
     */
    public function testThatReverseTransformReturnsExpected(): void
    {
        $entity1 = new UserGroup();
        $entity2 = new UserGroup();

        $this->getUserGroupResourceMock()
            ->expects(self::exactly(2))
            ->method('findOne')
            ->withConsecutive(['1'], ['2'])
            ->willReturnOnConsecutiveCalls($entity1, $entity2);

        $transformer = new UserGroupTransformer($this->getUserGroupResource());

        self::assertSame([$entity1, $entity2], $transformer->reverseTransform(['1', '2']));
    }

    /**
     * @psalm-return Generator<array{0: StringableArrayObject, 1: ?StringableArrayObject}>
     * @phpstan-return Generator<array{0: StringableArrayObject<mixed>, 1: ?StringableArrayObject<mixed>}>
     */
    public function dataProviderTestThatTransformReturnsExpected(): Generator
    {
        yield [new StringableArrayObject([]), null];

        $entity = new UserGroup();

        yield [new StringableArrayObject([$entity->getId()]), new StringableArrayObject([$entity])];
    }

    private function getUserGroupResource(): UserGroupResource
    {
        return $this->userGroupResource instanceof UserGroupResource
            ? $this->userGroupResource
            : throw new UnexpectedValueException('UserGroupResource not set');
    }

    private function getUserGroupResourceMock(): MockObject
    {
        return $this->userGroupResource instanceof MockObject
            ? $this->userGroupResource
            : throw new UnexpectedValueException('UserGroupResource not set');
    }
}
