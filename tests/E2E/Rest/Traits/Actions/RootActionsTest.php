<?php
declare(strict_types=1);
/**
 * /tests/E2E/Rest/Traits/Actions/RootActionsTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\E2E\Rest\Traits\Actions;

use App\Utils\Tests\RestTraitTestCase;

/**
 * Class RootActionsTest
 *
 * @package App\Tests\E2E\Rest\Traits\Actions
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RootActionsTest extends RestTraitTestCase
{
    /**
     * @var string
     */
    protected static $route = '/test_root_actions';

    /**
     * @return mixed[]
     */
    public function getValidUsers(): array
    {
        return [
            ['john-root',   'password-root'],
        ];
    }

    /**
     * @return mixed[]
     */
    public function getInvalidUsers(): array
    {
        return [
            [null,          null],
            ['john',        'password'],
            ['john-logged', 'password-logged'],
            ['john-user',   'password-user'],
            ['john-admin',  'password-admin'],
        ];
    }
}
