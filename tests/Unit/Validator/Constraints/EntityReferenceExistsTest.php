<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Validator/Constraints/EntityReferenceExistsTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Unit\Validator\Constraints;

use App\Validator\Constraints\EntityReferenceExists;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class EntityReferenceExistsTest
 *
 * @package App\Tests\Unit\Validator\Constraints
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class EntityReferenceExistsTest extends KernelTestCase
{
    /**
     * @testdox Test that `getTargets` method returns expected
     */
    public function testThatGetTargetsReturnsExpected(): void
    {
        static::assertSame('property', (new EntityReferenceExists())->getTargets());
    }
}
