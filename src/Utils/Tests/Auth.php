<?php
declare(strict_types = 1);
/**
 * /src/Utils/Tests/Auth.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Utils\Tests;

use App\Utils\JSON;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

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
     * @return array
     */
    public function getJwtHeaders(): array
    {
        return [
            'REMOTE_ADDR'       => '123.123.123.123',
            'HTTP_USER_AGENT'   => 'foobar',
        ];
    }

    /**
     * Method to make actual login to application with specified username and password.
     *
     * @codeCoverageIgnore
     *
     * @param string $username
     * @param string $password
     *
     * @return string
     *
     * @throws \UnexpectedValueException
     */
    private function getToken(string $username, string $password): string
    {
        // Get client
        /** @noinspection MissingService */
        $client = $this->container->get('test.client');

        // Create request to make login using given credentials
        $client->request(
            'POST',
            '/auth/getToken',
            [],
            [],
            \array_merge(
                $this->getJwtHeaders(),
                [
                    'CONTENT_TYPE'          => 'application/json',
                    'HTTP_X-Requested-With' => 'XMLHttpRequest'
                ]
            ),
            \json_encode(['username' => $username, 'password' => $password])
        );

        /** @var Response $response */
        $response = $client->getResponse();

        if ($response === null) {
            throw new \UnexpectedValueException('Test client did not return response at all');
        }

        if ($response->getStatusCode() !== 200) {
            throw new \UnexpectedValueException('Invalid status code: '. $response->getStatusCode());
        }

        return JSON::decode($response->getContent())->token;
    }
}
