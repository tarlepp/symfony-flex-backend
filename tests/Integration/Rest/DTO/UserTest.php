<?php
declare(strict_types=1);
/**
 * /tests/Integration/Rest/DTO/UserTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Rest\DTO;

use App\Rest\DTO\User;

/**
 * Class UserTest
 *
 * @package App\Tests\Integration\Rest\DTO
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserTest extends DtoTestCase
{
    protected $dtoClass = User::class;
}
