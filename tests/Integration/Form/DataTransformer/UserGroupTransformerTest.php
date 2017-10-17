<?php
declare(strict_types=1);
/**
 * /tests/Integration/Form/DataTransformer/UserGroupTransformerTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Form\DataTransformer;

use App\Entity\UserGroup;
use App\Form\DataTransformer\UserGroupTransformer;
use App\Resource\UserGroupResource;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class UserGroupTransformerTest
 *
 * @package App\Tests\Integration\Form\Console\DataTransformer
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserGroupTransformerTest extends KernelTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|UserGroupResource
     */
    private $userGroupResource;

    public function setUp()
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
     * @param mixed $expected
     * @param mixed $input
     */
    public function testThatTransformReturnsExpected($expected, $input): void
    {
        $transformer = new UserGroupTransformer($this->userGroupResource);

        static::assertSame($expected, $transformer->transform($input));
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

        $transformer = new UserGroupTransformer($this->userGroupResource);
        $transformer->reverseTransform(['1', '2']);
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage User group with id "2" does not exist!
     */
    public function testThatReverseTransformThrowsAnException(): void
    {
        $entity = new UserGroup();

        $this->userGroupResource
            ->expects(static::exactly(2))
            ->method('findOne')
            ->withConsecutive(['1'], ['2'])
            ->willReturnOnConsecutiveCalls($entity, null);

        $transformer = new UserGroupTransformer($this->userGroupResource);
        $transformer->reverseTransform(['1', '2']);
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
     * @return array
     */
    public function dataProviderTestThatTransformReturnsExpected(): array
    {
        $entity = new UserGroup();

        return [
            [[], null],
            [[$entity->getId()], [$entity]],
        ];
    }
}
