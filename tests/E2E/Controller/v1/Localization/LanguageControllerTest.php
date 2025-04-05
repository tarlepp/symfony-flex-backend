<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/v1/Localization/LanguageControllerTest.php
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
final class LanguageControllerTest extends WebTestCase
{
    private string $baseUrl = '/v1/localization/language';

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatLanguageRouteDoesNotAllowOtherMethodThanGet')]
    #[TestDox('Test that `$method /v1/localization/language` request returns 405')]
    public function testThatLanguageRouteDoesNotAllowOtherMethodThanGet(string $method): void
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
    #[TestDox('Test that `GET /v1/localization/language` request returns `200`')]
    public function testThatLanguageRouteReturns200(): void
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
    #[TestDox('Test that `/v1/localization/language` request returns expected count of languages (2)')]
    public function testThatLanguageRouteReturnsExpectedNumberOfLanguages(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();
        $content = $response->getContent();

        self::assertNotFalse($content);
        self::assertJson($content);
        self::assertCount(2, JSON::decode($content));
    }

    /**
     * @return Generator<array{0: string}>
     */
    public static function dataProviderTestThatLanguageRouteDoesNotAllowOtherMethodThanGet(): Generator
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
