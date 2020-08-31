<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Rest/Traits/Actions/LoggedActionsTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\E2E\Rest\Traits\Actions;

use App\Utils\Tests\RestTraitTestCase;
use Generator;

/**
 * Class LoggedActionsTest
 *
 * @package App\Tests\E2E\Rest\Traits\Actions
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LoggedActionsTest extends RestTraitTestCase
{
    protected static string $route = '/test_logged_actions';

    public function getValidUsers(): Generator
    {
        //yield ['john-root',   'password-root'];
        //yield ['john-admin',  'password-admin'];
        //yield ['john-user',   'password-user'];
        yield ['john-logged', 'password-logged'];
    }

    public function getInvalidUsers(): Generator
    {
        //yield [null,   null];
        yield ['john', 'password'];
    }
}
