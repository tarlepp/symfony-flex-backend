<?php
declare(strict_types = 1);
/**
 * /tests/Integration/AutoMapper/UserGroup/RequestMapperTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\AutoMapper\UserGroup;

use App\AutoMapper\RestRequestMapper;
use App\AutoMapper\User\RequestMapper;
use App\DTO\UserGroup as DTO;
use App\Entity\Role;
use App\Entity\UserGroup;
use App\Resource\RoleResource;
use App\Resource\UserGroupResource;
use App\Tests\Integration\AutoMapper\RestRequestMapperTestCase;
use Generator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

/**
 * Class RequestMapperTest
 *
 * @package App\Tests\Integration\AutoMapper\UserGroup
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RequestMapperTest extends RestRequestMapperTestCase
{
    /**
     * @var string
     */
    protected $mapperClass = RequestMapper::class;

    /**
     * @var RestRequestMapper|RequestMapper
     */
    protected $mapperObject;

    /**
     * @var string[]
     */
    protected $restDtoClasses = [
        DTO\UserGroup::class,
        DTO\UserGroupCreate::class,
        DTO\UserGroupUpdate::class,
        DTO\UserGroupPatch::class,
    ];

    /**
     * @var MockObject|UserGroupResource
     */
    protected $mockRoleResource;

    /**
     * @dataProvider dataProviderTestThatTransformUserGroupsCallsExpectedResourceMethod
     *
     * @param string $dtoClass
     *
     * @throws Throwable
     */
    public function testThatTransformUserGroupsCallsExpectedResourceMethod(string $dtoClass): void
    {
        $role = new Role();

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

        static::assertSame([$role], $dto->getRole());
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatTransformUserGroupsCallsExpectedResourceMethod(): Generator
    {
        foreach ($this->restDtoClasses as $dtoClass) {
            yield [$dtoClass];
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRoleResource = $this->getMockBuilder(RoleResource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mapperObject = new RequestMapper($this->mockRoleResource);
    }
}
