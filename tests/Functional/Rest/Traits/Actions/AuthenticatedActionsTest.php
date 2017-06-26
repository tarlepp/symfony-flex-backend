<?php
declare(strict_types=1);
/**
 * /tests/Functional/Rest/Traits/Actions/AuthenticatedActionsTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Functional\Rest\Traits\Actions;

use App\Utils\Tests\RestTraitTestCase;

/**
 * Class AuthenticatedActionsTest
 *
 * @package App\Tests\Functional\Rest\Traits\Actions
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class AuthenticatedActionsTest extends RestTraitTestCase
{
    /**
     * @var string
     */
    protected static $route = '/test_authenticated_actions';

    /**
     * @return array
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
     * @return array
     */
    public function getInvalidUsers(): array
    {
        return [
            [null, null],
        ];
    }
}
