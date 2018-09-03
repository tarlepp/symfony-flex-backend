<?php
declare(strict_types=1);
/**
 * /tests/E2E/Rest/Traits/Actions/LoggedActionsTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\E2E\Rest\Traits\Actions;

use App\Utils\Tests\RestTraitTestCase;

/**
 * Class LoggedActionsTest
 *
 * @package App\Tests\E2E\Rest\Traits\Actions
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LoggedActionsTest extends RestTraitTestCase
{
    /**
     * @var string
     */
    protected static $route = '/test_logged_actions';

    /**
     * @return mixed[]
     */
    public function getValidUsers(): array
    {
        return [
            /*
            ['john-root',   'password-root'],
            ['john-admin',  'password-admin'],
            ['john-user',   'password-user'],
            */
            ['john-logged', 'password-logged'],
        ];
    }

    /**
     * @return mixed[]
     */
    public function getInvalidUsers(): array
    {
        return [
            [null,      null],
            ['john',    'password'],
        ];
    }
}
