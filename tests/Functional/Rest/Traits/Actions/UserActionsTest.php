<?php
declare(strict_types=1);
/**
 * /tests/Functional/Rest/Traits/Actions/UserActionsTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Functional\Rest\Traits\Actions;

use App\Utils\Tests\RestTraitTestCase;

/**
 * Class UserActionsTest
 *
 * @package App\Tests\Functional\Rest\Traits\Actions
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserActionsTest extends RestTraitTestCase
{
    /**
     * @var string
     */
    protected static $route = '/test_user_actions';

    /**
     * @return mixed[]
     */
    public function getValidUsers(): array
    {
        return [
            /*
            ['john-root',   'password-root'],
            ['john-admin',  'password-admin'],
            */
            ['john-user',   'password-user'],
        ];
    }

    /**
     * @return mixed[]
     */
    public function getInvalidUsers(): array
    {
        return [
            /*
            [null,          null],
            ['john',        'password'],
            */
            ['john-logged', 'password-logged'],
        ];
    }
}
