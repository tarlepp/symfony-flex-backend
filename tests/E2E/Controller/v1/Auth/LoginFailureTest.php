<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Controller/v1/Auth/LoginFailureTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Controller\v1\Auth;

use App\Resource\LogLoginFailureResource;
use App\Tests\Utils\PhpUnitUtil;
use App\Utils\JSON;
use Override;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Throwable;

/**
 * @package App\Tests\E2E\Controller\v1\Auth
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class LoginFailureTest extends WebTestCase
{
    private string $baseUrl = '/v1/auth/get_token';

    /**
     * @throws Throwable
     */
    #[Override]
    public static function tearDownAfterClass(): void
    {
        self::bootKernel();

        $kernel = self::$kernel;

        self::assertNotNull($kernel);

        PhpUnitUtil::loadFixtures($kernel);

        $kernel->shutdown();

        parent::tearDownAfterClass();
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `LogLoginFailure` entity is created when using valid user with invalid password')]
    public function testThatLogLoginFailureEntityIsCreated(): void
    {
        $client = static::createClient();

        $this->makeInvalidLoginRequest($client);

        $response = $client->getResponse();

        self::assertSame(401, $response->getStatusCode());

        /** @var LogLoginFailureResource $resource */
        $resource = static::getContainer()->get(LogLoginFailureResource::class);

        self::assertNotEmpty(
            $resource->find(),
            'Expected `LogLoginFailure` entity was not created',
        );
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `LogLoginFailure` entries are reset after successfully login')]
    public function testThatLogLoginFailuresAreResetAfterSuccessfullyLogin(): void
    {
        $client = static::createClient();

        /** @var LogLoginFailureResource $resource */
        $resource = static::getContainer()->get(LogLoginFailureResource::class);

        self::assertNotEmpty(
            $resource->find(),
            'There are no any `LogLoginFailure` entries in database',
        );

        $this->makeValidLoginRequest($client);

        $response = $client->getResponse();

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(
            [],
            $resource->find(),
            'There is `LogLoginFailure` entries in database, while there should not be any',
        );
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that locked user cannot log-in to application')]
    public function testThatLockedUserCannotLogin(): void
    {
        $client = static::createClient();

        for ($i = 0; $i <= 10; $i++) {
            $this->makeInvalidLoginRequest($client);

            $response = $client->getResponse();

            self::assertSame(401, $response->getStatusCode());
        }

        $this->makeValidLoginRequest($client);

        $response = $client->getResponse();

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('{"message":"Locked account.","code":0,"status":401}', $response->getContent());
    }

    /**
     * @throws Throwable
     */
    private function makeInvalidLoginRequest(KernelBrowser $client): void
    {
        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ];

        $content = JSON::encode([
            'username' => 'john',
            'password' => 'wrong-password',
        ]);

        $client->request('POST', $this->baseUrl, server: $server, content: $content);
    }

    /**
     * @throws Throwable
     */
    private function makeValidLoginRequest(KernelBrowser $client): void
    {
        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ];

        $content = JSON::encode([
            'username' => 'john',
            'password' => 'password',
        ]);

        $client->request('POST', $this->baseUrl, server: $server, content: $content);
    }
}
