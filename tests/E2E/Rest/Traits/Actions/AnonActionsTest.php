<?php
declare(strict_types = 1);
/**
 * /tests/E2E/Rest/Traits/Actions/AnonActionsTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\E2E\Rest\Traits\Actions;

use App\Utils\Tests\RestTraitTestCase;

/**
 * Class AnonActionsTest
 *
 * @package App\Tests\E2E\Rest\Traits\Actions
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class AnonActionsTest extends RestTraitTestCase
{
    /**
     * @var string
     */
    protected static $route = '/test_anon_actions';

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
            [null,          null],
        ];
    }

    /**
     * @return mixed[]
     */
    public function getInvalidUsers(): array
    {
        return [];
    }
}
