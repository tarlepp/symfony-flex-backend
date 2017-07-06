<?php
declare(strict_types=1);
/**
 * /tests/Integration/Form/DataTransformer/RoleTransformerTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Form\DataTransformer;

use App\Entity\Role;
use App\Form\DataTransformer\RoleTransformer;
use App\Repository\RoleRepository;
use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class RoleTransformerTest
 *
 * @package App\Tests\Integration\Form\Console\DataTransformer
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RoleTransformerTest extends KernelTestCase
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

        $transformer = new RoleTransformer($manager);

        static::assertSame($expected, $transformer->transform($input));
    }

    public function testThatReverseTransformCallsExpectedObjectManagerMethods(): void
    {
        /** @var PHPUnit_Framework_MockObject_MockObject|ObjectManager $manager */
        $manager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();

        /** @var PHPUnit_Framework_MockObject_MockObject|RoleRepository $repository */
        $repository = $this->getMockBuilder(RoleRepository::class)->disableOriginalConstructor()->getMock();

        $entity = new Role();

        $repository
            ->expects(static::once())
            ->method('find')
            ->with('rolename')
            ->willReturn($entity);

        $manager
            ->expects(static::once())
            ->method('getRepository')
            ->with(Role::class)
            ->willReturn($repository);

        $transformer = new RoleTransformer($manager);
        $transformer->reverseTransform('rolename');
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage Role with name "rolename" does not exist!
     */
    public function testThatReverseTransformThrowsAnException(): void
    {
        /** @var PHPUnit_Framework_MockObject_MockObject|ObjectManager $manager */
        $manager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();

        /** @var PHPUnit_Framework_MockObject_MockObject|RoleRepository $repository */
        $repository = $this->getMockBuilder(RoleRepository::class)->disableOriginalConstructor()->getMock();

        $repository
            ->expects(static::once())
            ->method('find')
            ->with('rolename')
            ->willReturn(null);

        $manager
            ->expects(static::once())
            ->method('getRepository')
            ->with(Role::class)
            ->willReturn($repository);

        $transformer = new RoleTransformer($manager);
        $transformer->reverseTransform('rolename');
    }

    public function testThatReverseTransformReturnsExpected(): void
    {
        /** @var PHPUnit_Framework_MockObject_MockObject|ObjectManager $manager */
        $manager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();

        /** @var PHPUnit_Framework_MockObject_MockObject|RoleRepository $repository */
        $repository = $this->getMockBuilder(RoleRepository::class)->disableOriginalConstructor()->getMock();

        $entity = new Role();

        $repository
            ->expects(static::once())
            ->method('find')
            ->with('rolename')
            ->willReturn($entity);

        $manager
            ->expects(static::once())
            ->method('getRepository')
            ->with(Role::class)
            ->willReturn($repository);

        $transformer = new RoleTransformer($manager);

        static::assertSame($entity, $transformer->reverseTransform('rolename'));
    }

    /**
     * @return array
     */
    public function dataProviderTestThatTransformReturnsExpected(): array
    {
        $entity = new Role();

        return [
            ['', null],
            [$entity->getId(), $entity],
        ];
    }
}
