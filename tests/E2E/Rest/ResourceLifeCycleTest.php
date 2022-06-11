<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Rest/ResourceLifeCycleTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Rest;

use App\Repository\RoleRepository;
use App\Security\Interfaces\RolesServiceInterface;
use App\Tests\E2E\Rest\src\Resource\ResourceForLifeCycleTests;
use App\Utils\Tests\WebTestCase;
use Generator;
use Throwable;
use function sprintf;

/**
 * Class ResourceLifeCycleTest
 *
 * @package App\Tests\E2E\Rest
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class ResourceLifeCycleTest extends WebTestCase
{
    /**
     * @dataProvider dataProviderTestThatModifiedEntityIsNotFlushedIfLifeCycleMethodThrowsAnException
     *
     * @throws Throwable
     *
     * @testdox Test that modified entity `$role` is not flushed if life cycle method throws exception
     */
    public function testThatModifiedEntityIsNotFlushedIfLifeCycleMethodThrowsAnException(string $role): void
    {
        $client = $this->getTestClient();
        $client->request('GET', '/test_lifecycle_behaviour/' . $role);

        $response = $client->getResponse();
        $entity = $this->getRepository()->findOneBy([
            'id' => $role,
        ]);

        self::assertNotNull($entity, sprintf('Role entity for id `%s` not found...', $role));
        self::assertSame(418, $response->getStatusCode(), (string)$response->getContent());
        self::assertSame('Description - ' . $role, $entity->getDescription());
    }

    /**
     * @return Generator<array<int, string>>
     */
    public function dataProviderTestThatModifiedEntityIsNotFlushedIfLifeCycleMethodThrowsAnException(): Generator
    {
        yield [RolesServiceInterface::ROLE_ADMIN];
        yield [RolesServiceInterface::ROLE_API];
        yield [RolesServiceInterface::ROLE_LOGGED];
        yield [RolesServiceInterface::ROLE_ROOT];
        yield [RolesServiceInterface::ROLE_USER];
    }

    private function getRepository(): RoleRepository
    {
        $resource = self::getContainer()->get(ResourceForLifeCycleTests::class);

        self::assertInstanceOf(ResourceForLifeCycleTests::class, $resource);

        return $resource->getRepository();
    }
}
