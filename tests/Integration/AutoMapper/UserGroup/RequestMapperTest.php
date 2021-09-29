<?php
declare(strict_types = 1);
/**
 * /tests/Integration/AutoMapper/UserGroup/RequestMapperTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
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
use UnexpectedValueException;

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

    private ?MockObject $mockRoleResource = null;

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
     * @param class-string $dtoClass
     *
     * @throws Throwable
     *
     * @testdox Test that `transformUserGroups` calls expected resource method when processing `$dtoClass`
     */
    public function testThatTransformUserGroupsCallsExpectedResourceMethod(string $dtoClass): void
    {
        $role = new Role('Some Role');

        $this->getMockRoleResource()
            ->expects(static::once())
            ->method('getReference')
            ->with($role->getId())
            ->willReturn($role);

        $request = new Request(
            [],
            [
                'role' => $role->getId(),
            ]
        );

        /**
         * @var DTO\UserGroup $dto
         */
        $dto = $this->getMapperObject()->mapToObject($request, new $dtoClass());

        static::assertSame($role, $dto->getRole());
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

    private function getMockRoleResource(): MockObject
    {
        return $this->mockRoleResource ?? throw new UnexpectedValueException('MockRoleResource not set');
    }
}
