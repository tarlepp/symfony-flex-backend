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
use App\Enum\Language;
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
     * @param class-string<DTO\User> $dtoClass
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

        $dto = new $dtoClass();
        $output = $requestMapper->mapToObject($request, $dto);

        self::assertInstanceOf(DTO\User::class, $output);
        self::assertSame([$userGroup], $output->getUserGroups());
    }

    /**
     * @dataProvider dataProviderTestThatTransformLanguageWorksAsExpected
     *
     * @param class-string<DTO\User> $dtoClass
     *
     * @throws Throwable
     *
     * @testdox Test that `transformLanguage` returns `$expected` when using `$dtoClass` DTO and `$input` as an input
     */
    public function testThatTransformLanguageWorksAsExpected(Language $expected, string $dtoClass, string $input): void
    {
        $requestMapper = new RequestMapper($this->getResource());

        $request = new Request([], [
            'language' => $input,
        ]);

        $dto = new $dtoClass();
        $output = $requestMapper->mapToObject($request, $dto);

        self::assertInstanceOf(DTO\User::class, $output);
        self::assertSame($expected, $output->getLanguage());
    }

    /**
     * @return Generator<array{0: class-string<DTO\User>}>
     */
    public function dataProviderTestThatTransformUserGroupsCallsExpectedResourceMethod(): Generator
    {
        foreach ($this->restDtoClasses as $dtoClass) {
            yield [$dtoClass];
        }
    }

    /**
     * @return Generator<array{0: Language, 1: class-string<DTO\User>, 2: string}>
     */
    public function dataProviderTestThatTransformLanguageWorksAsExpected(): Generator
    {
        foreach ($this->restDtoClasses as $dtoClass) {
            yield [Language::EN, $dtoClass, 'en'];
            yield [Language::FI, $dtoClass, 'fi'];
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
