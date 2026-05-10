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
use Override;
use function getenv;

/**
 * @package App\Tests\E2E\Rest\Traits\Actions
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class AnonActionsTest extends RestTraitTestCase
{
    private const string SKIPPED_INVALID_USER_TEST_MESSAGE = 'There are no invalid users, so this cannot be tested.';

    protected static string $route = '/test_anon_actions';

    /**
     * This endpoint is anonymous by design, so invalid-user assertions are not applicable.
     *
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[Override]
    public function testThatCountRouteDoesNotAllowInvalidUser(
        ?string $u = null,
        ?string $p = null,
        ?string $m = null
    ): void {
        self::skipInvalidUserTest();
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[Override]
    public function testThatRootRouteDoesNotAllowInvalidUser(
        ?string $u = null,
        ?string $p = null,
        ?string $m = null
    ): void {
        self::skipInvalidUserTest();
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[Override]
    public function testThatUuidRouteWithIdDoesNotAllowInvalidUser(
        string $uuid,
        ?string $u = null,
        ?string $p = null,
        ?string $m = null
    ): void {
        self::skipInvalidUserTest();
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[Override]
    public function testThatIdsRouteDoesNotAllowInvalidUser(
        ?string $u = null,
        ?string $p = null,
        ?string $m = null
    ): void {
        self::skipInvalidUserTest();
    }

    #[Override]
    public static function getValidUsers(): Generator
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
     * @return Generator<int, array<int, string|null>>
     */
    #[Override]
    public static function getInvalidUsers(): Generator
    {
        yield from [];
    }

    private static function skipInvalidUserTest(): void
    {
        static::markTestSkipped(self::SKIPPED_INVALID_USER_TEST_MESSAGE);
    }
}
