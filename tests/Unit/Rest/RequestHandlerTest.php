<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Rest/RequestHandlerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Unit\Rest;

use App\Rest\RequestHandler;
use App\Utils\Tests\StringableArrayObject;
use Generator;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use function json_encode;

/**
 * Class RequestHandlerTest
 *
 * @package App\Tests\Unit\Rest;
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RequestHandlerTest extends KernelTestCase
{
    /**
     * @testdox Test that `getCriteria` method throws an exception with invalid (non JSON) `?where` parameter
     */
    public function testThatGetCriteriaMethodThrowsAnExceptionWithInvalidWhereParameter(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Current \'where\' parameter is not valid JSON.');

        $fakeRequest = Request::create('/', 'GET', ['where' => '{foo bar']);

        RequestHandler::getCriteria($fakeRequest);
    }

    /**
     * @dataProvider dataProviderTestThatGetCriteriaMethodsReturnsExpectedGenerator
     *
     * @testdox Test that `getCriteria` method returns `$expected` when using `$where` as `?where` parameter
     *
     * @phpstan-param StringableArrayObject<array{0: StringableArrayObject, 1: StringableArrayObject}> $expected
     * @phpstan-param StringableArrayObject<array{0: StringableArrayObject, 1: StringableArrayObject}> $where
     * @psalm-param StringableArrayObject $expected
     * @psalm-param StringableArrayObject $where
     *
     * @throws JsonException
     */
    public function testThatGetCriteriaMethodsReturnsExpectedGenerator(
        StringableArrayObject $expected,
        StringableArrayObject $where
    ): void {
        $fakeRequest = Request::create(
            '/',
            'GET',
            ['where' => json_encode($where->getArrayCopy(), JSON_THROW_ON_ERROR)]
        );

        static::assertSame($expected->getArrayCopy(), RequestHandler::getCriteria($fakeRequest));
    }

    /**
     * @dataProvider dataProviderTestThatGetOrderByReturnsExpectedValue
     *
     * @phpstan-param StringableArrayObject<array{0: StringableArrayObject, 1: StringableArrayObject}> $parameters
     * @phpstan-param StringableArrayObject<array{0: StringableArrayObject, 1: StringableArrayObject}> $expected
     * @psalm-param StringableArrayObject $parameters
     * @psalm-param StringableArrayObject $expected
     *
     * @testdox Test that `getOrderBy` method returns `$expected` when using `$parameters` as an input
     */
    public function testThatGetOrderByReturnsExpectedValue(
        StringableArrayObject $parameters,
        StringableArrayObject $expected
    ): void {
        $fakeRequest = Request::create('/', 'GET', $parameters->getArrayCopy());

        static::assertSame(
            $expected->getArrayCopy(),
            RequestHandler::getOrderBy($fakeRequest),
            'getOrderBy method did not return expected value'
        );
    }

    /**
     * @testdox Test that `getLimit` method returns `null` when there is no `?limit` parameter on request
     */
    public function testThatGetLimitReturnsNullWithoutParameter(): void
    {
        $fakeRequest = Request::create('/');

        static::assertNull(
            RequestHandler::getLimit($fakeRequest),
            'getLimit method did not return NULL as it should without any parameters'
        );
    }

    /**
     * @dataProvider dataProviderTestThatGetLimitReturnsExpectedValue
     *
     * @phpstan-param StringableArrayObject<array{0: StringableArrayObject, 1: int}> $parameters
     * @psalm-param StringableArrayObject $parameters
     *
     * @testdox Test that `getLimit` method returns `$expected` when using `$parameters` as request parameter
     */
    public function testThatGetLimitReturnsExpectedValue(StringableArrayObject $parameters, int $expected): void
    {
        $fakeRequest = Request::create('/', 'GET', $parameters->getArrayCopy());

        $actual = RequestHandler::getLimit($fakeRequest);

        static::assertNotNull(
            $actual,
            'getLimit returned NULL and it should return an integer'
        );

        static::assertSame(
            $expected,
            $actual,
            'getLimit method did not return expected value'
        );
    }

    /**
     * @testdox Test that `getOffset` method returns `null` when there is no `?offset` parameter on request
     */
    public function testThatGetOffsetReturnsNullWithoutParameter(): void
    {
        $fakeRequest = Request::create('/');

        static::assertNull(
            RequestHandler::getOffset($fakeRequest),
            'getOffset method did not return NULL as it should without any parameters'
        );
    }

    /**
     * @dataProvider dataProviderTestThatGetOffsetReturnsExpectedValue
     *
     * @phpstan-param StringableArrayObject<array{0: StringableArrayObject, 1: int}> $parameters
     * @psalm-param StringableArrayObject $parameters
     *
     * @testdox Test that `getOffset` method returns `$expected` when using `$parameters` as request parameter
     */
    public function testThatGetOffsetReturnsExpectedValue(StringableArrayObject $parameters, int $expected): void
    {
        $fakeRequest = Request::create('/', 'GET', $parameters->getArrayCopy());

        $actual = RequestHandler::getOffset($fakeRequest);

        static::assertNotNull(
            $actual,
            'getOffset returned NULL and it should return an integer'
        );

        static::assertSame(
            $expected,
            $actual,
            'getOffset method did not return expected value'
        );
    }

    /**
     * @testdox Test that `getSearchTerms` method returns empty array when there is no `?search` parameter on request
     */
    public function testThatGetSearchTermsReturnsEmptyGeneratorWithoutParameters(): void
    {
        $fakeRequest = Request::create('/');

        static::assertSame(
            [],
            RequestHandler::getSearchTerms($fakeRequest),
            'getSearchTerms method did not return empty array ([]) as it should without any parameters'
        );
    }

    /**
     * @testdox Test that `getSearchTerms` method throws an exception when `?search` parameter contains invalid JSON
     */
    public function testThatGetSearchTermsThrowsAnExceptionWithInvalidJson(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage(
            'Given search parameter is not valid, within JSON provide \'and\' and/or \'or\' property.'
        );

        $parameters = [
            'search' => '{"foo": "bar"}',
        ];

        $fakeRequest = Request::create('/', 'GET', $parameters);

        RequestHandler::getSearchTerms($fakeRequest);
    }

    /**
     * @dataProvider dataProviderTestThatGetSearchTermsReturnsExpectedValue
     *
     * @phpstan-param StringableArrayObject<array{0: StringableArrayObject, 1: boolean|string}> $expected
     * @psalm-param StringableArrayObject $expected
     *
     * @param string|bool $search
     *
     * @testdox Test that `getSearchTerms` returns `$expected` when using `$search` as `?search` parameter
     */
    public function testThatGetSearchTermsReturnsExpectedValue(
        StringableArrayObject $expected,
        string | bool $search
    ): void {
        $parameters = [
            'search' => (string)$search,
        ];

        $fakeRequest = Request::create('/', 'GET', $parameters);

        static::assertSame(
            $expected->getArrayCopy(),
            RequestHandler::getSearchTerms($fakeRequest),
            'getSearchTerms method did not return expected value'
        );
    }

    /**
     * @psalm-return Generator<array{0: StringableArrayObject, 1: StringableArrayObject}>
     * @phpstan-return Generator<array{0: StringableArrayObject<mixed>, 1: StringableArrayObject<mixed>}>
     */
    public function dataProviderTestThatGetCriteriaMethodsReturnsExpectedGenerator(): Generator
    {
        yield [
            new StringableArrayObject(['foo' => 'bar']),
            new StringableArrayObject(['foo' => 'bar']),
        ];

        yield [
            new StringableArrayObject(['foo' => '']),
            new StringableArrayObject(['foo' => '']),
        ];

        yield [
            new StringableArrayObject(['foo' => '0']),
            new StringableArrayObject(['foo' => '0']),
        ];

        yield [
            new StringableArrayObject(['foo' => 0]),
            new StringableArrayObject(['foo' => 0]),
        ];

        yield [
            new StringableArrayObject(['foo' => true]),
            new StringableArrayObject(['foo' => true]),
        ];

        yield [
            new StringableArrayObject(['foo' => false]),
            new StringableArrayObject(['foo' => false]),
        ];

        yield [
            new StringableArrayObject([]),
            new StringableArrayObject(['foo' => null]),
        ];

        yield [
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

    /**
     * Data provider method for 'testThatGetOrderByReturnsExpectedValue' test.
     *
     * @psalm-return Generator<array{0: StringableArrayObject, 1: StringableArrayObject}>
     * @phpstan-return Generator<array{0: StringableArrayObject<mixed>, 1: StringableArrayObject<mixed>}>
     */
    public function dataProviderTestThatGetOrderByReturnsExpectedValue(): Generator
    {
        yield [
            new StringableArrayObject(['order' => 'column1']),
            new StringableArrayObject(['column1' => 'ASC']),
        ];

        yield [
            new StringableArrayObject(['order' => '-column1']),
            new StringableArrayObject(['column1' => 'DESC']),
        ];

        yield [
            new StringableArrayObject(['order' => 't.column1']),
            new StringableArrayObject(['t.column1' => 'ASC']),
        ];

        yield [
            new StringableArrayObject(['order' => '-t.column1']),
            new StringableArrayObject(['t.column1' => 'DESC']),
        ];

        yield [
            new StringableArrayObject([
                'order' => [
                    'column1' => 'ASC',
                ],
            ]),
            new StringableArrayObject(['column1' => 'ASC']),
        ];

        yield [
            new StringableArrayObject([
                'order' => [
                    'column1' => 'DESC',
                ],
            ]),
            new StringableArrayObject(['column1' => 'DESC']),
        ];

        yield [
            new StringableArrayObject([
                'order' => [
                    'column1' => 'foobar',
                ],
            ]),
            new StringableArrayObject(['column1' => 'ASC']),
        ];

        yield [
            new StringableArrayObject([
                'order' => [
                    't.column1' => 'ASC',
                ],
            ]),
            new StringableArrayObject(['t.column1' => 'ASC']),
        ];

        yield [
            new StringableArrayObject([
                'order' => [
                    't.column1' => 'DESC',
                ],
            ]),
            new StringableArrayObject(['t.column1' => 'DESC']),
        ];

        yield [
            new StringableArrayObject([
                'order' => [
                    't.column1' => 'foobar',
                ],
            ]),
            new StringableArrayObject(['t.column1' => 'ASC']),
        ];

        yield [
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

    /**
     * Data provider method for 'testThatGetLimitReturnsExpectedValue' test.
     *
     * @psalm-return Generator<array{0: StringableArrayObject, 1: int}>
     * @phpstan-return Generator<array{0: StringableArrayObject<mixed>, 1: int}>
     */
    public function dataProviderTestThatGetLimitReturnsExpectedValue(): Generator
    {
        yield [
            new StringableArrayObject(['limit' => 10]),
            10,
        ];

        yield [
            new StringableArrayObject(['limit' => 'ddd']),
            0,
        ];

        yield [
            new StringableArrayObject(['limit' => 'E10']),
            0,
        ];

        yield [
            new StringableArrayObject(['limit' => -10]),
            10,
        ];
    }

    /**
     * Data provider method for 'testThatGetOffsetReturnsExpectedValue' test.
     *
     * @psalm-return Generator<array{0: StringableArrayObject, 1: int}>
     * @phpstan-return Generator<array{0: StringableArrayObject<mixed>, 1: int}>
     */
    public function dataProviderTestThatGetOffsetReturnsExpectedValue(): Generator
    {
        yield [
            new StringableArrayObject(['offset' => 10]),
            10,
        ];

        yield [
            new StringableArrayObject(['offset' => 'ddd']),
            0,
        ];

        yield [
            new StringableArrayObject(['offset' => 'E10']),
            0,
        ];

        yield [
            new StringableArrayObject(['offset' => -10]),
            10,
        ];
    }

    /**
     * Data provider method for 'testThatGetSearchTermsReturnsExpectedValue' test.
     *
     * @psalm-return Generator<array{0: StringableArrayObject, 1: boolean|string}>
     * @phpstan-return Generator<array{0: StringableArrayObject<mixed>, 1: boolean|string}>
     */
    public function dataProviderTestThatGetSearchTermsReturnsExpectedValue(): Generator
    {
        yield [
            new StringableArrayObject([
                'or' => [
                    '1',
                ],
            ]),
            true,
        ];

        yield [
            new StringableArrayObject([
                'or' => [
                    'bar',
                ],
            ]),
            'bar',
        ];

        yield [
            new StringableArrayObject([
                'or' => [
                    'bar',
                    'foo',
                ],
            ]),
            'bar foo',
        ];

        yield [
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
            new StringableArrayObject([
                'and' => [
                    'foo',
                ],
            ]),
            '{"and": ["foo"]}',
        ];

        yield [
            new StringableArrayObject([
                'or' => [
                    'bar',
                ],
            ]),
            '{"or": ["bar"]}',
        ];

        yield [
            new StringableArrayObject([
                'and' => [
                    'foo',
                    'bar',
                ],
            ]),
            '{"and": ["foo", "bar"]}',
        ];

        yield [
            new StringableArrayObject([
                'or' => [
                    'bar',
                    'foo',
                ],
            ]),
            '{"or": ["bar", "foo"]}',
        ];

        yield [
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
