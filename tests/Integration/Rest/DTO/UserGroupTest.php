<?php
declare(strict_types=1);
/**
 * /tests/Integration/Rest/DTO/UserGroupTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Rest\DTO;

use App\Rest\DTO\UserGroup;

/**
 * Class UserGroupTest
 *
 * @package App\Tests\Integration\Rest\DTO
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserGroupTest extends DtoTestCase
{
    protected $dtoClass = UserGroup::class;
}
