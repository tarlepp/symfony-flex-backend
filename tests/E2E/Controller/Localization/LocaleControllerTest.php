<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/Localization/LocaleControllerTest.php
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
 * Class LocaleControllerTest
 *
 * @package App\Tests\E2E\Controller\Localization
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LocaleControllerTest extends WebTestCase
{
    private string $baseUrl = 'localization/locale';

    /**
     * @dataProvider dataProviderTestThatLocaleRouteDoesNotAllowOtherMethodThanGet
     *
     * @throws Throwable
     *
     * @testdox Test that `/localization/locale` endpoint returns 405 with `$method` method.
     */
    public function testThatLocaleRouteDoesNotAllowOtherMethodThanGet(string $method): void
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
     * @testdox Test that `/localization/locale` endpoint returns 200 with `GET` method.
     */
    public function testThatLocaleRouteReturns200(): void
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
     * @testdox Test that `/localization/locale` endpoint returns expected count of locales (2).
     */
    public function testThatLocaleRouteReturnsExpectedNumberOfLocales(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();

        $data = JSON::decode($response->getContent());

        static::assertCount(2, $data);
    }

    public function dataProviderTestThatLocaleRouteDoesNotAllowOtherMethodThanGet(): Generator
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
