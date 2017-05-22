<?php
declare(strict_types=1);
/**
 * /tests/bootstrap.php
 *
 * Bootstrap for PHPUnit tests, basically we need to do following things:
 *  1) Load test environment variables
 *  2) Boot kernel and create console application with that
 *  3) Drop test environment database
 *  4) Create empty database to test environment
 *  5) Update database schema
 *  6) Create user roles to database
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
use App\Kernel;
use Doctrine\Bundle\DoctrineBundle\Command\CreateDatabaseDoctrineCommand;
use Doctrine\Bundle\DoctrineBundle\Command\DropDatabaseDoctrineCommand;
use Doctrine\Bundle\DoctrineBundle\Command\Proxy\UpdateSchemaDoctrineCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

// Load test environment variables
(new Dotenv())->load(__DIR__.'/../.env.test');

// Create and boot 'test' kernel
$kernel = new Kernel(getenv('APP_ENV'), getenv('APP_DEBUG'));
$kernel->boot();

// Create new application
$application = new Application($kernel);

// Add the doctrine:database:drop command to the application and run it
$dropDatabaseDoctrineCommand = function () use ($application) {
    $command = new DropDatabaseDoctrineCommand();
    $application->add($command);

    $input = new ArrayInput([
        'command' => 'doctrine:database:drop',
        '--force' => true,
    ]);

    $input->setInteractive(false);

    $command->run($input, new ConsoleOutput());
};

// Add the doctrine:database:create command to the application and run it
$createDatabaseDoctrineCommand = function () use ($application) {
    $command = new CreateDatabaseDoctrineCommand();
    $application->add($command);

    $input = new ArrayInput([
        'command' => 'doctrine:database:create',
    ]);

    $input->setInteractive(false);

    $command->run($input, new ConsoleOutput());
};

// Add the doctrine:schema:update command to the application and run it
$updateSchemaDoctrineCommand = function () use ($application) {
    $command = new UpdateSchemaDoctrineCommand();
    $application->add($command);

    $input = new ArrayInput([
        'command' => 'doctrine:schema:update',
        '--force' => true,
    ]);

    $input->setInteractive(false);

    $command->run($input, new ConsoleOutput());
};

// Add the user:create-roles command to the application and run it
$createUserRolesCommand = function () use ($application) {
    $container = $application->getKernel()->getContainer();

    /** @var \Doctrine\ORM\EntityManagerInterface $entityManager */
    $entityManager = $container->get('doctrine.orm.entity_manager');

    /** @var App\Repository\RoleRepository $roleRepository */
    $roleRepository = $container->get(App\Repository\RoleRepository::class);

    /** @var App\Security\RolesInterface $rolesInterface */
    $rolesInterface = $container->get(App\Security\Roles::class);

    $command = new \App\Command\User\CreateRolesCommand(null, $entityManager, $roleRepository, $rolesInterface);
    $application->add($command);

    $input = new ArrayInput([
        'command' => 'user:create-roles',
    ]);

    $input->setInteractive(false);

    $command->run($input, new ConsoleOutput());
};

// And finally call each of initialize functions to make test environment ready
\array_map(
    '\call_user_func',
    [
        $dropDatabaseDoctrineCommand,
        $createDatabaseDoctrineCommand,
        $updateSchemaDoctrineCommand,
        $createUserRolesCommand,
    ]
);
