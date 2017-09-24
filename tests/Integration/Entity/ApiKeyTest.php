<?php
declare(strict_types=1);
/**
 * /tests/Integration/Entity/ApiKeyTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Entity;

use App\Entity\ApiKey;

/**
 * Class ApiKeyTest
 *
 * @package App\Tests\Integration\Entity
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @property ApiKey $entity
 */
class ApiKeyTest extends EntityTestCase
{
    /**
     * @var string
     */
    protected $entityName = ApiKey::class;

    public function testThatTokenIsGenerated(): void
    {
        static::assertSame(40, \strlen($this->entity->getToken()));
    }
}
