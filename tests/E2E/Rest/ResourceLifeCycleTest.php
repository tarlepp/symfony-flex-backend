<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Rest/ResourceLifeCycleTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\E2E\Rest;

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
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ResourceLifeCycleTest extends WebTestCase
{
    private RoleRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel();

        $this->repository = static::$kernel->getContainer()->get(ResourceForLifeCycleTests::class)->getRepository();
    }

    /**
     * @dataProvider dataProviderTestThatModifiedEntityIsNotFlushedIfLifeCycleMethodThrowsAnException
     *
     * @throws Throwable
     *
     * @testdox Test that modified entity ($role) is not flushed if life cycle method throws exception
     */
    public function testThatModifiedEntityIsNotFlushedIfLifeCycleMethodThrowsAnException(string $role): void
    {
        $client = $this->getTestClient();
        $client->request('GET', '/test_lifecycle_behaviour/' . $role);

        $response = $client->getResponse();
        $entity = $this->repository->findOneBy(['id' => $role]);

        static::assertSame(418, $response->getStatusCode(), $response->getContent());
        static::assertSame('Description - ' . $role, $entity->getDescription());
    }

    public function dataProviderTestThatModifiedEntityIsNotFlushedIfLifeCycleMethodThrowsAnException(): Generator
    {
        yield [RolesService::ROLE_ADMIN];
        yield [RolesService::ROLE_API];
        yield [RolesService::ROLE_LOGGED];
        yield [RolesService::ROLE_ROOT];
        yield [RolesService::ROLE_USER];
    }
}
