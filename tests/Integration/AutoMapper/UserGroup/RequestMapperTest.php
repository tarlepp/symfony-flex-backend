<?php
declare(strict_types = 1);
/**
 * /tests/Integration/AutoMapper/UserGroup/RequestMapperTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\AutoMapper\UserGroup;

use App\AutoMapper\RestRequestMapper;
use App\AutoMapper\UserGroup\RequestMapper;
use App\DTO\UserGroup as DTO;
use App\Entity\Role;
use App\Resource\RoleResource;
use App\Tests\Integration\AutoMapper\RestRequestMapperTestCase;
use Generator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

/**
 * Class RequestMapperTest
 *
 * @package App\Tests\Integration\AutoMapper\UserGroup
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RequestMapperTest extends RestRequestMapperTestCase
{
    /**
     * @var array<int, class-string>
     */
    protected array $restDtoClasses = [
        DTO\UserGroup::class,
        DTO\UserGroupCreate::class,
        DTO\UserGroupUpdate::class,
        DTO\UserGroupPatch::class,
    ];

    /**
     * @dataProvider dataProviderTestThatTransformUserGroupsCallsExpectedResourceMethod
     *
     * @param class-string $dtoClass
     *
     * @throws Throwable
     *
     * @testdox Test that `transformUserGroups` calls expected resource method when processing `$dtoClass` DTO object
     */
    public function testThatTransformUserGroupsCallsExpectedResourceMethod(string $dtoClass): void
    {
        $resource = $this->getResource();
        $requestMapper = new RequestMapper($resource);
        $role = new Role('Some Role');

        $resource
            ->expects(self::once())
            ->method('getReference')
            ->with($role->getId())
            ->willReturn($role);

        $request = new Request(
            [],
            [
                'role' => $role->getId(),
            ]
        );

        /** @var DTO\UserGroup $dto */
        $dto = $requestMapper->mapToObject($request, new $dtoClass());

        self::assertSame($role, $dto->getRole());
    }

    /**
     * @return Generator<array{0: class-string}>
     */
    public function dataProviderTestThatTransformUserGroupsCallsExpectedResourceMethod(): Generator
    {
        foreach ($this->restDtoClasses as $dtoClass) {
            yield [$dtoClass];
        }
    }

    /**
     * @phpstan-return MockObject&RoleResource
     */
    protected function getResource(): MockObject
    {
        return $this->getMockBuilder(RoleResource::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function getRequestMapper(): RestRequestMapper
    {
        return new RequestMapper($this->getResource());
    }
}
