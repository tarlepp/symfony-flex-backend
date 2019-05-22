<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Resource/CollectionTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Resource;

use App\Resource\Collection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PropertyAccess\Tests\Fixtures\TraversableArrayObject;

/**
 * Class CollectionTest
 *
 * @package App\Tests\Integration\Resource
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class CollectionTest extends KernelTestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Resource 'FooBar' does not exists
     */
    public function testThatGetMethodThrowsAnException(): void
    {
        $collection = new Collection(new TraversableArrayObject());
        $collection->get('FooBar');

        unset($collection);
    }
}
