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
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Throwable;

/**
 * Class RoleTransformerTest
 *
 * @package App\Tests\Integration\Form\Console\DataTransformer
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RoleTransformerTest extends KernelTestCase
{
    /**
     * @dataProvider dataProviderTestThatTransformReturnsExpected
     *
     * @testdox Test that `transform` method returns `$expected` when using `$input` input.
     */
    public function testThatTransformReturnsExpected(string $expected, ?Role $input): void
    {
        $roleResourceMock = $this->createMock(RoleResource::class);

        $transformer = new RoleTransformer($roleResourceMock);

        static::assertSame($expected, $transformer->transform($input));
    }

    /**
     * @throws Throwable
     */
    public function testThatReverseTransformCallsExpectedObjectManagerMethods(): void
    {
        $entity = new Role('Some Role');

        $roleResourceMock = $this->createMock(RoleResource::class);

        $roleResourceMock
            ->expects(static::once())
            ->method('findOne')
            ->with($entity->getId())
            ->willReturn($entity);

        (new RoleTransformer($roleResourceMock))
            ->reverseTransform($entity->getId());
    }

    /**
     * @throws Throwable
     */
    public function testThatReverseTransformThrowsAnException(): void
    {
        $roleResourceMock = $this->createMock(RoleResource::class);

        $roleResourceMock
            ->expects(static::once())
            ->method('findOne')
            ->with('role_name')
            ->willReturn(null);

        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Role with name "role_name" does not exist!');

        (new RoleTransformer($roleResourceMock))
            ->reverseTransform('role_name');
    }

    /**
     * @throws Throwable
     */
    public function testThatReverseTransformReturnsExpected(): void
    {
        $entity = new Role('Some Role');

        $roleResourceMock = $this->createMock(RoleResource::class);

        $roleResourceMock
            ->expects(static::once())
            ->method('findOne')
            ->with('Some Role')
            ->willReturn($entity);

        $transformer = new RoleTransformer($roleResourceMock);

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
}
