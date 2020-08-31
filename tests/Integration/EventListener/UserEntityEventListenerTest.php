<?php
declare(strict_types = 1);
/**
 * /tests/Integration/EventListener/UserEntityEventListenerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
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
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Throwable;

/**
 * Class UserEntityEventListenerTest
 *
 * @package App\Tests\Integration\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserEntityEventListenerTest extends KernelTestCase
{
    private EntityManager $entityManager;
    private User $entity;
    private UserPasswordEncoderInterface $encoder;
    private UserEntityEventListener $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel();

        // Store container and entity manager
        $testContainer = static::$kernel->getContainer();

        /* @noinspection MissingService */
        /* @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->entityManager = $testContainer->get('doctrine.orm.default_entity_manager');

        /* @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->encoder = $testContainer->get('security.password_encoder');

        // Create listener
        $this->subscriber = new UserEntityEventListener($this->encoder);

        // Create new user but not store it at this time
        $this->entity = (new User())
            ->setUsername('john_doe_the_tester')
            ->setEmail('john.doe_the_tester@test.com')
            ->setFirstName('John')
            ->setLastName('Doe');
    }

    /**
     * @throws Throwable
     */
    protected function tearDown(): void
    {
        if ($this->entityManager->contains($this->entity)) {
            $this->entityManager->remove($this->entity);
            $this->entityManager->flush();
        }

        $this->entityManager->close();

        static::$kernel->shutdown();

        parent::tearDown();
    }

    public function testThatTooShortPasswordThrowsAnExceptionWithPrePersist(): void
    {
        $this->expectException(LengthException::class);
        $this->expectExceptionMessage('Too short password');

        // Set plain password so that listener can make a real one
        $this->entity->setPlainPassword('test');

        // Create event for prePersist method
        $event = new LifecycleEventArgs($this->entity, $this->entityManager);

        // Call listener method
        $this->subscriber->prePersist($event);
    }

    public function testThatTooShortPasswordThrowsAnExceptionWithPreUpdate(): void
    {
        $this->expectException(LengthException::class);
        $this->expectExceptionMessage('Too short password');

        // Set plain password so that listener can make a real one
        $this->entity->setPlainPassword('test');

        $changeSet = [];

        $event = new PreUpdateEventArgs($this->entity, $this->entityManager, $changeSet);

        $this->subscriber->preUpdate($event);
    }

    public function testListenerPrePersistMethodWorksAsExpected(): void
    {
        // Get store old password
        $oldPassword = $this->entity->getPassword();

        // Set plain password so that listener can make a real one
        $this->entity->setPlainPassword('test_test');

        // Create event for prePersist method
        $event = new LifecycleEventArgs($this->entity, $this->entityManager);

        // Call listener method
        $this->subscriber->prePersist($event);

        static::assertEmpty(
            $this->entity->getPlainPassword(),
            'Listener did not reset plain password value.'
        );

        static::assertNotSame($oldPassword, $this->entity->getPassword(), 'Password was not changed by the listener.');

        static::assertTrue(
            $this->encoder->isPasswordValid(new SecurityUser($this->entity), 'test_test'),
            'Changed password is not valid.'
        );
    }

    public function testListenerPreUpdateMethodWorksAsExpected(): void
    {
        // Create encrypted password manually for user
        $this->entity->setPassword(
            fn ($password): string => $this->encoder->encodePassword(new SecurityUser($this->entity), $password),
            'test_test'
        );

        // Set plain password so that listener can make a real one
        $this->entity->setPlainPassword('test_test_test');

        // Get store old password
        $oldPassword = $this->entity->getPassword();

        $changeSet = [];

        $event = new PreUpdateEventArgs($this->entity, $this->entityManager, $changeSet);

        $this->subscriber->preUpdate($event);

        static::assertEmpty(
            $this->entity->getPlainPassword(),
            'Listener did not reset plain password value.'
        );

        static::assertNotSame($oldPassword, $this->entity->getPassword(), 'Password was not changed by the listener.');

        static::assertTrue(
            $this->encoder->isPasswordValid(new SecurityUser($this->entity), 'test_test_test'),
            'Changed password is not valid.'
        );
    }
}
