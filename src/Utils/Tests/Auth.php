<?php
declare(strict_types = 1);
/**
 * /src/Utils/Tests/Auth.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Utils\Tests;

use App\Utils\JSON;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use UnexpectedValueException;
use function array_key_exists;
use function array_merge;
use function compact;
use function getenv;
use function sha1;
use function str_pad;
use function sys_get_temp_dir;

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
    private $testContainer;

    /**
     * Auth constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->testContainer = $container;
    }

    /**
     * Method to get authorization headers for specified user.
     *
     * @param string $username
     * @param string $password
     *
     * @return mixed[]
     *
     * @throws Throwable
     */
    public function getAuthorizationHeadersForUser(string $username, string $password): array
    {
        // Return valid authorization headers for user
        return $this->getAuthorizationHeaders($this->getToken($username, $password));
    }

    /**
     * Method to get authorization headers for specified API Key role.
     *
     * @param string $role
     *
     * @return mixed[]
     */
    public function getAuthorizationHeadersForApiKey(string $role): array
    {
        return array_merge(
            $this->getContentTypeHeader(),
            [
                'HTTP_AUTHORIZATION' => 'ApiKey ' . str_pad($role, 40, '_'),
            ]
        );
    }

    /**
     * Method to get authorization headers for specified token.
     *
     * @param string $token
     *
     * @return mixed[]
     */
    public function getAuthorizationHeaders(string $token): array
    {
        return array_merge(
            $this->getContentTypeHeader(),
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ]
        );
    }

    /**
     * @return mixed[]
     */
    public function getJwtHeaders(): array
    {
        return [
            'REMOTE_ADDR' => '123.123.123.123',
            'HTTP_USER_AGENT' => 'foobar',
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
     * @throws UnexpectedValueException
     */
    private function getToken(string $username, string $password): string
    {
        // Specify used cache file
        $filename = sprintf(
            '%s%stest_jwt_auth_cache%s.json',
            sys_get_temp_dir(),
            DIRECTORY_SEPARATOR,
            (string)getenv('ENV_TEST_CHANNEL_READABLE')
        );

        // Read current cache
        /** @var array<string, string> $cache */
        $cache = (array)JSON::decode((string)file_get_contents($filename), true);

        // Create hash for username + password
        $hash = sha1($username . $password);

        // User + password doesn't exists on cache - so we need to make real login
        if (!array_key_exists($hash, $cache)) {
            // Get client
            /** @noinspection MissingService */
            /** @var KernelBrowser $client */
            $client = $this->testContainer->get('test.client');

            // Create request to make login using given credentials
            $client->request(
                'POST',
                '/auth/getToken',
                [],
                [],
                array_merge(
                    $this->getJwtHeaders(),
                    $this->getContentTypeHeader(),
                    [
                        'HTTP_X-Requested-With' => 'XMLHttpRequest',
                    ]
                ),
                JSON::encode(compact('username', 'password'))
            );

            /** @var Response|null $response */
            $response = $client->getResponse();

            if ($response === null) {
                throw new UnexpectedValueException('Test client did not return response at all');
            }

            if ($response->getStatusCode() !== 200) {
                throw new UnexpectedValueException(
                    'Invalid status code: ' . $response->getStatusCode() . " Response:\n" . $response
                );
            }

            /** @var object $payload */
            $payload = JSON::decode($response->getContent());

            $cache[$hash] = property_exists($payload, 'token') ? (string)$payload->token : '';
        }

        // And finally store cache for later usage
        file_put_contents($filename, JSON::encode($cache));

        return $cache[$hash];
    }

    /**
     * @return mixed[]
     */
    private function getContentTypeHeader(): array
    {
        return [
            'CONTENT_TYPE' => 'application/json',
        ];
    }
}
