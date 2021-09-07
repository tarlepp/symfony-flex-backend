<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/v1/Localization/LocaleControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\v1\Localization;

use App\Utils\JSON;
use App\Utils\Tests\WebTestCase;
use Generator;
use Throwable;

/**
 * Class LocaleControllerTest
 *
 * @package App\Tests\E2E\Controller\v1\Localization
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class LocaleControllerTest extends WebTestCase
{
    private string $baseUrl = '/v1/localization/locale';

    /**
     * @dataProvider dataProviderTestThatLocaleRouteDoesNotAllowOtherMethodThanGet
     *
     * @throws Throwable
     *
     * @testdox Test that `/v1/localization/locale` endpoint returns 405 with `$method` method
     */
    public function testThatLocaleRouteDoesNotAllowOtherMethodThanGet(string $method): void
    {
        $client = $this->getTestClient();
        $client->request($method, $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        static::assertNotFalse($content);
        static::assertSame(405, $response->getStatusCode(), $content);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `/v1/localization/locale` endpoint returns 200 with `GET` method
     */
    public function testThatLocaleRouteReturns200(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        static::assertNotFalse($content);
        static::assertSame(200, $response->getStatusCode(), $content);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `/v1/localization/locale` endpoint returns expected count of locales (2)
     */
    public function testThatLocaleRouteReturnsExpectedNumberOfLocales(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        static::assertNotFalse($content);
        static::assertCount(2, JSON::decode($content));
    }

    /**
     * @return Generator<array{0: string}>
     */
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
