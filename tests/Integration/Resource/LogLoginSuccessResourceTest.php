<?php
declare(strict_types=1);
/**
 * /tests/Integration/Resource/LogLoginSuccessResourceTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Resource;

use App\Entity\LogLoginSuccess;
use App\Repository\LogLoginSuccessRepository;
use App\Resource\LogLoginSuccessResource;

/**
 * Class LogLoginSuccessResourceTest
 *
 * @package App\Tests\Integration\Resource
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LogLoginSuccessResourceTest extends ResourceTestCase
{
    protected $entityClass = LogLoginSuccess::class;
    protected $resourceClass = LogLoginSuccessResource::class;
    protected $repositoryClass = LogLoginSuccessRepository::class;
}
