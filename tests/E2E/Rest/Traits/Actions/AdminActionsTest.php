<?php
declare(strict_types=1);
/**
 * /tests/E2E/Rest/Traits/Actions/AdminActionsTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\E2E\Rest\Traits\Actions;

use App\Utils\Tests\RestTraitTestCase;

/**
 * Class AdminActionsTest
 *
 * @package App\Tests\E2E\Rest\Traits\Actions
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class AdminActionsTest extends RestTraitTestCase
{
    /**
     * @var string
     */
    protected static $route = '/test_admin_actions';

    /**
     * @return mixed[]
     */
    public function getValidUsers(): array
    {
        return [
            /*
            ['john-root',   'password-root'],
            */
            ['john-admin',  'password-admin'],
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
            ['john-logged', 'password-logged'],
            */
            ['john-user',   'password-user'],
        ];
    }
}
