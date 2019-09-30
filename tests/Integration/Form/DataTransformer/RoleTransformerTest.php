<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Form/DataTransformer/RoleTransformerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Form\DataTransformer;

use App\Entity\Role;
use App\Form\DataTransformer\RoleTransformer;
use App\Resource\RoleResource;
use Generator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Throwable;

/**
 * Class RoleTransformerTest
 *
 * @package App\Tests\Integration\Form\Console\DataTransformer
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RoleTransformerTest extends KernelTestCase
{
    /**
     * @var MockObject|RoleResource
     */
    private $roleResource;

    /**
     * @dataProvider dataProviderTestThatTransformReturnsExpected
     *
     * @param mixed $expected
     * @param mixed $input
     */
    public function testThatTransformReturnsExpected($expected, $input): void
    {
        $transformer = new RoleTransformer($this->roleResource);

        static::assertSame($expected, $transformer->transform($input));
    }

    public function testThatReverseTransformCallsExpectedObjectManagerMethods(): void
    {
        $entity = new Role();

        $this->roleResource
            ->expects(static::once())
            ->method('findOne')
            ->with($entity->getId()->toString())
            ->willReturn($entity);

        $transformer = new RoleTransformer($this->roleResource);
        $transformer->reverseTransform($entity->getId()->toString());

        unset($transformer, $entity);
    }

    public function testThatReverseTransformThrowsAnException(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Role with id "role_name" does not exist!');

        $this->roleResource
            ->expects(static::once())
            ->method('findOne')
            ->with('role_name')
            ->willReturn(null);

        $transformer = new RoleTransformer($this->roleResource);
        $transformer->reverseTransform('role_name');

        unset($transformer);
    }

    public function testThatReverseTransformReturnsExpected(): void
    {
        $entity = new Role();

        $this->roleResource
            ->expects(static::once())
            ->method('findOne')
            ->with('rolename')
            ->willReturn($entity);

        $transformer = new RoleTransformer($this->roleResource);

        static::assertSame($entity, $transformer->reverseTransform('rolename'));

        unset($transformer, $entity);
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatTransformReturnsExpected(): Generator
    {
        yield ['', null];

        $entity = new Role();

        yield [$entity->getId()->toString(), $entity];
    }

    /**
     * @throws Throwable
     */
    protected function setUp(): void
    {
        gc_enable();

        parent::setUp();

        $this->roleResource = $this
            ->getMockBuilder(RoleResource::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->roleResource);

        gc_collect_cycles();
    }
}
