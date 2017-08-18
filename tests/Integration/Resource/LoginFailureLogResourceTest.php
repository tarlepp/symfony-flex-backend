<?php
declare(strict_types=1);
/**
 * /tests/Integration/Resource/LoginFailureLogResourceTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Resource;

use App\Entity\LoginFailureLog;
use App\Repository\LoginFailureLogRepository;
use App\Resource\LoginFailureLogResource;

/**
 * Class LoginFailureLogResourceTest
 *
 * @package App\Tests\Integration\Resource
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LoginFailureLogResourceTest extends ResourceTestCase
{
    protected $entityClass = LoginFailureLog::class;
    protected $resourceClass = LoginFailureLogResource::class;
    protected $repositoryClass = LoginFailureLogRepository::class;
}
