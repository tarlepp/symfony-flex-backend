<?php
declare(strict_types=1);
/**
 * /tests/Integration/Resource/LogLoginFailureResourceTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Resource;

use App\Entity\LogLoginFailure;
use App\Repository\LogLoginFailureRepository;
use App\Resource\LogLoginFailureResource;

/**
 * Class LogLoginFailureResourceTest
 *
 * @package App\Tests\Integration\Resource
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LogLoginFailureResourceTest extends ResourceTestCase
{
    protected $entityClass = LogLoginFailure::class;
    protected $repositoryClass = LogLoginFailureRepository::class;
    protected $resourceClass = LogLoginFailureResource::class;
}
