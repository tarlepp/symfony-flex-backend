<?php
declare(strict_types = 1);
/**
 * /tests/Integration/AutoMapper/ApiKey/RequestMapperTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\AutoMapper\ApiKey;

use App\AutoMapper\RestRequestMapper;
use App\AutoMapper\ApiKey\RequestMapper;
use App\DTO\ApiKey as DTO;
use App\Resource\UserGroupResource;
use App\Tests\Integration\AutoMapper\RestRequestMapperTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class RequestMapperTest
 *
 * @package App\Tests\Integration\AutoMapper\User
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RequestMapperTest extends RestRequestMapperTestCase
{
    /**
     * @var string
     */
    protected $mapperClass = RequestMapper::class;

    /**
     * @var RestRequestMapper|RequestMapper
     */
    protected $mapperObject;

    /**
     * @var string[]
     */
    protected $restDtoClasses = [
        DTO\ApiKey::class,
        DTO\ApiKeyCreate::class,
        DTO\ApiKeyUpdate::class,
        DTO\ApiKeyPatch::class,
    ];

    /**
     * @var MockObject|UserGroupResource
     */
    protected $userGroupResource;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userGroupResource = $this->getMockBuilder(UserGroupResource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mapperObject = new RequestMapper($this->userGroupResource);
    }
}
