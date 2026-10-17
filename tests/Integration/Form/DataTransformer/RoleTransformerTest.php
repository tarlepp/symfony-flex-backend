<?php
declare(strict_types = 1);

/**
 * /tests/Integration/Form/DataTransformer/RoleTransformerTest.php
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

final class RoleTransformerTest extends KernelTestCase
{
    #[DataProvider('dataProviderTestThatTransformReturnsExpected')]
    #[TestDox('Test that `transform` method returns `$expected` when using `$input` as input')]
    public function testThatTransformReturnsExpected(string $expected, ?Role $input): void
    {
        $resourceStub = self::createStub(RoleResource::class);

        $transformer = new RoleTransformer($resourceStub);

        self::assertSame($expected, $transformer->transform($input));
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `reverseTransform` method calls expected resource methods')]
    public function testThatReverseTransformCallsExpectedResourceMethods(): void
    {
        $resourceMock = $this->getRoleResourceMock();

        $entity = new Role('Some Role');

        $resourceMock
            ->expects($this->once())
            ->method('findOne')
            ->with($entity->getId())
            ->willReturn($entity);

        new RoleTransformer($resourceMock)
            ->reverseTransform($entity->getId());
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `reverseTransform` throws an exception for non-existing role')]
    public function testThatReverseTransformThrowsAnException(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessageIsOrContains('Role with name "role_name" does not exist!');

        $resourceMock = $this->getRoleResourceMock();

        $resourceMock
            ->expects($this->once())
            ->method('findOne')
            ->with('role_name')
            ->willReturn(null);

        new RoleTransformer($resourceMock)
            ->reverseTransform('role_name');
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `reverseTransform` method returns expected `role` entity')]
    public function testThatReverseTransformReturnsExpected(): void
    {
        $resourceMock = $this->getRoleResourceMock();

        $entity = new Role('Some Role');

        $resourceMock
            ->expects($this->once())
            ->method('findOne')
            ->with('Some Role')
            ->willReturn($entity);

        $transformer = new RoleTransformer($resourceMock);

        self::assertSame($entity, $transformer->reverseTransform('Some Role'));
    }

    /**
     * @return Generator<int, array{0: string, 1: Role|null}>
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
    private function getRoleResourceMock(): MockObject
    {
        return $this
            ->getMockBuilder(RoleResource::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
