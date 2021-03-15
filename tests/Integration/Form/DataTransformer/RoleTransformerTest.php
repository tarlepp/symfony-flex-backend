<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Form/DataTransformer/RoleTransformerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
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
use UnexpectedValueException;

/**
 * Class RoleTransformerTest
 *
 * @package App\Tests\Integration\Form\Console\DataTransformer
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RoleTransformerTest extends KernelTestCase
{
    private MockObject | RoleResource | null $roleResource = null;

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
     * @testdox Test that `transform` method returns `$expected` when using `$input` input.
     */
    public function testThatTransformReturnsExpected(string $expected, ?Role $input): void
    {
        $transformer = new RoleTransformer($this->getRoleResource());

        static::assertSame($expected, $transformer->transform($input));
    }

    /**
     * @throws Throwable
     */
    public function testThatReverseTransformCallsExpectedObjectManagerMethods(): void
    {
        $entity = new Role('Some Role');

        $this->getRoleResourceMock()
            ->expects(static::once())
            ->method('findOne')
            ->with($entity->getId())
            ->willReturn($entity);

        (new RoleTransformer($this->getRoleResource()))
            ->reverseTransform($entity->getId());
    }

    /**
     * @throws Throwable
     */
    public function testThatReverseTransformThrowsAnException(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Role with name "role_name" does not exist!');

        $this->getRoleResourceMock()
            ->expects(static::once())
            ->method('findOne')
            ->with('role_name')
            ->willReturn(null);

        (new RoleTransformer($this->getRoleResource()))
            ->reverseTransform('role_name');
    }

    /**
     * @throws Throwable
     */
    public function testThatReverseTransformReturnsExpected(): void
    {
        $entity = new Role('Some Role');

        $this->getRoleResourceMock()
            ->expects(static::once())
            ->method('findOne')
            ->with('Some Role')
            ->willReturn($entity);

        $transformer = new RoleTransformer($this->getRoleResource());

        static::assertSame($entity, $transformer->reverseTransform('Some Role'));
    }

    /**
     * @return Generator<array{0: string, 1: Role|null}>
     */
    public function dataProviderTestThatTransformReturnsExpected(): Generator
    {
        yield ['', null];

        $entity = new Role('some role');

        yield [$entity->getId(), $entity];
    }

    private function getRoleResource(): RoleResource
    {
        return $this->roleResource instanceof RoleResource
            ? $this->roleResource
            : throw new UnexpectedValueException('RoleResource not set');
    }

    private function getRoleResourceMock(): MockObject
    {
        return $this->roleResource instanceof MockObject
            ? $this->roleResource
            : throw new UnexpectedValueException('RoleResource not set');
    }
}
