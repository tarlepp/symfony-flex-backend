<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Rest/ResourceLifeCycleTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Rest;

use App\Enum\Role;
use App\Repository\RoleRepository;
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
    public function testThatModifiedEntityIsNotFlushedIfLifeCycleMethodThrowsAnException(Role $role): void
    {
        $client = $this->getTestClient();
        $client->request('GET', '/test_lifecycle_behaviour/' . $role->value);

        $response = $client->getResponse();
        $entity = $this->getRepository()->findOneBy([
            'id' => $role->value,
        ]);

        self::assertNotNull($entity, sprintf('Role entity for id `%s` not found...', $role->value));
        self::assertSame(418, $response->getStatusCode(), (string)$response->getContent());
        self::assertSame('Description - ' . $role->getLabel(), $entity->getDescription());
    }

    /**
     * @return Generator<array{0: Role}>
     */
    public function dataProviderTestThatModifiedEntityIsNotFlushedIfLifeCycleMethodThrowsAnException(): Generator
    {
        yield [Role::ROLE_ADMIN];
        yield [Role::ROLE_API];
        yield [Role::ROLE_LOGGED];
        yield [Role::ROLE_ROOT];
        yield [Role::ROLE_USER];
    }

    private function getRepository(): RoleRepository
    {
        return self::getContainer()->get(ResourceForLifeCycleTests::class)->getRepository();
    }
}
