<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Rest/Traits/Actions/AnonActionsTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Rest\Traits\Actions;

use App\Tests\E2E\TestCase\RestTraitTestCase;
use Generator;
use function getenv;

/**
 * Class AnonActionsTest
 *
 * @package App\Tests\E2E\Rest\Traits\Actions
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class AnonActionsTest extends RestTraitTestCase
{
    protected static string $route = '/test_anon_actions';

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public function testThatCountRouteDoesNotAllowInvalidUser(
        ?string $u = null,
        ?string $p = null,
        ?string $m = null
    ): void {
        static::markTestSkipped('There isn\'t invalid users, so cannot test this.');
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public function testThatRootRouteDoesNotAllowInvalidUser(
        ?string $u = null,
        ?string $p = null,
        ?string $m = null
    ): void {
        static::markTestSkipped('There isn\'t invalid users, so cannot test this.');
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public function testThatUuidRouteWithIdDoesNotAllowInvalidUser(
        string $uuid = '',
        ?string $u = null,
        ?string $p = null,
        ?string $m = null
    ): void {
        static::markTestSkipped('There isn\'t invalid users, so cannot test this.');
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public function testThatIdsRouteDoesNotAllowInvalidUser(
        ?string $u = null,
        ?string $p = null,
        ?string $m = null
    ): void {
        static::markTestSkipped('There isn\'t invalid users, so cannot test this.');
    }

    public function getValidUsers(): Generator
    {
        yield [null, null];

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['john', 'password'];
            yield ['john-logged', 'password-logged'];
            yield ['john-api', 'password-api'];
            yield ['john-user', 'password-user'];
            yield ['john-admin', 'password-admin'];
            yield ['john-root', 'password-root'];
            yield ['john.doe@test.com', 'password'];
            yield ['john.doe-logged@test.com', 'password-logged'];
            yield ['john.doe-api@test.com', 'password-api'];
            yield ['john.doe-user@test.com', 'password-user'];
            yield ['john.doe-admin@test.com', 'password-admin'];
            yield ['john.doe-root@test.com', 'password-root'];
        }
    }

    /**
     * @psalm-suppress InvalidReturnType
     */
    public function getInvalidUsers(): Generator
    {
        yield from [];
    }
}
