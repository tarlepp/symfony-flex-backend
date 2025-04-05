<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Rest/RequestHandlerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Unit\Rest;

use App\Rest\RequestHandler;
use App\Tests\Utils\StringableArrayObject;
use Generator;
use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use function json_encode;

/**
 * @package App\Tests\Unit\Rest;
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class RequestHandlerTest extends KernelTestCase
{
    #[DataProvider('dataProviderTestThatGetCriteriaMethodThrowsAnExceptionWithInvalidWhereParameter')]
    #[TestDox(
        'Test that `getCriteria` method throws an exception with `$method` invalid (non JSON) `?where` parameter'
    )]
    public function testThatGetCriteriaMethodThrowsAnExceptionWithInvalidWhereParameter(string $method): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Current \'where\' parameter is not valid JSON.');

        $fakeRequest = Request::create(
            '/',
            $method,
            [
                'where' => '{foo bar',
            ]
        );

        RequestHandler::getCriteria($fakeRequest);
    }

    /**
     * @throws JsonException
     */
    #[DataProvider('dataProviderTestThatGetCriteriaMethodsReturnsExpectedGenerator')]
    #[TestDox('Test that `getCriteria` returns `$expected` when using `$method` and `$where` as `?where` parameter')]
    public function testThatGetCriteriaMethodsReturnsExpectedGenerator(
        string $method,
        StringableArrayObject $expected,
        StringableArrayObject $where
    ): void {
        $fakeRequest = Request::create(
            '/',
            'GET',
            [
                'where' => json_encode($where->getArrayCopy(), JSON_THROW_ON_ERROR),
            ]
        );

        self::assertSame($expected->getArrayCopy(), RequestHandler::getCriteria($fakeRequest));
    }

    #[DataProvider('dataProviderTestThatGetOrderByReturnsExpectedValue')]
    #[TestDox('Test that `getOrderBy` method returns `$expected` when using `$method` and `$parameters` as an input')]
    public function testThatGetOrderByReturnsExpectedValue(
        string $method,
        StringableArrayObject $parameters,
        StringableArrayObject $expected
    ): void {
        $fakeRequest = Request::create('/', $method, $parameters->getArrayCopy());

        self::assertSame(
            $expected->getArrayCopy(),
            RequestHandler::getOrderBy($fakeRequest),
            'getOrderBy method did not return expected value'
        );
    }

    #[DataProvider('dataProviderTestThatGetLimitReturnsNullWithoutParameter')]
    #[TestDox('Test that `getLimit` method returns `null` when there is no `?limit` parameter on `$method` request')]
    public function testThatGetLimitReturnsNullWithoutParameter(string $method): void
    {
        $fakeRequest = Request::create('/', $method);

        self::assertNull(
            RequestHandler::getLimit($fakeRequest),
            'getLimit method did not return NULL as it should without any parameters'
        );
    }

    #[DataProvider('dataProviderTestThatGetLimitReturnsExpectedValue')]
    #[TestDox(
        'Test that `getLimit` method returns `$expected` when using `$parameters` as `$method` request parameter'
    )]
    public function testThatGetLimitReturnsExpectedValue(
        string $method,
        StringableArrayObject $parameters,
        int $expected
    ): void {
        $fakeRequest = Request::create('/', $method, $parameters->getArrayCopy());

        $actual = RequestHandler::getLimit($fakeRequest);

        self::assertNotNull(
            $actual,
            'getLimit returned NULL and it should return an integer'
        );

        self::assertSame(
            $expected,
            $actual,
            'getLimit method did not return expected value'
        );
    }

    #[DataProvider('dataProviderTestThatGetOffsetReturnsNullWithoutParameter')]
    #[TestDox('Test that `getOffset` method returns `null` when there is no `?offset` parameter on `$method` request')]
    public function testThatGetOffsetReturnsNullWithoutParameter(string $method): void
    {
        $fakeRequest = Request::create('/', $method);

        self::assertNull(
            RequestHandler::getOffset($fakeRequest),
            'getOffset method did not return NULL as it should without any parameters'
        );
    }

    #[DataProvider('dataProviderTestThatGetOffsetReturnsExpectedValue')]
    #[TestDox(
        'Test that `getOffset` method returns `$expected` when using `$parameters` as `$method` request parameter'
    )]
    public function testThatGetOffsetReturnsExpectedValue(
        string $method,
        StringableArrayObject $parameters,
        int $expected,
    ): void {
        $fakeRequest = Request::create('/', $method, $parameters->getArrayCopy());

        $actual = RequestHandler::getOffset($fakeRequest);

        self::assertNotNull(
            $actual,
            'getOffset returned NULL and it should return an integer'
        );

        self::assertSame(
            $expected,
            $actual,
            'getOffset method did not return expected value'
        );
    }

    #[DataProvider('dataProviderTestThatGetSearchTermsReturnsEmptyGeneratorWithoutParameters')]
    #[TestDox(
        'Test that `getSearchTerms` returns empty array when there is no `search` parameter on `$method` request'
    )]
    public function testThatGetSearchTermsReturnsEmptyGeneratorWithoutParameters(string $method): void
    {
        $fakeRequest = Request::create('/', $method);

        self::assertSame(
            [],
            RequestHandler::getSearchTerms($fakeRequest),
            'getSearchTerms method did not return empty array ([]) as it should without any parameters'
        );
    }

    #[DataProvider('dataProviderTestThatGetSearchTermsThrowsAnExceptionWithInvalidJson')]
    #[TestDox('Test that `getSearchTerms` throws an exception when `$method` `search` parameter contains invalid JSON')]
    public function testThatGetSearchTermsThrowsAnExceptionWithInvalidJson(string $method): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage(
            'Given search parameter is not valid, within JSON provide \'and\' and/or \'or\' property.'
        );

        $parameters = [
            'search' => '{"foo": "bar"}',
        ];

        $fakeRequest = Request::create('/', $method, $parameters);

        RequestHandler::getSearchTerms($fakeRequest);
    }

    #[DataProvider('dataProviderTestThatGetSearchTermsReturnsExpectedValue')]
    #[TestDox('Test that `getSearchTerms` returns `$expected` when using `$search` as `$method` `search` parameter')]
    public function testThatGetSearchTermsReturnsExpectedValue(
        string $method,
        StringableArrayObject $expected,
        string | bool $search,
    ): void {
        $parameters = [
            'search' => (string)$search,
        ];

        $fakeRequest = Request::create('/', $method, $parameters);

        self::assertSame(
            $expected->getArrayCopy(),
            RequestHandler::getSearchTerms($fakeRequest),
            'getSearchTerms method did not return expected value'
        );
    }

    /**
     * @return Generator<array{0: string}>
     */
    public static function dataProviderTestThatGetCriteriaMethodThrowsAnExceptionWithInvalidWhereParameter(): Generator
    {
        yield [Request::METHOD_GET];
        yield [Request::METHOD_POST];
    }

    /**
     * @return Generator<array{0: string, 1: StringableArrayObject, 2: StringableArrayObject}>
     */
    public static function dataProviderTestThatGetCriteriaMethodsReturnsExpectedGenerator(): Generator
    {
        foreach ([Request::METHOD_GET, Request::METHOD_POST] as $method) {
            yield [
                $method,
                new StringableArrayObject([
                    'foo' => 'bar',
                ]),
                new StringableArrayObject([
                    'foo' => 'bar',
                ]),
            ];

            yield [
                $method,
                new StringableArrayObject([
                    'foo' => '',
                ]),
                new StringableArrayObject([
                    'foo' => '',
                ]),
            ];

            yield [
                $method,
                new StringableArrayObject([
                    'foo' => '0',
                ]),
                new StringableArrayObject([
                    'foo' => '0',
                ]),
            ];

            yield [
                $method,
                new StringableArrayObject([
                    'foo' => 0,
                ]),
                new StringableArrayObject([
                    'foo' => 0,
                ]),
            ];

            yield [
                $method,
                new StringableArrayObject([
                    'foo' => true,
                ]),
                new StringableArrayObject([
                    'foo' => true,
                ]),
            ];

            yield [
                $method,
                new StringableArrayObject([
                    'foo' => false,
                ]),
                new StringableArrayObject([
                    'foo' => false,
                ]),
            ];

            yield [
                $method,
                new StringableArrayObject([]),
                new StringableArrayObject([
                    'foo' => null,
                ]),
            ];

            yield [
                $method,
                new StringableArrayObject([
                    'foo1' => 'bar',
                    'foo2' => '',
                    'foo3' => '0',
                    'foo4' => 0,
                    'foo5' => true,
                    'foo6' => false,
                ]),
                new StringableArrayObject([
                    'foo1' => 'bar',
                    'foo11' => null,
                    'foo2' => '',
                    'foo21' => null,
                    'foo3' => '0',
                    'foo31' => null,
                    'foo4' => 0,
                    'foo41' => null,
                    'foo5' => true,
                    'foo51' => null,
                    'foo6' => false,
                    'foo61' => null,
                ]),
            ];
        }
    }

    /**
     * Data provider method for 'testThatGetOrderByReturnsExpectedValue' test.
     *
     * @return Generator<array{0: string, 1: StringableArrayObject, 2: StringableArrayObject}>
     */
    public static function dataProviderTestThatGetOrderByReturnsExpectedValue(): Generator
    {
        foreach ([Request::METHOD_GET, Request::METHOD_POST] as $method) {
            yield [
                $method,
                new StringableArrayObject([
                    'order' => 'column1',
                ]),
                new StringableArrayObject([
                    'column1' => 'ASC',
                ]),
            ];

            yield [
                $method,
                new StringableArrayObject([
                    'order' => '-column1',
                ]),
                new StringableArrayObject([
                    'column1' => 'DESC',
                ]),
            ];

            yield [
                $method,
                new StringableArrayObject([
                    'order' => 't.column1',
                ]),
                new StringableArrayObject([
                    't.column1' => 'ASC',
                ]),
            ];

            yield [
                $method,
                new StringableArrayObject([
                    'order' => '-t.column1',
                ]),
                new StringableArrayObject([
                    't.column1' => 'DESC',
                ]),
            ];

            yield [
                $method,
                new StringableArrayObject([
                    'order' => [
                        'column1' => 'ASC',
                    ],
                ]),
                new StringableArrayObject([
                    'column1' => 'ASC',
                ]),
            ];

            yield [
                $method,
                new StringableArrayObject([
                    'order' => [
                        'column1' => 'DESC',
                    ],
                ]),
                new StringableArrayObject([
                    'column1' => 'DESC',
                ]),
            ];

            yield [
                $method,
                new StringableArrayObject([
                    'order' => [
                        'column1' => 'foobar',
                    ],
                ]),
                new StringableArrayObject([
                    'column1' => 'ASC',
                ]),
            ];

            yield [
                $method,
                new StringableArrayObject([
                    'order' => [
                        't.column1' => 'ASC',
                    ],
                ]),
                new StringableArrayObject([
                    't.column1' => 'ASC',
                ]),
            ];

            yield [
                $method,
                new StringableArrayObject([
                    'order' => [
                        't.column1' => 'DESC',
                    ],
                ]),
                new StringableArrayObject([
                    't.column1' => 'DESC',
                ]),
            ];

            yield [
                $method,
                new StringableArrayObject([
                    'order' => [
                        't.column1' => 'foobar',
                    ],
                ]),
                new StringableArrayObject([
                    't.column1' => 'ASC',
                ]),
            ];

            yield [
                $method,
                new StringableArrayObject([
                    'order' => [
                        'column1' => 'ASC',
                        'column2' => 'DESC',
                    ],
                ]),
                new StringableArrayObject([
                    'column1' => 'ASC',
                    'column2' => 'DESC',
                ]),
            ];

            yield [
                $method,
                new StringableArrayObject([
                    'order' => [
                        't.column1' => 'ASC',
                        't.column2' => 'DESC',
                    ],
                ]),
                new StringableArrayObject([
                    't.column1' => 'ASC',
                    't.column2' => 'DESC',
                ]),
            ];

            yield [
                $method,
                new StringableArrayObject([
                    'order' => [
                        't.column1' => 'ASC',
                        'column2' => 'ASC',
                    ],
                ]),
                new StringableArrayObject([
                    't.column1' => 'ASC',
                    'column2' => 'ASC',
                ]),
            ];

            yield [
                $method,
                new StringableArrayObject([
                    'order' => [
                        'column1' => 'ASC',
                        'column2' => 'foobar',
                    ],
                ]),
                new StringableArrayObject([
                    'column1' => 'ASC',
                    'column2' => 'ASC',
                ]),
            ];
        }
    }

    /**
     * @return Generator<array{0: string}>
     */
    public static function dataProviderTestThatGetLimitReturnsNullWithoutParameter(): Generator
    {
        yield [Request::METHOD_GET];
        yield [Request::METHOD_POST];
    }

    /**
     * Data provider method for 'testThatGetLimitReturnsExpectedValue' test.
     *
     * @return Generator<array{0: string, 1: StringableArrayObject, 2: int}>
     */
    public static function dataProviderTestThatGetLimitReturnsExpectedValue(): Generator
    {
        foreach ([Request::METHOD_GET, Request::METHOD_POST] as $method) {
            yield [
                $method,
                new StringableArrayObject([
                    'limit' => 10,
                ]),
                10,
            ];

            yield [
                $method,
                new StringableArrayObject([
                    'limit' => 'ddd',
                ]),
                0,
            ];

            yield [
                $method,
                new StringableArrayObject([
                    'limit' => 'E10',
                ]),
                0,
            ];

            yield [
                $method,
                new StringableArrayObject([
                    'limit' => -10,
                ]),
                10,
            ];
        }
    }

    /**
     * @return Generator<array{0: string}>
     */
    public static function dataProviderTestThatGetOffsetReturnsNullWithoutParameter(): Generator
    {
        yield [Request::METHOD_GET];
        yield [Request::METHOD_POST];
    }

    /**
     * Data provider method for 'testThatGetOffsetReturnsExpectedValue' test.
     *
     * @return Generator<array{0: string, 1: StringableArrayObject, 2: int}>
     */
    public static function dataProviderTestThatGetOffsetReturnsExpectedValue(): Generator
    {
        foreach ([Request::METHOD_GET, Request::METHOD_POST] as $method) {
            yield [
                $method,
                new StringableArrayObject([
                    'offset' => 10,
                ]),
                10,
            ];

            yield [
                $method,
                new StringableArrayObject([
                    'offset' => 'ddd',
                ]),
                0,
            ];

            yield [
                $method,
                new StringableArrayObject([
                    'offset' => 'E10',
                ]),
                0,
            ];

            yield [
                $method,
                new StringableArrayObject([
                    'offset' => -10,
                ]),
                10,
            ];
        }
    }

    /**
     * @return Generator<array{0: string}>
     */
    public static function dataProviderTestThatGetSearchTermsReturnsEmptyGeneratorWithoutParameters(): Generator
    {
        yield [Request::METHOD_GET];
        yield [Request::METHOD_POST];
    }

    /**
     * @return Generator<array{0: string}>
     */
    public static function dataProviderTestThatGetSearchTermsThrowsAnExceptionWithInvalidJson(): Generator
    {
        yield [Request::METHOD_GET];
        yield [Request::METHOD_POST];
    }

    /**
     * Data provider method for 'testThatGetSearchTermsReturnsExpectedValue' test.
     *
     * @return Generator<array{0: string, 1: StringableArrayObject, 2: boolean|string}>
     */
    public static function dataProviderTestThatGetSearchTermsReturnsExpectedValue(): Generator
    {
        foreach ([Request::METHOD_GET, Request::METHOD_POST] as $method) {
            yield [
                $method,
                new StringableArrayObject([
                    'or' => [
                        '1',
                    ],
                ]),
                true,
            ];

            yield [
                $method,
                new StringableArrayObject([
                    'or' => [
                        'bar',
                    ],
                ]),
                'bar',
            ];

            yield [
                $method,
                new StringableArrayObject([
                    'or' => [
                        'bar',
                        'foo',
                    ],
                ]),
                'bar foo',
            ];

            yield [
                $method,
                new StringableArrayObject([
                    'or' => [
                        'bar',
                        'f',
                        'oo',
                    ],
                ]),
                'bar  f    oo ',
            ];

            yield [
                $method,
                new StringableArrayObject([
                    'and' => [
                        'foo',
                    ],
                ]),
                '{"and": ["foo"]}',
            ];

            yield [
                $method,
                new StringableArrayObject([
                    'or' => [
                        'bar',
                    ],
                ]),
                '{"or": ["bar"]}',
            ];

            yield [
                $method,
                new StringableArrayObject([
                    'and' => [
                        'foo',
                        'bar',
                    ],
                ]),
                '{"and": ["foo", "bar"]}',
            ];

            yield [
                $method,
                new StringableArrayObject([
                    'or' => [
                        'bar',
                        'foo',
                    ],
                ]),
                '{"or": ["bar", "foo"]}',
            ];

            yield [
                $method,
                new StringableArrayObject([
                    'or' => [
                        'bar',
                        'foo',
                    ],
                    'and' => [
                        'foo',
                        'bar',
                    ],
                ]),
                '{"or": ["bar", "foo"], "and": ["foo", "bar"]}',
            ];

            yield [
                $method,
                new StringableArrayObject([
                    'or' => [
                        '{"or":',
                        '["bar",',
                        '"foo"],',
                    ],
                ]),
                // With invalid JSON input it should fallback to string handling
                '{"or": ["bar", "foo"], ',
            ];
        }
    }
}
