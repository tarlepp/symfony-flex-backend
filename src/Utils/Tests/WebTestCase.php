<?php
declare(strict_types = 1);
/**
 * /src/Utils/Tests/WebTestCase.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Utils\Tests;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class WebTestCase
 *
 * @package App\Tests
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
abstract class WebTestCase extends BaseWebTestCase
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Auth
     */
    private $authService;

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
     * @param string|null  $username
     * @param string|null  $password
     * @param mixed[]|null $options
     * @param mixed[]|null $server
     *
     * @return Client
     *
     * @throws \Exception
     */
    public function getClient(
        ?string $username = null,
        ?string $password = null,
        ?array $options = null,
        ?array $server = null
    ): Client {
        $options = $options ?? [];
        $server = $server ?? [];

        // Merge authorization headers
        $server = \array_merge(
            $username === null ? [] : $this->authService->getAuthorizationHeadersForUser($username, $password),
            \array_merge($this->getJsonHeaders(), $this->getFastestHeaders()),
            $this->authService->getJwtHeaders(),
            $server
        );

        return static::createClient($options, $server);
    }

    /**
     * Helper method to get authorized API Key client for specified role.
     *
     * @param string|null  $role
     * @param mixed[]|null $options
     * @param mixed[]|null $server
     *
     * @return Client
     */
    public function getApiKeyClient(?string $role = null, ?array $options = null, ?array $server = null): Client
    {
        $options = $options ?? [];
        $server = $server ?? [];

        // Merge authorization headers
        $server = \array_merge(
            $role === null ? [] : $this->authService->getAuthorizationHeadersForApiKey($role),
            \array_merge($this->getJsonHeaders(), $this->getFastestHeaders()),
            $this->authService->getJwtHeaders(),
            $server
        );

        return static::createClient($options, $server);
    }

    /**
     * @return mixed[]
     */
    public function getJsonHeaders(): array
    {
        return [
            'CONTENT_TYPE'          => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ];
    }

    /**
     * @codeCoverageIgnore
     *
     * @return mixed[]
     */
    public function getFastestHeaders(): array
    {
        $output = [];

        if (\getenv('ENV_TEST_CHANNEL_READABLE')) {
            $output = [
                'X-FASTEST-ENV-TEST-CHANNEL-READABLE' => \getenv('ENV_TEST_CHANNEL_READABLE'),
            ];
        }

        return $output;
    }

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->container = static::$kernel->getContainer();
        $this->authService = $this->container->get('test.service_locator')->get(Auth::class);
    }
}
