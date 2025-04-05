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
use App\Tests\E2E\TestCase\WebTestCase;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Throwable;
use function sprintf;

/**
 * @package App\Tests\E2E\Rest
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class ResourceLifeCycleTest extends WebTestCase
{
    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatModifiedEntityIsNotFlushedIfLifeCycleMethodThrowsAnException')]
    #[TestDox('Test that modified entity `$role` is not flushed if life cycle method throws exception')]
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
    public static function dataProviderTestThatModifiedEntityIsNotFlushedIfLifeCycleMethodThrowsAnException(): Generator
    {
        foreach (Role::cases() as $role) {
            yield [$role->value];
        }
    }

    /**
     * @throws Throwable
     */
    private function getRepository(): RoleRepository
    {
        return self::getContainer()->get(ResourceForLifeCycleTests::class)->getRepository();
    }
}
