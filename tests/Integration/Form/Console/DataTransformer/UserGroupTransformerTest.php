<?php
declare(strict_types=1);
/**
 * /tests/Integration/Form/Console/DataTransformer/UserGroupTransformerTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Form\Console\DataTransformer;

use App\Entity\UserGroup;
use App\Form\Console\DataTransformer\UserGroupTransformer;
use App\Repository\UserGroupRepository;
use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit_Framework_MockObject_MockObject;
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
     * @dataProvider dataProviderTestThatTransformReturnsExpected
     *
     * @param mixed $expected
     * @param mixed $input
     */
    public function testThatTransformReturnsExpected($expected, $input): void
    {
        /** @var PHPUnit_Framework_MockObject_MockObject|ObjectManager $manager */
        $manager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();

        $transformer = new UserGroupTransformer($manager);

        static::assertSame($expected, $transformer->transform($input));
    }

    public function testThatReverseTransformCallsExpectedObjectManagerMethods(): void
    {
        /** @var PHPUnit_Framework_MockObject_MockObject|ObjectManager $manager */
        $manager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();

        /** @var PHPUnit_Framework_MockObject_MockObject|UserGroupRepository $repository */
        $repository = $this->getMockBuilder(UserGroupRepository::class)->disableOriginalConstructor()->getMock();

        $entity1 = new UserGroup();
        $entity2 = new UserGroup();

        $repository
            ->expects(static::exactly(2))
            ->method('find')
            ->withConsecutive(['1'], ['2'])
            ->willReturnOnConsecutiveCalls($entity1, $entity2);

        $manager
            ->expects(static::once())
            ->method('getRepository')
            ->with(UserGroup::class)
            ->willReturn($repository);

        $transformer = new UserGroupTransformer($manager);
        $transformer->reverseTransform(['1', '2']);
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage User group with id "2" does not exist!
     */
    public function testThatReverseTransformThrowsAnException(): void
    {
        /** @var PHPUnit_Framework_MockObject_MockObject|ObjectManager $manager */
        $manager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();

        /** @var PHPUnit_Framework_MockObject_MockObject|UserGroupRepository $repository */
        $repository = $this->getMockBuilder(UserGroupRepository::class)->disableOriginalConstructor()->getMock();

        $entity = new UserGroup();

        $repository
            ->expects(static::exactly(2))
            ->method('find')
            ->withConsecutive(['1'], ['2'])
            ->willReturnOnConsecutiveCalls($entity, null);

        $manager
            ->expects(static::once())
            ->method('getRepository')
            ->with(UserGroup::class)
            ->willReturn($repository);

        $transformer = new UserGroupTransformer($manager);
        $transformer->reverseTransform(['1', '2']);
    }

    public function testThatReverseTransformReturnsExpected(): void
    {
        /** @var PHPUnit_Framework_MockObject_MockObject|ObjectManager $manager */
        $manager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();

        /** @var PHPUnit_Framework_MockObject_MockObject|UserGroupRepository $repository */
        $repository = $this->getMockBuilder(UserGroupRepository::class)->disableOriginalConstructor()->getMock();

        $entity1 = new UserGroup();
        $entity2 = new UserGroup();

        $repository
            ->expects(static::exactly(2))
            ->method('find')
            ->withConsecutive(['1'], ['2'])
            ->willReturnOnConsecutiveCalls($entity1, $entity2);

        $manager
            ->expects(static::once())
            ->method('getRepository')
            ->with(UserGroup::class)
            ->willReturn($repository);

        $transformer = new UserGroupTransformer($manager);

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
