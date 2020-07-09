<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/Localization/LanguageControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\E2E\Controller\Localization;

use App\Utils\JSON;
use App\Utils\Tests\WebTestCase;
use Generator;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Class LanguageControllerTest
 *
 * @package App\Tests\E2E\Controller\Localization
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LanguageControllerTest extends WebTestCase
{
    private string $baseUrl = 'localization/language';

    /**
     * @dataProvider dataProviderTestThatLanguageRouteDoesNotAllowOtherMethodThanGet
     *
     * @throws Throwable
     *
     * @testdox Test that `/localization/language` endpoint returns 405 with `$method` method.
     */
    public function testThatLanguageRouteDoesNotAllowOtherMethodThanGet(string $method): void
    {
        $client = $this->getTestClient();
        $client->request($method, $this->baseUrl);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(405, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `/localization/language` endpoint returns 200 with `GET` method.
     */
    public function testThatLanguageRouteReturns200(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);
        static::assertSame(200, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `/localization/language` endpoint returns expected count of languages (2).
     */
    public function testThatLanguageRouteReturnsExpectedNumberOfLanguages(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();

        $data = JSON::decode($response->getContent());

        static::assertCount(2, $data);
    }

    public function dataProviderTestThatLanguageRouteDoesNotAllowOtherMethodThanGet(): Generator
    {
        yield ['PUT'];
        yield ['POST'];
        yield ['DELETE'];
        yield ['TRACE'];
        yield ['OPTIONS'];
        yield ['CONNECT'];
        yield ['PATCH'];
    }
}
