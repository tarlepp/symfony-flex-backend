<?php
declare(strict_types = 1);
/**
 * /tests/Integration/AutoMapper/UserGroup/RequestMapperTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\AutoMapper\UserGroup;

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
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RequestMapperTest extends RestRequestMapperTestCase
{
    protected RequestMapper $mapperObject;
    protected string $mapperClass = RequestMapper::class;
    protected array $restDtoClasses = [
        DTO\UserGroup::class,
        DTO\UserGroupCreate::class,
        DTO\UserGroupUpdate::class,
        DTO\UserGroupPatch::class,
    ];

    /**
     * @var MockObject|RoleResource
     */
    private MockObject $mockRoleResource;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRoleResource = $this->getMockBuilder(RoleResource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mapperObject = new RequestMapper($this->mockRoleResource);
    }

    /**
     * @dataProvider dataProviderTestThatTransformUserGroupsCallsExpectedResourceMethod
     *
     * @throws Throwable
     *
     * @testdox Test that `transformUserGroups` calls expected resource method when processing `$dtoClass`.
     */
    public function testThatTransformUserGroupsCallsExpectedResourceMethod(string $dtoClass): void
    {
        $role = new Role('Some Role');

        $this->mockRoleResource
            ->expects(static::once())
            ->method('getReference')
            ->with($role->getId())
            ->willReturn($role);

        $request = new Request([], ['role' => $role->getId()]);

        /**
         * @var DTO\UserGroup $dto
         */
        $dto = $this->mapperObject->mapToObject($request, new $dtoClass());

        static::assertSame($role, $dto->getRole());
    }

    public function dataProviderTestThatTransformUserGroupsCallsExpectedResourceMethod(): Generator
    {
        foreach ($this->restDtoClasses as $dtoClass) {
            yield [$dtoClass];
        }
    }
}
