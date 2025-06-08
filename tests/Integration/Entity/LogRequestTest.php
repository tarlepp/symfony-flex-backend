<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Entity/LogRequestTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Entity;

use App\Entity\ApiKey;
use App\Entity\LogRequest;
use App\Entity\User;
use App\Tests\Integration\TestCase\EntityTestCase;
use App\Tests\Utils\PhpUnitUtil;
use App\Tests\Utils\StringableArrayObject;
use Doctrine\Common\Collections\ArrayCollection;
use Generator;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use function array_key_exists;
use function in_array;
use function is_array;
use function is_object;
use function sprintf;
use function ucfirst;

/**
 * @package App\Tests\Integration\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @method LogRequest getEntity()
 */
class LogRequestTest extends EntityTestCase
{
    /**
     * @var class-string
     */
    protected static string $entityName = LogRequest::class;

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[DataProvider('dataProviderTestThatSetterAndGettersWorksWithoutReadOnlyFlag')]
    #[TestDox('No setter for `$property` property in read only entity - so cannot test this')]
    #[Override]
    public function testThatSetterOnlyAcceptSpecifiedType(
        ?string $property = null,
        ?string $type = null,
        ?array $meta = null
    ): void {
        self::markTestSkipped('There is not setter in read only entity...');
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[DataProvider('dataProviderTestThatSetterAndGettersWorksWithoutReadOnlyFlag')]
    #[TestDox('No setter for `$property` property in read only entity - so cannot test this')]
    #[Override]
    public function testThatSetterReturnsInstanceOfEntity(
        ?string $property = null,
        ?string $type = null,
        ?array $meta = null
    ): void {
        self::markTestSkipped('There is not setter in read only entity...');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatSetterAndGettersWorksWithoutReadOnlyFlag')]
    #[TestDox('Test that getter method for `$type $property` returns expected')]
    #[Override]
    public function testThatGetterReturnsExpectedValue(string $property, string $type, array $meta): void
    {
        $getter = 'get' . ucfirst($property);

        if (in_array($type, [PhpUnitUtil::TYPE_BOOL, PhpUnitUtil::TYPE_BOOLEAN], true)) {
            $getter = 'is' . ucfirst($property);
        }

        $logRequest = new LogRequest(
            [],
            Request::create(''),
            new Response('abcdefgh'),
            new User(),
            new ApiKey()
        );

        $value = $logRequest->{$getter}();

        if (!(array_key_exists('columnName', $meta) || array_key_exists('joinColumns', $meta))) {
            $type = ArrayCollection::class;

            self::assertInstanceOf($type, $value);
        }

        $returnValue = $value;

        if (is_object($value)) {
            $returnValue = $value::class;
        } elseif (is_array($value)) {
            $returnValue = 'array';
        }

        $message = sprintf(
            'Getter \'%s\' for field \'%s\' did not return expected type \'%s\' return value was \'%s\'',
            $getter,
            $property,
            $type,
            $returnValue
        );

        try {
            $method = $type === 'json' ? 'assertIsArray' : 'assertIs' . ucfirst($type);

            self::$method($value, $message);
        } catch (Throwable $error) {
            /**
             * @var class-string $type
             */
            self::assertInstanceOf($type, $value, $message . ' - ' . $error->getMessage());
        }
    }

    /**
     * @phpstan-param StringableArrayObject<array<int, string>> $properties
     * @phpstan-param StringableArrayObject<array<string, string>> $headers
     * @phpstan-param StringableArrayObject<array<string, string>> $expected
     * @psalm-param StringableArrayObject $properties
     * @psalm-param StringableArrayObject $headers
     * @psalm-param StringableArrayObject $expected
     *
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatSensitiveDataIsCleaned')]
    #[TestDox('Test that sensitive data `$properties` from `$headers` is cleaned and output is expected `$expected`')]
    public function testThatSensitiveDataIsCleanedFromHeaders(
        StringableArrayObject $properties,
        StringableArrayObject $headers,
        StringableArrayObject $expected
    ): void {
        $request = Request::create('');
        $request->headers->replace($headers->getArrayCopy());

        $logRequest = new LogRequest($properties->getArrayCopy(), $request, new Response());

        self::assertSame($expected->getArrayCopy(), $logRequest->getHeaders());
    }

    /**
     * @phpstan-param StringableArrayObject<array<int, string>> $properties
     * @phpstan-param StringableArrayObject<array<string, string>> $parameters
     * @phpstan-param StringableArrayObject<array<string, string>> $expected
     * @psalm-param StringableArrayObject $properties
     * @psalm-param StringableArrayObject $parameters
     * @psalm-param StringableArrayObject $expected
     *
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatSensitiveDataIsCleaned')]
    #[TestDox('Test that sensitive data `$properties` from `parameters` is cleaned and output is expected `$expected`')]
    public function testThatSensitiveDataIsCleanedFromParameters(
        StringableArrayObject $properties,
        StringableArrayObject $parameters,
        StringableArrayObject $expected,
    ): void {
        $request = Request::create('', 'POST');
        $request->request->replace($parameters->getArrayCopy());

        $logRequest = new LogRequest($properties->getArrayCopy(), $request, new Response());

        self::assertSame($expected->getArrayCopy(), $logRequest->getParameters());
    }

    /**
     * @phpstan-param StringableArrayObject<array<string, string>> $expected
     * @psalm-param StringableArrayObject $expected
     *
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatDetermineParametersWorksLikeExpected')]
    #[TestDox('Test that `determineParameters` method returns `$expected` when using `$content` as input')]
    public function testThatDetermineParametersWorksLikeExpected(string $content, StringableArrayObject $expected): void
    {
        $logRequest = new LogRequest([], Request::create(''), new Response());

        $request = Request::create('', 'GET', [], [], [], [], $content);

        self::assertSame(
            $expected->getArrayCopy(),
            PhpUnitUtil::callMethod($logRequest, 'determineParameters', [$request])
        );
    }

    /**
     * @psalm-return Generator<array{0: StringableArrayObject, 1: StringableArrayObject, 2: StringableArrayObject}>
     * @phpstan-return Generator<array{
     *      0: StringableArrayObject<mixed>,
     *      1: StringableArrayObject<mixed>,
     *      2: StringableArrayObject<mixed>,
     *  }>
     */
    public static function dataProviderTestThatSensitiveDataIsCleaned(): Generator
    {
        yield [
            new StringableArrayObject(['password']),
            new StringableArrayObject([
                'password' => 'password',
            ]),
            new StringableArrayObject([
                'password' => '*** REPLACED ***',
            ]),
        ];

        yield [
            new StringableArrayObject(['token']),
            new StringableArrayObject([
                'token' => 'secret token',
            ]),
            new StringableArrayObject([
                'token' => '*** REPLACED ***',
            ]),
        ];

        yield [
            new StringableArrayObject(['authorization']),
            new StringableArrayObject([
                'authorization' => 'authorization bearer',
            ]),
            new StringableArrayObject([
                'authorization' => '*** REPLACED ***',
            ]),
        ];

        yield [
            new StringableArrayObject(['cookie']),
            new StringableArrayObject([
                'cookie' => 'cookie',
            ]),
            new StringableArrayObject([
                'cookie' => '*** REPLACED ***',
            ]),
        ];

        yield [
            new StringableArrayObject([
                'password',
                'token',
                'authorization',
                'cookie',
            ]),
            new StringableArrayObject([
                'password' => 'password',
                'token' => 'secret token',
                'authorization' => 'authorization bearer',
                'cookie' => 'cookie',
            ]),
            new StringableArrayObject([
                'password' => '*** REPLACED ***',
                'token' => '*** REPLACED ***',
                'authorization' => '*** REPLACED ***',
                'cookie' => '*** REPLACED ***',
            ]),
        ];
    }

    /**
     * @psalm-return Generator<array{0: string, 1: StringableArrayObject}>
     * @phpstan-return Generator<array{0: string, 1: StringableArrayObject<mixed>}>
     */
    public static function dataProviderTestThatDetermineParametersWorksLikeExpected(): Generator
    {
        yield [
            '{"foo":"bar"}',
            new StringableArrayObject([
                'foo' => 'bar',
            ]),
        ];

        yield [
            'foo=bar',
            new StringableArrayObject([
                'foo' => 'bar',
            ]),
        ];
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     *
     * @throws Throwable
     */
    #[Override]
    protected function createEntity(): LogRequest
    {
        return new LogRequest([]);
    }
}
