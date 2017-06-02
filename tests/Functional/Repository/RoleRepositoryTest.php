<?php
declare(strict_types=1);
/**
 * /tests/Functional/Repository/RoleRepositoryTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Functional\Repository;

use App\Command\User\CreateRolesCommand;
use App\Repository\RoleRepository;
use App\Security\Roles;
use App\Security\RolesInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class RoleRepositoryTest
 *
 * @package Functional\Repository
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RoleRepositoryTest extends KernelTestCase
{
    /**
     * @var RoleRepository;
     */
    private $repository;

    public function setUp(): void
    {
        parent::setUp();

        static::bootKernel();

        $this->repository = static::$kernel->getContainer()->get(RoleRepository::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $application = new Application(static::$kernel);
        $container = static::$kernel->getContainer();

        /** @var \Doctrine\ORM\EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');

        /** @var RoleRepository $roleRepository */
        $roleRepository = $container->get(RoleRepository::class);

        /** @var RolesInterface $rolesInterface */
        $rolesInterface = $container->get(Roles::class);

        $command = new CreateRolesCommand(null, $entityManager, $roleRepository, $rolesInterface);
        $application->add($command);

        $input = new ArrayInput([
            'command' => 'user:create-roles',
        ]);

        $input->setInteractive(false);

        $command->run($input, new ConsoleOutput());
    }

    public function testThatResetMethodDeletesAllRecords(): void
    {
        $this->repository->reset();

        self::assertSame(0, $this->repository->count([]));
    }
}
