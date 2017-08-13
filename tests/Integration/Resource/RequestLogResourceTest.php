<?php
declare(strict_types=1);
/**
 * /tests/Integration/Resource/RequestLogResourceTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Resource;

use App\Entity\RequestLog;
use App\Repository\RequestLogRepository;
use App\Resource\RequestLogResource;

/**
 * Class RequestLogResourceTest
 *
 * @package App\Tests\Integration\Resource
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RequestLogResourceTest extends ResourceTestCase
{
    protected $entityClass = RequestLog::class;
    protected $resourceClass = RequestLogResource::class;
    protected $repositoryClass = RequestLogRepository::class;
}
