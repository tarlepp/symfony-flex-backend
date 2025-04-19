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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Throwable;

/**
 * @package App\Tests\Integration\Form\Console\DataTransformer
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RoleTransformerTest extends KernelTestCase
{
    #[DataProvider('dataProviderTestThatTransformReturnsExpected')]
    #[TestDox('Test that `transform` method returns `$expected` when using `$input` as input')]
    public function testThatTransformReturnsExpected(string $expected, ?Role $input): void
    {
        $resource = $this->getRoleResource();

        $transformer = new RoleTransformer($resource);

        self::assertSame($expected, $transformer->transform($input));
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `reverseTransform` method calls expected resource methods')]
    public function testThatReverseTransformCallsExpectedResourceMethods(): void
    {
        $resource = $this->getRoleResource();

        $entity = new Role('Some Role');

        $resource
            ->expects($this->once())
            ->method('findOne')
            ->with($entity->getId())
            ->willReturn($entity);

        new RoleTransformer($resource)
            ->reverseTransform($entity->getId());
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `reverseTransform` throws an exception for non-existing role')]
    public function testThatReverseTransformThrowsAnException(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Role with name "role_name" does not exist!');

        $resource = $this->getRoleResource();

        $resource
            ->expects($this->once())
            ->method('findOne')
            ->with('role_name')
            ->willReturn(null);

        new RoleTransformer($resource)
            ->reverseTransform('role_name');
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `reverseTransform` method returns expected `role` entity')]
    public function testThatReverseTransformReturnsExpected(): void
    {
        $resource = $this->getRoleResource();

        $entity = new Role('Some Role');

        $resource
            ->expects($this->once())
            ->method('findOne')
            ->with('Some Role')
            ->willReturn($entity);

        $transformer = new RoleTransformer($resource);

        self::assertSame($entity, $transformer->reverseTransform('Some Role'));
    }

    /**
     * @return Generator<array{0: string, 1: Role|null}>
     */
    public static function dataProviderTestThatTransformReturnsExpected(): Generator
    {
        yield ['', null];

        $entity = new Role('some role');

        yield [$entity->getId(), $entity];
    }

    /**
     * @phpstan-return MockObject&RoleResource
     */
    private function getRoleResource(): MockObject
    {
        return $this
            ->getMockBuilder(RoleResource::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
