<?php
declare(strict_types=1);
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

            $this->authService = $this->getContainer()->get(Auth::class);
        }

        return $this->authService;
    }

    /**
     * Helper method to get authorized client for specified username and password.
     *
     * @param string $username
     * @param string $password
     * @param array  $options
     * @param array  $server
     *
     * @return Client
     */
    public function getClient(string $username, string $password, array $options = null, array $server = null): Client
    {
        $options = $options ?? [];
        $server = $server ?? [];

        // Merge authorization headers
        $server = \array_merge(
            $this->getAuthService()->getAuthorizationHeadersForUser($username, $password),
            $this->getJsonHeaders(),
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
            'HTTP_X-Requested-With' => 'XMLHttpRequest'
        ];
    }
}
