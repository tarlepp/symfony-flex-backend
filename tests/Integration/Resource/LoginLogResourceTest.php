<?php
declare(strict_types=1);
/**
 * /tests/Integration/Resource/LoginLogResourceTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Resource;

use App\Entity\LoginLog;
use App\Repository\LoginLogRepository;
use App\Resource\LoginLogResource;

/**
 * Class LoginLogResourceTest
 *
 * @package App\Tests\Integration\Resource
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LoginLogResourceTest extends ResourceTestCase
{
    protected $entityClass = LoginLog::class;
    protected $resourceClass = LoginLogResource::class;
    protected $repositoryClass = LoginLogRepository::class;
}
