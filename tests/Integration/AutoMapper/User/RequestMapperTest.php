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
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

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
        DTO\User::class,
        DTO\UserCreate::class,
        DTO\UserUpdate::class,
        DTO\UserPatch::class,
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
        $userGroup = new UserGroup();

        $resource
            ->expects(self::once())
            ->method('getReference')
            ->with($userGroup->getId())
            ->willReturn($userGroup);

        $request = new Request([], [
            'userGroups' => [$userGroup->getId()],
        ]);

        /** @var DTO\User $dto */
        $dto = $requestMapper->mapToObject($request, new $dtoClass());

        self::assertSame([$userGroup], $dto->getUserGroups());
    }

    /**
     * @dataProvider dataProviderTestThatExceptionIsThrownWhenUsingInvalidLanguage
     *
     * @param class-string $dtoClass
     *
     * @throws Throwable
     *
     * @testdox Test that `transformLanguage` throws exception when using `$input` language with `$dtoClass` DTO object
     */
    public function testThatExceptionIsThrownWhenUsingInvalidLanguage(string $dtoClass, mixed $input): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid language');

        $resource = $this->getResource();
        $requestMapper = new RequestMapper($resource);

        $request = new Request([], [
            'language' => $input,
        ]);

        $requestMapper->mapToObject($request, new $dtoClass());
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
     * @return Generator<array{0: class-string, 1: mixed}>
     */
    public function dataProviderTestThatExceptionIsThrownWhenUsingInvalidLanguage(): Generator
    {
        $invalidValues = [
            '',
            'foo',
        ];

        foreach ($this->restDtoClasses as $dtoClass) {
            foreach ($invalidValues as $input) {
                yield [$dtoClass, $input];
            }
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
