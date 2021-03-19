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
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Throwable;
use UnexpectedValueException;
use function assert;

/**
 * Class UserEntityEventListenerTest
 *
 * @package App\Tests\Integration\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UserEntityEventListenerTest extends KernelTestCase
{
    private ?EntityManager $entityManager = null;
    private ?User $entity = null;
    private ?UserPasswordEncoderInterface $encoder = null;
    private ?UserEntityEventListener $listener = null;

    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel();

        // Store container and entity manager
        $testContainer = static::$kernel->getContainer();
        $entityManager = $testContainer->get('doctrine.orm.default_entity_manager');
        $encoder = $testContainer->get('security.password_encoder');

        assert($entityManager instanceof EntityManager);
        assert($encoder instanceof UserPasswordEncoderInterface);

        $this->entityManager = $entityManager;
        $this->encoder = $encoder;

        // Create listener
        $this->listener = new UserEntityEventListener($this->encoder);

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
        $entityManager = $this->getEntityManager();
        $entity = $this->getEntity();

        if ($entityManager->contains($entity)) {
            $entityManager->remove($entity);
            $entityManager->flush();
        }

        $entityManager->close();

        static::$kernel->shutdown();

        parent::tearDown();
    }

    public function testThatTooShortPasswordThrowsAnExceptionWithPrePersist(): void
    {
        $this->expectException(LengthException::class);
        $this->expectExceptionMessage('Too short password');

        // Set plain password so that listener can make a real one
        $this->getEntity()->setPlainPassword('test');

        // Create event for prePersist method
        $event = new LifecycleEventArgs($this->getEntity(), $this->getEntityManager());

        // Call listener method
        $this->getListener()->prePersist($event);
    }

    public function testThatTooShortPasswordThrowsAnExceptionWithPreUpdate(): void
    {
        $this->expectException(LengthException::class);
        $this->expectExceptionMessage('Too short password');

        // Set plain password so that listener can make a real one
        $this->getEntity()->setPlainPassword('test');

        $changeSet = [];

        $event = new PreUpdateEventArgs($this->getEntity(), $this->getEntityManager(), $changeSet);

        $this->getListener()->preUpdate($event);
    }

    public function testListenerPrePersistMethodWorksAsExpected(): void
    {
        // Get store old password
        $oldPassword = $this->getEntity()->getPassword();

        // Set plain password so that listener can make a real one
        $this->getEntity()->setPlainPassword('test_test');

        // Create event for prePersist method
        $event = new LifecycleEventArgs($this->getEntity(), $this->getEntityManager());

        // Call listener method
        $this->getListener()->prePersist($event);

        static::assertEmpty(
            $this->getEntity()->getPlainPassword(),
            'Listener did not reset plain password value.'
        );

        static::assertNotSame(
            $oldPassword,
            $this->getEntity()->getPassword(),
            'Password was not changed by the listener.',
        );

        static::assertTrue(
            $this->getEncoder()->isPasswordValid(new SecurityUser($this->getEntity()), 'test_test'),
            'Changed password is not valid.'
        );
    }

    public function testListenerPreUpdateMethodWorksAsExpected(): void
    {
        // Create encrypted password manually for user
        $this->getEntity()->setPassword(
            fn (string $password): string =>
                $this->getEncoder()->encodePassword(new SecurityUser($this->getEntity()), $password),
            'test_test'
        );

        // Set plain password so that listener can make a real one
        $this->getEntity()->setPlainPassword('test_test_test');

        // Get store old password
        $oldPassword = $this->getEntity()->getPassword();

        $changeSet = [];

        $event = new PreUpdateEventArgs($this->getEntity(), $this->getEntityManager(), $changeSet);

        $this->getListener()->preUpdate($event);

        static::assertEmpty(
            $this->getEntity()->getPlainPassword(),
            'Listener did not reset plain password value.'
        );

        static::assertNotSame(
            $oldPassword,
            $this->getEntity()->getPassword(),
            'Password was not changed by the listener.',
        );

        static::assertTrue(
            $this->getEncoder()->isPasswordValid(new SecurityUser($this->getEntity()), 'test_test_test'),
            'Changed password is not valid.'
        );
    }

    private function getEntityManager(): EntityManager
    {
        return $this->entityManager ?? throw new UnexpectedValueException('EntityManager not set');
    }

    private function getEncoder(): UserPasswordEncoderInterface
    {
        return $this->encoder ?? throw new UnexpectedValueException('Encoder not set');
    }

    private function getListener(): UserEntityEventListener
    {
        return $this->listener ?? throw new UnexpectedValueException('Listener not set');
    }

    private function getEntity(): User
    {
        return $this->entity ?? throw new UnexpectedValueException('Entity not set');
    }
}
