<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Rest/Traits/Actions/AdminActionsTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\E2E\Rest\Traits\Actions;

use App\Utils\Tests\RestTraitTestCase;
use Generator;

/**
 * Class AdminActionsTest
 *
 * @package App\Tests\E2E\Rest\Traits\Actions
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class AdminActionsTest extends RestTraitTestCase
{
    protected static string $route = '/test_admin_actions';

    public function getValidUsers(): Generator
    {
        //yield ['john-root', 'password-root'];
        yield ['john-admin', 'password-admin'];
    }

    public function getInvalidUsers(): Generator
    {
        //yield [null, null];
        //yield ['john', 'password'];
        //yield ['john-logged', 'password-logged'];
        yield ['john-user', 'password-user'];
    }
}
