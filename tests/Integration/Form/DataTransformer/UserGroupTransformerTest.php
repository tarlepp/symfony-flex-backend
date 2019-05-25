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
use Generator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;

/**
 * Class UserGroupTransformerTest
 *
 * @package App\Tests\Integration\Form\Console\DataTransformer
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserGroupTransformerTest extends KernelTestCase
{
    /**
     * @var MockObject|UserGroupResource
     */
    private $userGroupResource;

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

        unset($transformer);
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

        unset($transformer, $entity1, $entity2);
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection */
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

        unset($transformer, $entity);
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

        unset($transformer, $entity1, $entity2);
    }

    /**
     * @return Generator
     *
     * @throws Throwable
     */
    public function dataProviderTestThatTransformReturnsExpected(): Generator
    {
        yield [[], null];

        $entity = new UserGroup();

        yield [[$entity->getId()], [$entity]];
    }

    protected function setUp(): void
    {
        gc_enable();

        parent::setUp();

        $this->userGroupResource = $this
            ->getMockBuilder(UserGroupResource::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->userGroupResource);

        gc_collect_cycles();
    }
}
