<?php
declare(strict_types=1);
/**
 * /tests/E2E/Rest/Traits/Actions/AuthenticatedActionsTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\E2E\Rest\Traits\Actions;

use App\Utils\Tests\RestTraitTestCase;

/**
 * Class AuthenticatedActionsTest
 *
 * @package App\Tests\E2E\Rest\Traits\Actions
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class AuthenticatedActionsTest extends RestTraitTestCase
{
    /**
     * @var string
     */
    protected static $route = '/test_authenticated_actions';

    /**
     * @return mixed[]
     */
    public function getValidUsers(): array
    {
        return [
            ['john-root',   'password-root'],
            ['john-admin',  'password-admin'],
            ['john-user',   'password-user'],
            ['john-logged', 'password-logged'],
            ['john',        'password'],
        ];
    }

    /**
     * @return mixed[]
     */
    public function getInvalidUsers(): array
    {
        return [
            [null, null],
        ];
    }
}
