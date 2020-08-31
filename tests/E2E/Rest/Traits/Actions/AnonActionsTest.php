<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Rest/Traits/Actions/AnonActionsTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\E2E\Rest\Traits\Actions;

use App\Utils\Tests\RestTraitTestCase;
use Generator;

/**
 * Class AnonActionsTest
 *
 * @package App\Tests\E2E\Rest\Traits\Actions
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class AnonActionsTest extends RestTraitTestCase
{
    protected static string $route = '/test_anon_actions';

    public function getValidUsers(): Generator
    {
        //yield ['john-root', 'password-root'];
        //yield ['john-admin', 'password-admin'];
        //yield ['john-user', 'password-user'];
        //yield ['john-logged', 'password-logged'];
        //yield ['john', 'password'];
        yield [null, null];
    }

    public function getInvalidUsers(): Generator
    {
        yield from [];
    }
}
