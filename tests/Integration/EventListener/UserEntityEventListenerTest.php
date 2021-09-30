<?php
declare(strict_types = 1);
/**
 * /tests/Integration/EventListener/UserEntityEventListenerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\EventListener;

use App\Entity\User;
use App\EventListener\UserEntityEventListener;
use App\Security\SecurityUser;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use LengthException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Class UserEntityEventListenerTest
 *
 * @package App\Tests\Integration\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UserEntityEventListenerTest extends KernelTestCase
{
    /**
     * @testdox Test that too short password throws an exception with `prePersist` event
     */
    public function testThatTooShortPasswordThrowsAnExceptionWithPrePersist(): void
    {
        /** @var EntityManager $entityManager */
        $entityManager = static::getContainer()->get('doctrine.orm.default_entity_manager');

        /** @var UserPasswordHasherInterface $hasher */
        $hasher = static::getContainer()->get('security.user_password_hasher');

        $entity = (new User())
            ->setUsername('john_doe_the_tester')
            ->setEmail('john.doe_the_tester@test.com')
            ->setFirstName('John')
            ->setLastName('Doe');

        $this->expectException(LengthException::class);
        $this->expectExceptionMessage('Too short password');

        // Set plain password so that listener can make a real one
        $entity->setPlainPassword('test');

        // Create event for prePersist method
        $event = new LifecycleEventArgs($entity, $entityManager);

        // Call listener method
        (new UserEntityEventListener($hasher))->prePersist($event);
    }

    /**
     * @testdox Test that too short password throws an exception with `preUpdate` event
     */
    public function testThatTooShortPasswordThrowsAnExceptionWithPreUpdate(): void
    {
        /** @var EntityManager $entityManager */
        $entityManager = static::getContainer()->get('doctrine.orm.default_entity_manager');

        /** @var UserPasswordHasherInterface $hasher */
        $hasher = static::getContainer()->get('security.user_password_hasher');

        $entity = (new User())
            ->setUsername('john_doe_the_tester')
            ->setEmail('john.doe_the_tester@test.com')
            ->setFirstName('John')
            ->setLastName('Doe');

        $this->expectException(LengthException::class);
        $this->expectExceptionMessage('Too short password');

        // Set plain password so that listener can make a real one
        $entity->setPlainPassword('test');

        $changeSet = [];

        $event = new PreUpdateEventArgs($entity, $entityManager, $changeSet);

        (new UserEntityEventListener($hasher))->preUpdate($event);
    }

    public function testListenerPrePersistMethodWorksAsExpected(): void
    {
        /** @var EntityManager $entityManager */
        $entityManager = static::getContainer()->get('doctrine.orm.default_entity_manager');

        /** @var UserPasswordHasherInterface $hasher */
        $hasher = static::getContainer()->get('security.user_password_hasher');

        $entity = (new User())
            ->setUsername('john_doe_the_tester')
            ->setEmail('john.doe_the_tester@test.com')
            ->setFirstName('John')
            ->setLastName('Doe');

        // Get store old password
        $oldPassword = $entity->getPassword();

        // Set plain password so that listener can make a real one
        $entity->setPlainPassword('test_test');

        // Create event for prePersist method
        $event = new LifecycleEventArgs($entity, $entityManager);

        // Call listener method
        (new UserEntityEventListener($hasher))->prePersist($event);

        static::assertEmpty(
            $entity->getPlainPassword(),
            'Listener did not reset plain password value.'
        );

        static::assertNotSame(
            $oldPassword,
            $entity->getPassword(),
            'Password was not changed by the listener.',
        );

        static::assertTrue(
            $hasher->isPasswordValid(new SecurityUser($entity), 'test_test'),
            'Changed password is not valid.'
        );
    }

    public function testListenerPreUpdateMethodWorksAsExpected(): void
    {
        /** @var EntityManager $entityManager */
        $entityManager = static::getContainer()->get('doctrine.orm.default_entity_manager');

        /** @var UserPasswordHasherInterface $hasher */
        $hasher = static::getContainer()->get('security.user_password_hasher');

        $entity = (new User())
            ->setUsername('john_doe_the_tester')
            ->setEmail('john.doe_the_tester@test.com')
            ->setFirstName('John')
            ->setLastName('Doe');

        // Create encrypted password manually for user
        $entity->setPassword(
            fn (string $password): string =>
                $hasher->hashPassword(new SecurityUser($entity), $password),
            'test_test'
        );

        // Set plain password so that listener can make a real one
        $entity->setPlainPassword('test_test_test');

        // Get store old password
        $oldPassword = $entity->getPassword();

        $changeSet = [];

        $event = new PreUpdateEventArgs($entity, $entityManager, $changeSet);

        (new UserEntityEventListener($hasher))->preUpdate($event);

        static::assertEmpty(
            $entity->getPlainPassword(),
            'Listener did not reset plain password value.'
        );

        static::assertNotSame(
            $oldPassword,
            $entity->getPassword(),
            'Password was not changed by the listener.',
        );

        static::assertTrue(
            $hasher->isPasswordValid(new SecurityUser($entity), 'test_test_test'),
            'Changed password is not valid.'
        );
    }
}
