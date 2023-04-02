<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Rest/Traits/Actions/AdminActionsTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\E2E\Rest\Traits\Actions;

use App\Tests\E2E\TestCase\RestTraitTestCase;
use Generator;
use function getenv;

/**
 * Class AdminActionsTest
 *
 * @package App\Tests\E2E\Rest\Traits\Actions
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class AdminActionsTest extends RestTraitTestCase
{
    protected static string $route = '/test_admin_actions';

    public function getValidUsers(): Generator
    {
        yield ['john-admin', 'password-admin'];

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['john-root', 'password-root'];
        }

        yield ['john.doe-admin@test.com', 'password-admin'];

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['john.doe-root@test.com', 'password-root'];
        }
    }

    public function getInvalidUsers(): Generator
    {
        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield [null, null];
            yield ['john', 'password'];
            yield ['john-logged', 'password-logged'];
            yield ['john-api', 'password-api'];
        }

        yield ['john-user', 'password-user'];

        if (getenv('USE_ALL_USER_COMBINATIONS') === 'yes') {
            yield ['john.doe@test.com', 'password'];
            yield ['john.doe-logged@test.com', 'password-logged'];
            yield ['john.doe-api@test.com', 'password-api'];
        }

        yield ['john.doe-user@test.com', 'password-user'];
    }
}
