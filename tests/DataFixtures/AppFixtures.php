<?php
declare(strict_types = 1);

/**
 * /tests/DataFixtures/AppFixtures.php
 */

namespace App\Tests\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Override;

final class AppFixtures extends Fixture
{
    #[Override]
    public function load(ObjectManager $manager): void
    {
        $manager->flush();
    }
}
