<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Rest/RequestHandlerTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Unit\Rest;

use App\Rest\RequestHandler;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class RequestTest
 *
 * @package App\Tests\Unit\Rest;
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RequestHandlerTest extends KernelTestCase
{
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
     * @dataProvider dataProviderTestThatGetCriteriaMethodsReturnsExpectedArray
     *
     * @param array $expected
     * @param array $where
     */
    public function testThatGetCriteriaMethodsReturnsExpectedArray(array $expected, array $where): void
    {
        $fakeRequest = Request::create('/', 'GET', ['where' => \json_encode($where)]);

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
     * @param   array   $parameters
     * @param   integer $expected
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
     * @param   array   $parameters
     * @param   integer $expected
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

    public function testThatGetSearchTermsReturnsEmptyArrayWithoutParameters(): void
    {
        $fakeRequest = Request::create('/');

        static::assertSame(
            [],
            RequestHandler::getSearchTerms($fakeRequest),
            'getSearchTerms method did not return empty array ([]) as it should without any parameters'
        );

        unset($fakeRequest);
    }

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
     * @param   array   $expected
     * @param   string  $search
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
     * @return array
     */
    public function dataProviderTestThatGetCriteriaMethodsReturnsExpectedArray(): array
    {
        return [
            [
                [
                    'foo' => 'bar',
                ],
                [
                    'foo' => 'bar',
                ],
            ],
            [
                [
                    'foo' => '',
                ],
                [
                    'foo' => '',
                ],
            ],
            [
                [
                    'foo' => '0',
                ],
                [
                    'foo' => '0',
                ],
            ],
            [
                [
                    'foo' => 0,
                ],
                [
                    'foo' => 0,
                ],
            ],
            [
                [
                    'foo' => true,
                ],
                [
                    'foo' => true,
                ],
            ],
            [
                [
                    'foo' => false,
                ],
                [
                    'foo' => false,
                ],
            ],
            [
                [],
                [
                    'foo' => null,
                ],
            ],
            [
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
            ],
        ];
    }

    /**
     * Data provider method for 'testThatGetOrderByReturnsExpectedValue' test.
     *
     * @return array
     */
    public function dataProviderTestThatGetOrderByReturnsExpectedValue(): array
    {
        return [
            [
                ['order' => 'column1'],
                ['column1' => 'ASC'],
            ],
            [
                ['order' => '-column1'],
                ['column1' => 'DESC'],
            ],
            [
                ['order' => 't.column1'],
                ['t.column1' => 'ASC'],
            ],
            [
                ['order' => '-t.column1'],
                ['t.column1' => 'DESC'],
            ],
            [
                [
                    'order' => [
                        'column1' => 'ASC',
                    ],
                ],
                ['column1' => 'ASC'],
            ],
            [
                [
                    'order' => [
                        'column1' => 'DESC',
                    ],
                ],
                ['column1' => 'DESC'],
            ],
            [
                [
                    'order' => [
                        'column1' => 'foobar',
                    ],
                ],
                ['column1' => 'ASC'],
            ],
            [
                [
                    'order' => [
                        't.column1' => 'ASC',
                    ],
                ],
                ['t.column1' => 'ASC'],
            ],
            [
                [
                    'order' => [
                        't.column1' => 'DESC',
                    ],
                ],
                ['t.column1' => 'DESC'],
            ],
            [
                [
                    'order' => [
                        't.column1' => 'foobar',
                    ],
                ],
                ['t.column1' => 'ASC'],
            ],
            [
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
            ],
            [
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
            ],
            [
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
            ],
            [
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
            ],
        ];
    }

    /**
     * Data provider method for 'testThatGetLimitReturnsExpectedValue' test.
     *
     * @return array
     */
    public function dataProviderTestThatGetLimitReturnsExpectedValue(): array
    {
        return [
            [
                ['limit' => 10],
                10,
            ],
            [
                ['limit' => 'ddd'],
                0,
            ],
            [
                ['limit' => 'E10'],
                0,
            ],
            [
                ['limit' => -10],
                10,
            ],
        ];
    }

    /**
     * Data provider method for 'testThatGetOffsetReturnsExpectedValue' test.
     *
     * @return array
     */
    public function dataProviderTestThatGetOffsetReturnsExpectedValue(): array
    {
        return [
            [
                ['offset' => 10],
                10,
            ],
            [
                ['offset' => 'ddd'],
                0,
            ],
            [
                ['offset' => 'E10'],
                0,
            ],
            [
                ['offset' => -10],
                10,
            ],
        ];
    }

    /**
     * Data provider method for 'testThatGetSearchTermsReturnsExpectedValue' test.
     *
     * @return array
     */
    public function dataProviderTestThatGetSearchTermsReturnsExpectedValue(): array
    {
        return [
            [
                [
                    'or' => [
                        '1',
                    ],
                ],
                true,
            ],
            [
                [
                    'or' => [
                        'bar',
                    ],
                ],
                'bar',
            ],
            [
                [
                    'or' => [
                        'bar',
                        'foo',
                    ],
                ],
                'bar foo',
            ],
            [
                [
                    'or' => [
                        'bar',
                        'f',
                        'oo',
                    ],
                ],
                'bar  f    oo ',
            ],
            [
                [
                    'and' => [
                        'foo',
                    ],
                ],
                '{"and": ["foo"]}'
            ],
            [
                [
                    'or' => [
                        'bar',
                    ],
                ],
                '{"or": ["bar"]}'
            ],
            [
                [
                    'and' => [
                        'foo',
                        'bar',
                    ],
                ],
                '{"and": ["foo", "bar"]}'
            ],
            [
                [
                    'or' => [
                        'bar',
                        'foo',
                    ],
                ],
                '{"or": ["bar", "foo"]}'
            ],
            [
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
            ],
            [
                [
                    'or' => [
                        '{"or":',
                        '["bar",',
                        '"foo"],',
                    ],
                ],
                '{"or": ["bar", "foo"], ', // With invalid JSON input it should fallback to string handling
            ],
        ];
    }
}
