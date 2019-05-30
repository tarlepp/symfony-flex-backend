<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Rest/ResourceLifeCycleTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\E2E\Rest;

use App\Entity\Role;
use App\Repository\RoleRepository;
use App\Security\RolesService;
use App\Tests\E2E\Rest\src\Resource\ResourceForLifeCycleTests;
use App\Utils\Tests\WebTestCase;
use Generator;
use Throwable;

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

    /**
     * @dataProvider dataProviderTestThatModifiedEntityIsNotFlushedIfLifeCycleMethodThrowsAnException
     *
     * @param string $role
     *
     * @throws Throwable
     */
    public function testThatModifiedEntityIsNotFlushedIfLifeCycleMethodThrowsAnException(string $role): void
    {
        $client = $this->getClient();
        $client->request('GET', '/test_lifecycle_behaviour/' . $role);

        $response = $client->getResponse();

        /** @noinspection NullPointerExceptionInspection */
        static::assertSame(418, $response->getStatusCode(), $response->getContent());

        $entity = $this->repository->findOneBy(['id' => $role]);

        if ($entity instanceof Role) {
            static::assertSame('Description - ' . $role, $entity->getDescription());
        }
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatModifiedEntityIsNotFlushedIfLifeCycleMethodThrowsAnException(): Generator
    {
        yield [RolesService::ROLE_ADMIN];
        yield [RolesService::ROLE_API];
        yield [RolesService::ROLE_LOGGED];
        yield [RolesService::ROLE_ROOT];
        yield [RolesService::ROLE_USER];
    }

    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel();

        $this->repository = static::$kernel->getContainer()->get(ResourceForLifeCycleTests::class)->getRepository();
    }
}
