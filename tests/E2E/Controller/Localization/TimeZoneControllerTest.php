<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/Localization/TimeZoneControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\Localization;

use App\Utils\JSON;
use App\Utils\Tests\WebTestCase;
use Generator;
use Throwable;

/**
 * Class TimeZoneControllerTest
 *
 * @package App\Tests\E2E\Controller\Localization
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class TimeZoneControllerTest extends WebTestCase
{
    private string $baseUrl = 'localization/timezone';

    /**
     * @dataProvider dataProviderTestThatTimeZoneRouteDoesNotAllowOtherMethodThanGet
     *
     * @throws Throwable
     *
     * @testdox Test that `/localization/timezone` endpoint returns 405 with `$method` method.
     */
    public function testThatTimeZoneRouteDoesNotAllowOtherMethodThanGet(string $method): void
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
     * @testdox Test that `/localization/timezone` endpoint returns 200 with `GET` method.
     */
    public function testThatTimeZoneRouteReturns200(): void
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
     * @testdox Test that `/localization/timezone` endpoint returns and array of timezones
     */
    public function testThatTimeZoneRouteReturnsAnArrayOfTimeZones(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        static::assertNotFalse($content);
        $data = JSON::decode((string)$response->getContent());

        static::assertIsArray($data, $content);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `/localization/timezone` endpoint returns data as in expected structure.
     */
    public function testThatTimeZoneRouteReturnsDataAsInExpectedStructure(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        static::assertNotFalse($content);
        $data = JSON::decode($content, true)[0];

        static::assertArrayHasKey('timezone', $data);
        static::assertArrayHasKey('identifier', $data);
        static::assertArrayHasKey('offset', $data);
        static::assertArrayHasKey('value', $data);
    }

    /**
     * @return Generator<array{0: string}>
     */
    public function dataProviderTestThatTimeZoneRouteDoesNotAllowOtherMethodThanGet(): Generator
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
