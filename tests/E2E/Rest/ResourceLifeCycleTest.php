<?php
declare(strict_types=1);
/**
 * /tests/E2E/Rest/ResourceLifeCycleTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\E2E\Rest;

use App\Entity\Role;
use App\Repository\RoleRepository;
use App\Security\RolesService;
use App\Tests\E2E\Rest\src\Resource\ResourceForLifeCycleTests;
use App\Utils\Tests\WebTestCase;

/**
 * Class ResourceLifeCycleTest
 *
 * @package App\Tests\E2E\Rest
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ResourceLifeCycleTest extends WebTestCase
{
    /**
     * @var RoleRepository
     */
    private $repository;

    public function setUp(): void
    {
        parent::setUp();

        static::bootKernel();

        $this->repository = static::$kernel->getContainer()->get(ResourceForLifeCycleTests::class)->getRepository();
    }

    /**
     * @dataProvider dataProviderTestThatModifiedEntityIsNotFlushedIfLifeCycleMethodThrowsAnException
     *
     * @param string $role
     *
     * @throws \Exception
     */
    public function testThatModifiedEntityIsNotFlushedIfLifeCycleMethodThrowsAnException(string $role): void
    {
        $client = $this->getClient();
        $client->request('GET', '/test_lifecycle_behaviour/' . $role);

        $response = $client->getResponse();

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(418, $response->getStatusCode());

        $entity = $this->repository->findOneBy(['id' => $role]);

        if ($entity instanceof Role) {
            static::assertSame('Description - ' . $role, $entity->getDescription());
        }
    }

    /**
     * @return array
     */
    public function dataProviderTestThatModifiedEntityIsNotFlushedIfLifeCycleMethodThrowsAnException(): array
    {
        return [
            [RolesService::ROLE_ADMIN],
            [RolesService::ROLE_API],
            [RolesService::ROLE_LOGGED],
            [RolesService::ROLE_ROOT],
            [RolesService::ROLE_USER],
        ];
    }
}
