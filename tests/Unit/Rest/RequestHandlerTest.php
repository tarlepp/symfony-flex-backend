<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Rest/RequestHandlerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Unit\Rest;

use App\Rest\RequestHandler;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use function json_encode;

/**
 * Class RequestTest
 *
 * @package App\Tests\Unit\Rest;
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RequestHandlerTest extends KernelTestCase
{
    /** @noinspection PhpFullyQualifiedNameUsageInspection */
    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @expectedExceptionMessage Current 'where' parameter is not valid JSON.
     */
    public function testThatGetCriteriaMethodThrowsAnExceptionWithInvalidWhereParameter(): void
    {
        $fakeRequest = Request::create('/', 'GET', ['where' => '{foo bar']);

        RequestHandler::getCriteria($fakeRequest);
    }

    /**
     * @dataProvider dataProviderTestThatGetCriteriaMethodsReturnsExpectedGenerator
     *
     * @param array $expected
     * @param array $where
     */
    public function testThatGetCriteriaMethodsReturnsExpectedGenerator(array $expected, array $where): void
    {
        $fakeRequest = Request::create('/', 'GET', ['where' => json_encode($where)]);

        static::assertSame($expected, RequestHandler::getCriteria($fakeRequest));

        unset($fakeRequest);
    }

    /**
     * @dataProvider dataProviderTestThatGetOrderByReturnsExpectedValue
     *
     * @param array $parameters
     * @param array $expected
     */
    public function testThatGetOrderByReturnsExpectedValue(array $parameters, array $expected): void
    {
        $fakeRequest = Request::create('/', 'GET', $parameters);

        static::assertSame(
            $expected,
            RequestHandler::getOrderBy($fakeRequest),
            'getOrderBy method did not return expected value'
        );

        unset($fakeRequest);
    }

    public function testThatGetLimitReturnsNullWithoutParameter(): void
    {
        $fakeRequest = Request::create('/');

        static::assertNull(
            RequestHandler::getLimit($fakeRequest),
            'getLimit method did not return NULL as it should without any parameters'
        );

        unset($fakeRequest);
    }

    /**
     * @dataProvider dataProviderTestThatGetLimitReturnsExpectedValue
     *
     * @param array   $parameters
     * @param integer $expected
     */
    public function testThatGetLimitReturnsExpectedValue(array $parameters, int $expected): void
    {
        $fakeRequest = Request::create('/', 'GET', $parameters);

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

        unset($actual, $fakeRequest);
    }

    public function testThatGetOffsetReturnsNullWithoutParameter(): void
    {
        $fakeRequest = Request::create('/');

        static::assertNull(
            RequestHandler::getOffset($fakeRequest),
            'getOffset method did not return NULL as it should without any parameters'
        );

        unset($fakeRequest);
    }

    /**
     * @dataProvider dataProviderTestThatGetOffsetReturnsExpectedValue
     *
     * @param array   $parameters
     * @param integer $expected
     */
    public function testThatGetOffsetReturnsExpectedValue(array $parameters, int $expected): void
    {
        $fakeRequest = Request::create('/', 'GET', $parameters);

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

        unset($actual, $fakeRequest);
    }

    public function testThatGetSearchTermsReturnsEmptyGeneratorWithoutParameters(): void
    {
        $fakeRequest = Request::create('/');

        static::assertSame(
            [],
            RequestHandler::getSearchTerms($fakeRequest),
            'getSearchTerms method did not return empty array ([]) as it should without any parameters'
        );

        unset($fakeRequest);
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection */
    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @expectedExceptionMessage Given search parameter is not valid, within JSON provide 'and' and/or 'or' property.
     */
    public function testThatGetSearchTermsThrowsAnExceptionWithInvalidJson(): void
    {
        $parameters = [
            'search' => '{"foo": "bar"}'
        ];

        $fakeRequest = Request::create('/', 'GET', $parameters);

        RequestHandler::getSearchTerms($fakeRequest);

        unset($fakeRequest);
    }

    /**
     * @dataProvider dataProviderTestThatGetSearchTermsReturnsExpectedValue
     *
     * @param array  $expected
     * @param string $search
     */
    public function testThatGetSearchTermsReturnsExpectedValue(array $expected, string $search): void
    {
        $parameters = [
            'search' => $search,
        ];

        $fakeRequest = Request::create('/', 'GET', $parameters);

        static::assertSame(
            $expected,
            RequestHandler::getSearchTerms($fakeRequest),
            'getSearchTerms method did not return expected value'
        );

        unset($fakeRequest);
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatGetCriteriaMethodsReturnsExpectedGenerator(): Generator
    {
        yield [
            [
                'foo' => 'bar',
            ],
            [
                'foo' => 'bar',
            ],
        ];

        yield [
            [
                'foo' => '',
            ],
            [
                'foo' => '',
            ],
        ];

        yield [
            [
                'foo' => '0',
            ],
            [
                'foo' => '0',
            ],
        ];

        yield [
            [
                'foo' => 0,
            ],
            [
                'foo' => 0,
            ],
        ];

        yield [
            [
                'foo' => true,
            ],
            [
                'foo' => true,
            ],
        ];

        yield [
            [
                'foo' => false,
            ],
            [
                'foo' => false,
            ],
        ];

        yield [
            [],
            [
                'foo' => null,
            ],
        ];

        yield [
            [
                'foo1' => 'bar',
                'foo2' => '',
                'foo3' => '0',
                'foo4' => 0,
                'foo5' => true,
                'foo6' => false,
            ],
            [
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
            ],
        ];
    }

    /**
     * Data provider method for 'testThatGetOrderByReturnsExpectedValue' test.
     *
     * @return Generator
     */
    public function dataProviderTestThatGetOrderByReturnsExpectedValue(): Generator
    {
        yield [
            ['order' => 'column1'],
            ['column1' => 'ASC'],
        ];

        yield [
            ['order' => '-column1'],
            ['column1' => 'DESC'],
        ];

        yield [
            ['order' => 't.column1'],
            ['t.column1' => 'ASC'],
        ];

        yield [
            ['order' => '-t.column1'],
            ['t.column1' => 'DESC'],
        ];

        yield [
            [
                'order' => [
                    'column1' => 'ASC',
                ],
            ],
            ['column1' => 'ASC'],
        ];

        yield [
            [
                'order' => [
                    'column1' => 'DESC',
                ],
            ],
            ['column1' => 'DESC'],
        ];

        yield [
            [
                'order' => [
                    'column1' => 'foobar',
                ],
            ],
            ['column1' => 'ASC'],
        ];

        yield [
            [
                'order' => [
                    't.column1' => 'ASC',
                ],
            ],
            ['t.column1' => 'ASC'],
        ];

        yield [
            [
                'order' => [
                    't.column1' => 'DESC',
                ],
            ],
            ['t.column1' => 'DESC'],
        ];

        yield [
            [
                'order' => [
                    't.column1' => 'foobar',
                ],
            ],
            ['t.column1' => 'ASC'],
        ];

        yield [
            [
                'order' => [
                    'column1' => 'ASC',
                    'column2' => 'DESC',
                ],
            ],
            [
                'column1' => 'ASC',
                'column2' => 'DESC',
            ],
        ];

        yield [
            [
                'order' => [
                    't.column1' => 'ASC',
                    't.column2' => 'DESC',
                ],
            ],
            [
                't.column1' => 'ASC',
                't.column2' => 'DESC',
            ],
        ];

        yield [
            [
                'order' => [
                    't.column1' => 'ASC',
                    'column2' => 'ASC',
                ],
            ],
            [
                't.column1' => 'ASC',
                'column2' => 'ASC',
            ],
        ];

        yield [
            [
                'order' => [
                    'column1' => 'ASC',
                    'column2' => 'foobar',
                ],
            ],
            [
                'column1' => 'ASC',
                'column2' => 'ASC',
            ],
        ];
    }

    /**
     * Data provider method for 'testThatGetLimitReturnsExpectedValue' test.
     *
     * @return Generator
     */
    public function dataProviderTestThatGetLimitReturnsExpectedValue(): Generator
    {
        yield [
            ['limit' => 10],
            10,
        ];

        yield [
            ['limit' => 'ddd'],
            0,
        ];

        yield [
            ['limit' => 'E10'],
            0,
        ];

        yield [
            ['limit' => -10],
            10,
        ];
    }

    /**
     * Data provider method for 'testThatGetOffsetReturnsExpectedValue' test.
     *
     * @return Generator
     */
    public function dataProviderTestThatGetOffsetReturnsExpectedValue(): Generator
    {
        yield [
            ['offset' => 10],
            10,
        ];

        yield [
            ['offset' => 'ddd'],
            0,
        ];

        yield [
            ['offset' => 'E10'],
            0,
        ];

        yield [
            ['offset' => -10],
            10,
        ];
    }

    /**
     * Data provider method for 'testThatGetSearchTermsReturnsExpectedValue' test.
     *
     * @return Generator
     */
    public function dataProviderTestThatGetSearchTermsReturnsExpectedValue(): Generator
    {
        yield [
            [
                'or' => [
                    '1',
                ],
            ],
            true,
        ];

        yield [
            [
                'or' => [
                    'bar',
                ],
            ],
            'bar',
        ];

        yield [
            [
                'or' => [
                    'bar',
                    'foo',
                ],
            ],
            'bar foo',
        ];

        yield [
            [
                'or' => [
                    'bar',
                    'f',
                    'oo',
                ],
            ],
            'bar  f    oo ',
        ];

        yield [
            [
                'and' => [
                    'foo',
                ],
            ],
            '{"and": ["foo"]}'
        ];

        yield [
            [
                'or' => [
                    'bar',
                ],
            ],
            '{"or": ["bar"]}'
        ];

        yield [
            [
                'and' => [
                    'foo',
                    'bar',
                ],
            ],
            '{"and": ["foo", "bar"]}'
        ];

        yield [
            [
                'or' => [
                    'bar',
                    'foo',
                ],
            ],
            '{"or": ["bar", "foo"]}'
        ];

        yield [
            [
                'or' => [
                    'bar',
                    'foo',
                ],
                'and' => [
                    'foo',
                    'bar',
                ],
            ],
            '{"or": ["bar", "foo"], "and": ["foo", "bar"]}'
        ];

        yield [
            [
                'or' => [
                    '{"or":',
                    '["bar",',
                    '"foo"],',
                ],
            ],
            '{"or": ["bar", "foo"], ', // With invalid JSON input it should fallback to string handling
        ];
    }
}
