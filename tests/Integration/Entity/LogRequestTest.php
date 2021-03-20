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
use App\Utils\Tests\PhpUnitUtil;
use App\Utils\Tests\StringableArrayObject;
use Doctrine\Common\Collections\ArrayCollection;
use Generator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use function array_key_exists;
use function get_class;
use function in_array;
use function is_array;
use function is_object;
use function sprintf;
use function ucfirst;

/**
 * Class LogRequestTest
 *
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
    protected string $entityName = LogRequest::class;

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * @testdox No setter for `$field` field in read only entity - so cannot test this.
     */
    public function testThatSetterOnlyAcceptSpecifiedType(
        ?string $field = null,
        ?string $type = null,
        ?array $meta = null
    ): void {
        static::markTestSkipped('There is not setter in read only entity...');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * @testdox No setter for `$field` field in read only entity - so cannot test this.
     */
    public function testThatSetterReturnsInstanceOfEntity(
        ?string $field = null,
        ?string $type = null,
        ?array $meta = null
    ): void {
        static::markTestSkipped('There is not setter in read only entity...');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * @dataProvider dataProviderTestThatSetterAndGettersWorks
     *
     * @throws Throwable
     *
     * @testdox Test that getter method for `$field` with `$type` returns expected.
     */
    public function testThatGetterReturnsExpectedValue(string $field, string $type, array $meta): void
    {
        $getter = 'get' . ucfirst($field);

        if (in_array($type, [PhpUnitUtil::TYPE_BOOL, PhpUnitUtil::TYPE_BOOLEAN], true)) {
            $getter = 'is' . ucfirst($field);
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

            static::assertInstanceOf($type, $value);
        }

        $returnValue = $value;

        if (is_object($value)) {
            $returnValue = get_class($value);
        } elseif (is_array($value)) {
            $returnValue = 'array';
        }

        $message = sprintf(
            'Getter \'%s\' for field \'%s\' did not return expected type \'%s\' return value was \'%s\'',
            $getter,
            $field,
            $type,
            $returnValue
        );

        try {
            $method = 'assertIs' . ucfirst($type);

            static::$method($value, $message);
        } catch (Throwable $error) {
            /**
             * @var class-string $type
             */
            static::assertInstanceOf($type, $value, $message . ' - ' . $error->getMessage());
        }
    }

    /**
     * @dataProvider dataProviderTestThatSensitiveDataIsCleaned
     *
     * @phpstan-param StringableArrayObject<array<int, string>> $properties
     * @phpstan-param StringableArrayObject<array<string, string>> $headers
     * @phpstan-param StringableArrayObject<array<string, string>> $expected
     * @psalm-param StringableArrayObject $properties
     * @psalm-param StringableArrayObject $headers
     * @psalm-param StringableArrayObject $expected
     *
     * @throws Throwable
     *
     * @testdox Test that sensitive data `$properties` from `$headers` is cleaned and output is expected `$expected`.
     */
    public function testThatSensitiveDataIsCleanedFromHeaders(
        StringableArrayObject $properties,
        StringableArrayObject $headers,
        StringableArrayObject $expected
    ): void {
        $request = Request::create('');
        $request->headers->replace($headers->getArrayCopy());

        $logRequest = new LogRequest($properties->getArrayCopy(), $request, new Response());

        static::assertSame($expected->getArrayCopy(), $logRequest->getHeaders());
    }

    /**
     * @dataProvider dataProviderTestThatSensitiveDataIsCleaned
     *
     * @phpstan-param StringableArrayObject<array<int, string>> $properties
     * @phpstan-param StringableArrayObject<array<string, string>> $parameters
     * @phpstan-param StringableArrayObject<array<string, string>> $expected
     * @psalm-param StringableArrayObject $properties
     * @psalm-param StringableArrayObject $parameters
     * @psalm-param StringableArrayObject $expected
     *
     * @throws Throwable
     *
     * @testdox Test that sensitive data `$properties` from `parameters` is cleaned and output is expected `$expected`.
     */
    public function testThatSensitiveDataIsCleanedFromParameters(
        StringableArrayObject $properties,
        StringableArrayObject $parameters,
        StringableArrayObject $expected,
    ): void {
        $request = Request::create('', 'POST');
        $request->request->replace($parameters->getArrayCopy());

        $logRequest = new LogRequest($properties->getArrayCopy(), $request, new Response());

        static::assertSame($expected->getArrayCopy(), $logRequest->getParameters());
    }

    /**
     * @dataProvider dataProviderTestThatDetermineParametersWorksLikeExpected
     *
     * @phpstan-param StringableArrayObject<array<string, string>> $expected
     * @psalm-param StringableArrayObject $expected
     *
     * @throws Throwable
     *
     * @testdox Test that `determineParameters` method returns `$expected` when using `$content` as input.
     */
    public function testThatDetermineParametersWorksLikeExpected(string $content, StringableArrayObject $expected): void
    {
        $logRequest = new LogRequest([], Request::create(''), new Response());

        $request = Request::create('', 'GET', [], [], [], [], $content);

        static::assertSame(
            $expected->getArrayCopy(),
            PhpUnitUtil::callMethod($logRequest, 'determineParameters', [$request])
        );
    }

    /**
     * @return Generator<array{0: StringableArrayObject, 1: StringableArrayObject, 2: StringableArrayObject}>
     */
    public function dataProviderTestThatSensitiveDataIsCleaned(): Generator
    {
        yield [
            new StringableArrayObject(['password']),
            new StringableArrayObject(['password' => 'password']),
            new StringableArrayObject(['password' => '*** REPLACED ***']),
        ];

        yield [
            new StringableArrayObject(['token']),
            new StringableArrayObject(['token' => 'secret token']),
            new StringableArrayObject(['token' => '*** REPLACED ***']),
        ];

        yield [
            new StringableArrayObject(['authorization']),
            new StringableArrayObject(['authorization' => 'authorization bearer']),
            new StringableArrayObject(['authorization' => '*** REPLACED ***']),
        ];

        yield [
            new StringableArrayObject(['cookie']),
            new StringableArrayObject(['cookie' => 'cookie']),
            new StringableArrayObject(['cookie' => '*** REPLACED ***']),
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
     * @return Generator<array{0: string, 1: StringableArrayObject}>
     */
    public function dataProviderTestThatDetermineParametersWorksLikeExpected(): Generator
    {
        yield [
            '{"foo":"bar"}',
            new StringableArrayObject(['foo' => 'bar']),
        ];

        yield [
            'foo=bar',
            new StringableArrayObject(['foo' => 'bar']),
        ];
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     *
     * @throws Throwable
     */
    protected function createEntity(): LogRequest
    {
        return new LogRequest([]);
    }
}
