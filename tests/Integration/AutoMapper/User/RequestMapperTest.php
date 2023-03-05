<?php
declare(strict_types = 1);
/**
 * /tests/Integration/AutoMapper/User/RequestMapperTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\AutoMapper\User;

use App\AutoMapper\RestRequestMapper;
use App\AutoMapper\User\RequestMapper;
use App\DTO\User as DTO;
use App\Entity\UserGroup;
use App\Resource\UserGroupResource;
use App\Tests\Integration\AutoMapper\RestRequestMapperTestCase;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Throwable;
use function class_exists;

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
    protected static array $restDtoClasses = [
        DTO\User::class,
        DTO\UserCreate::class,
        DTO\UserUpdate::class,
        DTO\UserPatch::class,
    ];

    /**
     * @param class-string $dtoClass
     *
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatTransformUserGroupsCallsExpectedResourceMethod')]
    #[TestDox('Test that `transformUserGroups` calls expected resource method when processing `$dtoClass` DTO object')]
    public function testThatTransformUserGroupsCallsExpectedResourceMethod(string $dtoClass): void
    {
        $resource = $this->getResource();
        $requestMapper = new RequestMapper($resource);
        $userGroup = new UserGroup();

        $resource
            ->expects(self::once())
            ->method('getReference')
            ->with($userGroup->getId())
            ->willReturn($userGroup);

        $request = new Request([], [
            'userGroups' => [$userGroup->getId()],
        ]);

        self::assertTrue(class_exists($dtoClass));

        $dto = $requestMapper->mapToObject($request, new $dtoClass());

        self::assertInstanceOf(DTO\User::class, $dto);
        self::assertSame([$userGroup], $dto->getUserGroups());
    }

    /**
     * @return Generator<array{0: class-string}>
     */
    public static function dataProviderTestThatTransformUserGroupsCallsExpectedResourceMethod(): Generator
    {
        foreach (static::$restDtoClasses as $dtoClass) {
            yield [$dtoClass];
        }
    }

    /**
     * @phpstan-return MockObject&UserGroupResource
     */
    protected function getResource(): MockObject
    {
        return $this->getMockBuilder(UserGroupResource::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function getRequestMapper(): RestRequestMapper
    {
        return new RequestMapper($this->getResource());
    }
}
