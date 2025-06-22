<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/v1/Localization/TimeZoneControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\v1\Localization;

use App\Tests\E2E\TestCase\WebTestCase;
use App\Utils\JSON;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Throwable;

/**
 * @package App\Tests\E2E\Controller\v1\Localization
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class TimeZoneControllerTest extends WebTestCase
{
    private string $baseUrl = '/v1/localization/timezone';

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatTimeZoneRouteDoesNotAllowOtherMethodThanGet')]
    #[TestDox('Test that `$method /v1/localization/timezone` request returns `405`')]
    public function testThatTimeZoneRouteDoesNotAllowOtherMethodThanGet(string $method): void
    {
        $client = $this->getTestClient();
        $client->request($method, $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(405, $response->getStatusCode(), $content);
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `GET /v1/localization/timezone` request returns `200`')]
    public function testThatTimeZoneRouteReturns200(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertSame(200, $response->getStatusCode(), $content);
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `GET /v1/localization/timezone` request returns and array of timezones')]
    public function testThatTimeZoneRouteReturnsAnArrayOfTimeZones(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        $data = JSON::decode((string)$response->getContent());

        self::assertIsArray($data, $content);
        self::assertNotEmpty($data);
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `GET /v1/localization/timezone` request returns data as in expected structure')]
    public function testThatTimeZoneRouteReturnsDataAsInExpectedStructure(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);

        $timezone = JSON::decode($content, true)[0];

        self::assertArrayHasKey('timezone', $timezone);
        self::assertArrayHasKey('identifier', $timezone);
        self::assertArrayHasKey('offset', $timezone);
        self::assertArrayHasKey('value', $timezone);
    }

    /**
     * @return Generator<array{0: string}>
     */
    public static function dataProviderTestThatTimeZoneRouteDoesNotAllowOtherMethodThanGet(): Generator
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
