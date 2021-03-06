<?php
declare(strict_types = 1);
/**
 * /tests/Integration/AutoMapper/ApiKey/RequestMapperTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\AutoMapper\ApiKey;

use App\AutoMapper\ApiKey\RequestMapper;
use App\DTO\ApiKey as DTO;
use App\Entity\UserGroup;
use App\Resource\UserGroupResource;
use App\Tests\Integration\AutoMapper\RestRequestMapperTestCase;
use Generator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Throwable;
use UnexpectedValueException;

/**
 * Class RequestMapperTest
 *
 * @package App\Tests\Integration\AutoMapper\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RequestMapperTest extends RestRequestMapperTestCase
{
    /**
     * @var array<int, class-string>
     */
    protected array $restDtoClasses = [
        DTO\ApiKey::class,
        DTO\ApiKeyCreate::class,
        DTO\ApiKeyUpdate::class,
        DTO\ApiKeyPatch::class,
    ];

    private ?MockObject $mockUserGroupResource = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockUserGroupResource = $this->getMockBuilder(UserGroupResource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mapperObject = new RequestMapper($this->mockUserGroupResource);
    }

    /**
     * @dataProvider dataProviderTestThatTransformUserGroupsCallsExpectedResourceMethod
     *
     * @param class-string $dtoClass
     *
     * @throws Throwable
     *
     * @testdox Test that `transformUserGroups` calls expected resource method when processing `$dtoClass`.
     */
    public function testThatTransformUserGroupsCallsExpectedResourceMethod(string $dtoClass): void
    {
        $userGroup = new UserGroup();

        $this->getMockUserGroupResource()
            ->expects(static::once())
            ->method('getReference')
            ->with($userGroup->getId())
            ->willReturn($userGroup);

        $request = new Request([], [
            'userGroups' => [$userGroup->getId()],
        ]);

        /**
         * @var DTO\ApiKey $dto
         */
        $dto = $this->getMapperObject()->mapToObject($request, new $dtoClass());

        static::assertSame([$userGroup], $dto->getUserGroups());
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

    private function getMockUserGroupResource(): MockObject
    {
        return $this->mockUserGroupResource ?? throw new UnexpectedValueException('MockUserGroupResource not set');
    }
}
