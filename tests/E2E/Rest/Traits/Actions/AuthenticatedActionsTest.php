<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Rest/Traits/Actions/AuthenticatedActionsTest.php
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
final class AuthenticatedActionsTest extends RestTraitTestCase
{
    protected static string $route = '/test_authenticated_actions';

    #[Override]
    public static function getValidUsers(): Generator
    {
        yield ['john', 'password'];

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['john-logged', 'password-logged'];
            yield ['john-api', 'password-api'];
            yield ['john-user', 'password-user'];
            yield ['john-admin', 'password-admin'];
            yield ['john-root', 'password-root'];
        }

        yield ['john.doe@test.com', 'password'];

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['john.doe-logged@test.com', 'password-logged'];
            yield ['john.doe-api@test.com', 'password-api'];
            yield ['john.doe-user@test.com', 'password-user'];
            yield ['john.doe-admin@test.com', 'password-admin'];
            yield ['john.doe-root@test.com', 'password-root'];
        }
    }

    #[Override]
    public static function getInvalidUsers(): Generator
    {
        yield [null, null];
    }
}
