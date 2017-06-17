<?php
declare(strict_types=1);
/**
 * /src/Utils/Tests/Auth.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Utils\Tests;

use App\Utils\JSON;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Auth
 *
 * @package App\Utils\Tests
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class Auth
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * JWT cache
     *
     * @var \string[]
     */
    private $cache = [];

    /**
     * Auth constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Method to get authorization headers for specified user.
     *
     * @param string $username
     * @param string $password
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getAuthorizationHeadersForUser(string $username, string $password): array
    {
        $key = \hash('sha512', $username . $password);

        if (!\array_key_exists($key, $this->cache)) {
            $this->cache[$key] = $this->getToken($username, $password);
        }

        // Return valid authorization headers for user
        return $this->getAuthorizationHeaders($this->cache[$key]);
    }

    /**
     * Method to get authorization headers for specified token.
     *
     * @param string $token
     *
     * @return array
     */
    public function getAuthorizationHeaders(string $token): array
    {
        return [
            'CONTENT_TYPE'          => 'application/json',
            'HTTP_AUTHORIZATION'    => 'Bearer ' . $token
        ];
    }

    /**
     * Method to make actual login to application with specified username and password.
     *
     * @param string $username
     * @param string $password
     *
     * @return string
     *
     * @throws \DomainException
     */
    private function getToken(string $username, string $password): string
    {
        // Get client
        $client = $this->container->get('test.client');

        // Create request to make login using given credentials
        $client->request(
            'POST',
            '/auth/getToken',
            [],
            [],
            [
                'CONTENT_TYPE'          => 'application/json',
                'HTTP_X-Requested-With' => 'XMLHttpRequest'
            ],
            \json_encode(['username' => $username, 'password' => $password])
        );

        $response = $client->getResponse();

        return JSON::decode($response->getContent())->token;
    }
}
