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
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RoleTransformerTest extends KernelTestCase
{
    /**
     * @var MockObject|RoleResource
     */
    private MockObject $roleResource;

    /**
     * @throws Throwable
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->roleResource = $this
            ->getMockBuilder(RoleResource::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @dataProvider dataProviderTestThatTransformReturnsExpected
     *
     * @param mixed $expected
     * @param mixed $input
     *
     * @testdox Test that `transform` method returns `$expected` when using `$input` input.
     */
    public function testThatTransformReturnsExpected($expected, $input): void
    {
        $transformer = new RoleTransformer($this->roleResource);

        static::assertSame($expected, $transformer->transform($input));
    }

    /**
     * @throws Throwable
     */
    public function testThatReverseTransformCallsExpectedObjectManagerMethods(): void
    {
        $entity = new Role('Some Role');

        $this->roleResource
            ->expects(static::once())
            ->method('findOne')
            ->with($entity->getId())
            ->willReturn($entity);

        (new RoleTransformer($this->roleResource))
            ->reverseTransform($entity->getId());
    }

    /**
     * @throws Throwable
     */
    public function testThatReverseTransformThrowsAnException(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Role with name "role_name" does not exist!');

        $this->roleResource
            ->expects(static::once())
            ->method('findOne')
            ->with('role_name')
            ->willReturn(null);

        (new RoleTransformer($this->roleResource))
            ->reverseTransform('role_name');
    }

    /**
     * @throws Throwable
     */
    public function testThatReverseTransformReturnsExpected(): void
    {
        $entity = new Role('Some Role');

        $this->roleResource
            ->expects(static::once())
            ->method('findOne')
            ->with('Some Role')
            ->willReturn($entity);

        $transformer = new RoleTransformer($this->roleResource);

        static::assertSame($entity, $transformer->reverseTransform('Some Role'));
    }

    /**
     * @throws Throwable
     */
    public function dataProviderTestThatTransformReturnsExpected(): Generator
    {
        yield ['', null];

        $entity = new Role('some role');

        yield [$entity->getId(), $entity];
    }
}
