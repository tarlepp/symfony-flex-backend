<?php
declare(strict_types = 1);

/**
 * /tests/Unit/TestCase/WebTestCaseTest.php
 */

namespace App\Tests\Unit\TestCase;

use App\Tests\E2E\TestCase\WebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Component\HttpFoundation\Response;

final class WebTestCaseTest extends WebTestCase
{
    #[TestDox('Response status assertion accepts the expected status code')]
    public function testThatResponseStatusCodeMatches(): void
    {
        self::assertResponseStatusCode(
            Response::HTTP_UNAUTHORIZED,
            new Response('Unauthorized', Response::HTTP_UNAUTHORIZED),
        );
    }

    #[DataProvider('provideFailureMessages')]
    #[TestDox('Response status assertion preserves response details and custom context')]
    public function testThatResponseStatusCodeMismatchIncludesDiagnostics(string $message): void
    {
        $response = new Response('Access denied', Response::HTTP_FORBIDDEN, [
            'X-Test' => 'diagnostic',
        ]);

        try {
            self::assertResponseStatusCode(Response::HTTP_OK, $response, $message);
        } catch (ExpectationFailedException $error) {
            $expectedMessage = ($message === '' ? '' : $message . "\n") . "Response:\n" . (string)$response;

            self::assertStringContainsString($expectedMessage, $error->getMessage());

            return;
        }

        self::fail('The status assertion must reject a mismatching response.');
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function provideFailureMessages(): array
    {
        return [
            'response only' => [''],
            'custom context' => ['Request payload context'],
        ];
    }
}
