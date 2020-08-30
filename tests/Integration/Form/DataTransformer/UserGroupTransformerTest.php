<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Form/DataTransformer/UserGroupTransformerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
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

/**
 * Class UserGroupTransformerTest
 *
 * @package App\Tests\Integration\Form\Console\DataTransformer
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserGroupTransformerTest extends KernelTestCase
{
    /**
     * @var MockObject|UserGroupResource
     */
    private MockObject $userGroupResource;

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
     * @testdox Test that `transform` method returns `$expected` when using `$input` input.
     */
    public function testThatTransformReturnsExpected(
        StringableArrayObject $expected,
        ?StringableArrayObject $input
    ): void {
        $transformer = new UserGroupTransformer($this->userGroupResource);

        static::assertSame(
            $expected->getArrayCopy(),
            $transformer->transform($input === null ? null : $input->getArrayCopy())
        );
    }

    public function testThatReverseTransformCallsExpectedObjectManagerMethods(): void
    {
        $entity1 = new UserGroup();
        $entity2 = new UserGroup();

        $this->userGroupResource
            ->expects(static::exactly(2))
            ->method('findOne')
            ->withConsecutive(['1'], ['2'])
            ->willReturnOnConsecutiveCalls($entity1, $entity2);

        (new UserGroupTransformer($this->userGroupResource))
            ->reverseTransform(['1', '2']);
    }

    public function testThatReverseTransformThrowsAnException(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('User group with id "2" does not exist!');

        $entity = new UserGroup();

        $this->userGroupResource
            ->expects(static::exactly(2))
            ->method('findOne')
            ->withConsecutive(['1'], ['2'])
            ->willReturnOnConsecutiveCalls($entity, null);

        (new UserGroupTransformer($this->userGroupResource))
            ->reverseTransform(['1', '2']);
    }

    public function testThatReverseTransformReturnsExpected(): void
    {
        $entity1 = new UserGroup();
        $entity2 = new UserGroup();

        $this->userGroupResource
            ->expects(static::exactly(2))
            ->method('findOne')
            ->withConsecutive(['1'], ['2'])
            ->willReturnOnConsecutiveCalls($entity1, $entity2);

        $transformer = new UserGroupTransformer($this->userGroupResource);

        static::assertSame([$entity1, $entity2], $transformer->reverseTransform(['1', '2']));
    }

    /**
     * @throws Throwable
     */
    public function dataProviderTestThatTransformReturnsExpected(): Generator
    {
        yield [new StringableArrayObject([]), null];

        $entity = new UserGroup();

        yield [new StringableArrayObject([$entity->getId()]), new StringableArrayObject([$entity])];
    }
}
