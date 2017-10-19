<?php
declare(strict_types=1);
/**
 * /tests/Functional/Controller/UserGroupControllerTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Functional\Controller;

use App\Resource\UserGroupResource;
use App\Utils\JSON;
use App\Utils\Tests\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class UserGroupControllerTest
 *
 * @package App\Tests\Functional\Controller
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserGroupControllerTest extends WebTestCase
{
    private $baseUrl = '/user_group';

    /**
     * @covers \App\Controller\UserGroupController::findAction()
     */
    public function testThatGetBaseRouteReturn403(): void
    {
        $client = $this->getClient();
        $client->request('GET', $this->baseUrl);

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(401, $response->getStatusCode());
    }

    /**
     * @covers \App\Controller\UserGroupController::getUserGroupUsersAction()
     *
     * @dataProvider dataProviderTestThatGetUserGroupUsersActionReturnsExpected
     *
     * @param int    $userCount
     * @param string $userGroupId
     */
    public function testThatGetUserGroupUsersActionReturnsExpected(int $userCount, string $userGroupId): void
    {
        $client = $this->getClient('john-root', 'password-root');
        $client->request('GET', $this->baseUrl . '/' . $userGroupId . '/users');

        $response = $client->getResponse();

        static::assertInstanceOf(Response::class, $response);

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(200, $response->getStatusCode(), $response->getContent());

        /** @noinspection NullPointerExceptionInspection */
        static::assertCount($userCount, JSON::decode($response->getContent()));
    }

    /**
     * @return array
     */
    public function dataProviderTestThatGetUserGroupUsersActionReturnsExpected(): array
    {
        self::bootKernel();

        /** @var UserGroupResource $userGroupResource */
        $userGroupResource = static::$kernel->getContainer()->get(UserGroupResource::class);

        /** @noinspection NullPointerExceptionInspection */
        return [
            [1, $userGroupResource->findOneBy(['name' => 'Root users'])->getId()],
            [2, $userGroupResource->findOneBy(['name' => 'Admin users'])->getId()],
            [3, $userGroupResource->findOneBy(['name' => 'Normal users'])->getId()],
            [1, $userGroupResource->findOneBy(['name' => 'Api users'])->getId()],
            [5, $userGroupResource->findOneBy(['name' => 'Logged in users'])->getId()],
        ];
    }
}
