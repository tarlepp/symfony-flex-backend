<?php
declare(strict_types = 1);
/**
 * /tests/DataFixtures/AppFixtures.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Override;

/**
 * @package App\DataFixtures
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class AppFixtures extends Fixture
{
    #[Override]
    public function load(ObjectManager $manager): void
    {
        $manager->flush();
    }
}
