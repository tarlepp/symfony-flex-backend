<?php
declare(strict_types = 1);
/**
 * /src/Utils/Tests/WebTestCase.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Utils\Tests;

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
 * @package App\Tests
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
abstract class WebTestCase extends BaseWebTestCase
{
    private Auth $authService;

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

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        /** @var Auth $authService */
        $authService = self::$container->get('test.app.utils.tests.auth');

        $this->authService = $authService;
    }

    /**
     * Helper method to get authorized client for specified username and password.
     *
     * @param array<mixed>|null $options
     * @param array<mixed>|null $server
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

        // Merge authorization headers
        $server = array_merge(
            $username === null || $password === null
                ? []
                : $this->authService->getAuthorizationHeadersForUser($username, $password),
            array_merge($this->getJsonHeaders(), $this->getFastestHeaders()),
            $this->authService->getJwtHeaders(),
            $server
        );

        self::ensureKernelShutdown();

        return static::createClient(array_merge($options, ['debug' => false]), $server);
    }

    /**
     * Helper method to get authorized API Key client for specified role.
     *
     * @param array<mixed>|null $options
     * @param array<mixed>|null $server
     */
    public function getApiKeyClient(?string $role = null, ?array $options = null, ?array $server = null): KernelBrowser
    {
        $options ??= [];
        $server ??= [];

        // Merge authorization headers
        $server = array_merge(
            $role === null ? [] : $this->authService->getAuthorizationHeadersForApiKey($role),
            array_merge($this->getJsonHeaders(), $this->getFastestHeaders()),
            $this->authService->getJwtHeaders(),
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
            $output = [
                'X-FASTEST-ENV-TEST-CHANNEL-READABLE' => (string)getenv('ENV_TEST_CHANNEL_READABLE'),
            ];
        }

        return $output;
    }
}
