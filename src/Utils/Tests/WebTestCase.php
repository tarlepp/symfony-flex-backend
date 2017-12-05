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
class WebTestCase extends BaseWebTestCase
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var Auth
     */
    private $authService;

    /**
     * Getter method for container
     *
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        if (!($this->container instanceof ContainerInterface)) {
            self::bootKernel();

            $this->container = static::$kernel->getContainer();
        }

        return $this->container;
    }

    /**
     * Getter method for auth service
     *
     * @return Auth
     */
    public function getAuthService(): Auth
    {
        if (!($this->authService instanceof Auth)) {
            // We need to boot kernel up to get auth service
            self::bootKernel();

            $this->authService = $this->getContainer()->get('test.service_locator')->get(Auth::class);
        }

        return $this->authService;
    }

    /**
     * Helper method to get authorized client for specified username and password.
     *
     * @param string|null $username
     * @param string|null $password
     * @param array|null  $options
     * @param array|null  $server
     *
     * @return Client
     *
     * @throws \Exception
     */
    public function getClient(
        string $username = null,
        string $password = null,
        array $options = null,
        array $server = null
    ): Client {
        $options = $options ?? [];
        $server = $server ?? [];

        // Merge authorization headers
        $server = \array_merge(
            $username === null ? [] : $this->getAuthService()->getAuthorizationHeadersForUser($username, $password),
            \array_merge($this->getJsonHeaders(), $this->getFastestHeaders()),
            $this->getAuthService()->getJwtHeaders(),
            $server
        );

        return static::createClient($options, $server);
    }

    /**
     * Helper method to get authorized API Key client for specified role.
     *
     * @param string|null $role
     * @param array|null  $options
     * @param array|null  $server
     *
     * @return Client
     */
    public function getApiKeyClient(string $role = null, array $options = null, array $server = null): Client
    {
        $options = $options ?? [];
        $server = $server ?? [];

        // Merge authorization headers
        $server = \array_merge(
            $role === null ? [] : $this->getAuthService()->getAuthorizationHeadersForApiKey($role),
            \array_merge($this->getJsonHeaders(), $this->getFastestHeaders()),
            $this->getAuthService()->getJwtHeaders(),
            $server
        );

        return static::createClient($options, $server);
    }

    /**
     * @return array
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
     * @return array
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
}
