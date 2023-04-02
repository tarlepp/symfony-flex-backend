<?php
declare(strict_types = 1);
/**
 * /tests/E2E/TestCase/WebTestCase.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\TestCase;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;
use Throwable;
use function array_merge;
use function gc_collect_cycles;
use function gc_enable;
use function getenv;

/**
 * Class WebTestCase
 *
 * @package App\Tests\E2E\TestCase
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
abstract class WebTestCase extends BaseWebTestCase
{
    /**
     * @codeCoverageIgnore
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        gc_enable();
    }

    /**
     * @codeCoverageIgnore
     */
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
        ?array $server = null
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
                ...$this->getFastestHeaders(),
            ],
            $authService->getJwtHeaders(),
            $server
        );

        self::ensureKernelShutdown();

        return static::createClient(
            [
                ...$options,
                ...[
                    'debug' => false,
                ],
            ],
            $server
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
                ...$this->getFastestHeaders(),
            ],
            $authService->getJwtHeaders(),
            $server
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

    /**
     * @codeCoverageIgnore
     *
     * @return array<string, string>
     */
    public function getFastestHeaders(): array
    {
        $output = [];

        if (getenv('ENV_TEST_CHANNEL_READABLE')) {
            $testChannel = (string)getenv('ENV_TEST_CHANNEL_READABLE');

            $output = [
                'X-FASTEST-ENV-TEST-CHANNEL-READABLE' => $testChannel,
            ];
        }

        return $output;
    }

    private function getAuthService(): Auth
    {
        static::bootKernel();

        return static::getContainer()->get(Auth::class);
    }
}
