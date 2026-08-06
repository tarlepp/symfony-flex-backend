<?php
declare(strict_types = 1);

/**
 * /tests/E2E/TestCase/WebTestCase.php
 */

namespace App\Tests\E2E\TestCase;

use Override;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;
use Throwable;
use function array_merge;
use function gc_collect_cycles;
use function gc_enable;

abstract class WebTestCase extends BaseWebTestCase
{
    /**
     * @codeCoverageIgnore
     */
    #[Override]
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        gc_enable();
    }

    /**
     * @codeCoverageIgnore
     */
    #[Override]
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        gc_collect_cycles();
    }

    /**
     * Helper method to get authorized client for specified username and password.
     *
     * @param array<string, string>|null $options
     * @param array<string, string>|null $server
     *
     * @throws Throwable
     */
    public function getTestClient(
        ?string $username = null,
        ?string $password = null,
        ?array $options = null,
        ?array $server = null,
    ): KernelBrowser {
        $options ??= [];
        $server ??= [];

        $authService = $this->getAuthService();

        // Merge authorization headers
        $server = array_merge(
            $username === null || $password === null
                ? []
                : $authService->getAuthorizationHeadersForUser($username, $password),
            [
                ...$this->getJsonHeaders(),
            ],
            $authService->getJwtHeaders(),
            $server,
        );

        self::ensureKernelShutdown();

        return static::createClient(
            [
                ...$options,
                ...[
                    'debug' => false,
                ],
            ],
            $server,
        );
    }

    /**
     * Helper method to get authorized API Key client for specified role.
     *
     * @param array<string, string>|null $options
     * @param array<string, string>|null $server
     */
    public function getApiKeyClient(?string $role = null, ?array $options = null, ?array $server = null): KernelBrowser
    {
        $options ??= [];
        $server ??= [];

        $authService = $this->getAuthService();

        // Merge authorization headers
        $server = array_merge(
            $role === null
                ? [
                    'HTTP_AUTHORIZATION' => 'ApiKey invalid-api-key',
                ]
                : $authService->getAuthorizationHeadersForApiKey($role),
            [
                ...$this->getJsonHeaders(),
            ],
            $authService->getJwtHeaders(),
            $server,
        );

        self::ensureKernelShutdown();

        return static::createClient($options, $server);
    }

    /**
     * @return array<string, string>
     */
    public function getJsonHeaders(): array
    {
        return [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ];
    }

    private function getAuthService(): Auth
    {
        self::bootKernel();

        return static::getContainer()->get(Auth::class);
    }
}
