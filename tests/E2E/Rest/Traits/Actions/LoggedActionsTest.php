<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Rest/Traits/Actions/LoggedActionsTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Rest\Traits\Actions;

use App\Tests\E2E\TestCase\RestTraitTestCase;
use Generator;
use function getenv;

/**
 * Class LoggedActionsTest
 *
 * @package App\Tests\E2E\Rest\Traits\Actions
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class LoggedActionsTest extends RestTraitTestCase
{
    protected static string $route = '/test_logged_actions';

    public function getValidUsers(): Generator
    {
        yield ['john-logged', 'password-logged'];

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['john-api', 'password-api'];
            yield ['john-user', 'password-user'];
            yield ['john-admin', 'password-admin'];
            yield ['john-root', 'password-root'];
        }

        yield ['john.doe-logged@test.com', 'password-logged'];

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['john.doe-api@test.com', 'password-api'];
            yield ['john.doe-user@test.com', 'password-user'];
            yield ['john.doe-admin@test.com', 'password-admin'];
            yield ['john.doe-root@test.com', 'password-root'];
        }
    }

    public function getInvalidUsers(): Generator
    {
        yield [null, null];
        yield ['john', 'password'];

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['john.doe@test.com', 'password'];
        }
    }
}
