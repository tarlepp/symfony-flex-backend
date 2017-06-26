<?php
declare(strict_types=1);
/**
 * /tests/Functional/Rest/Traits/Actions/AdminActionsTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Functional\Rest\Traits\Actions;

use App\Utils\Tests\RestTraitTestCase;

/**
 * Class AdminActionsTest
 *
 * @package App\Tests\Functional\Rest\Traits\Actions
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class AdminActionsTest extends RestTraitTestCase
{
    /**
     * @var string
     */
    protected static $route = '/test_admin_actions';

    /**
     * @return array
     */
    public function getValidUsers(): array
    {
        return [
            ['john-root',   'password-root'],
            ['john-admin',  'password-admin'],
        ];
    }

    /**
     * @return array
     */
    public function getInvalidUsers(): array
    {
        return [
            [null,          null],
            ['john',        'password'],
            ['john-logged', 'password-logged'],
            ['john-user',   'password-user'],
        ];
    }
}
