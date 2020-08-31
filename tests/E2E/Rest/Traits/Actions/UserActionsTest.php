<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Rest/Traits/Actions/UserActionsTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\E2E\Rest\Traits\Actions;

use App\Utils\Tests\RestTraitTestCase;
use Generator;

/**
 * Class UserActionsTest
 *
 * @package App\Tests\E2E\Rest\Traits\Actions
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserActionsTest extends RestTraitTestCase
{
    protected static string $route = '/test_user_actions';

    public function getValidUsers(): Generator
    {
        //yield ['john-root', 'password-root'];
        //yield ['john-admin', 'password-admin'];
        yield ['john-user', 'password-user'];
    }

    public function getInvalidUsers(): Generator
    {
        //yield [null, null];
        //yield ['john', 'password'];
        yield ['john-logged', 'password-logged'];
    }
}
