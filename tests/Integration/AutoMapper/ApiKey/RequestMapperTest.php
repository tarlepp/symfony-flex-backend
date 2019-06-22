<?php
declare(strict_types = 1);
/**
 * /tests/Integration/AutoMapper/ApiKey/RequestMapperTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\AutoMapper\ApiKey;

use App\AutoMapper\ApiKey\RequestMapper;
use App\AutoMapper\RestRequestMapper;
use App\DTO\ApiKey as DTO;
use App\Entity\UserGroup;
use App\Resource\UserGroupResource;
use App\Tests\Integration\AutoMapper\RestRequestMapperTestCase;
use Generator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

/**
 * Class RequestMapperTest
 *
 * @package App\Tests\Integration\AutoMapper\User
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
        DTO\ApiKey::class,
        DTO\ApiKeyCreate::class,
        DTO\ApiKeyUpdate::class,
        DTO\ApiKeyPatch::class,
    ];

    /**
     * @var MockObject|UserGroupResource
     */
    protected $mockUserGroupResource;

    /**
     * @dataProvider dataProviderTestThatTransformUserGroupsCallsExpectedResourceMethod
     *
     * @param string $dtoClass
     *
     * @throws Throwable
     */
    public function testThatTransformUserGroupsCallsExpectedResourceMethod(string $dtoClass): void
    {
        $userGroup = new UserGroup();

        $this->mockUserGroupResource
            ->expects(static::once())
            ->method('getReference')
            ->with($userGroup->getId())
            ->willReturn($userGroup);

        $request = new Request([], ['userGroups' => [$userGroup->getId()]]);

        /**
         * @var DTO\ApiKey $dto
         */
        $dto = $this->mapperObject->mapToObject($request, new $dtoClass());

        static::assertSame([$userGroup], $dto->getUserGroups());
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

        $this->mockUserGroupResource = $this->getMockBuilder(UserGroupResource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mapperObject = new RequestMapper($this->mockUserGroupResource);
    }
}
