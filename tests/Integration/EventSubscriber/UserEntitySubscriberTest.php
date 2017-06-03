<?php
declare(strict_types=1);
/**
 * /tests/Integration/EventSubscriber/UserEntitySubscriberTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\EventSubscriber;

use App\Entity\User;
use App\EventSubscriber\UserEntitySubscriber;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

/**
 * Class UserEntitySubscriberTest
 *
 * @package App\Tests\Integration\EventSubscriber
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserEntitySubscriberTest extends KernelTestCase
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var User
     */
    protected $entity;

    /**
     * @var PasswordEncoderInterface
     */
    protected $encoder;

    /**
     * @var UserEntitySubscriber
     */
    protected $subscriber;

    public function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        // Store container and entity manager
        $this->container = static::$kernel->getContainer();
        $this->entityManager = $this->container->get('doctrine.orm.default_entity_manager');

        // Create listener
        $this->subscriber = new UserEntitySubscriber($this->container->get('security.encoder_factory'));

        // Create new user but not store it at this time
        $this->entity = new User();
        $this->entity->setUsername('john_doe_the_tester');
        $this->entity->setEmail('john.doe_the_tester@test.com');
        $this->entity->setFirstname('John');
        $this->entity->setSurname('Doe');

        // Get used encoder
        $this->encoder = $this->container->get('security.encoder_factory')->getEncoder($this->entity);
    }

    public function tearDown(): void
    {
        if ($this->entityManager->contains($this->entity)) {
            $this->entityManager->remove($this->entity);
            $this->entityManager->flush();
        }

        $this->entityManager->close();
        $this->entityManager = null; // avoid memory leaks

        static::$kernel->shutdown();

        parent::tearDown();
    }

    /**
     * @expectedException \LengthException
     * @expectedExceptionMessage Too short password
     */
    public function testThatTooShortPasswordThrowsAnExceptionWithPrePersist(): void
    {
        // Set plain password so that listener can make a real one
        $this->entity->setPlainPassword('test');

        // Create event for prePersist method
        $event = new LifecycleEventArgs($this->entity, $this->entityManager);

        // Call listener method
        $this->subscriber->prePersist($event);
    }

    /**
     * @expectedException \LengthException
     * @expectedExceptionMessage Too short password
     */
    public function testThatTooShortPasswordThrowsAnExceptionWithPreUpdate(): void
    {
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
            $this->encoder->isPasswordValid($this->entity->getPassword(), 'test_test', ''),
            'Changed password is not valid.'
        );
    }

    public function testListenerPreUpdateMethodWorksAsExpected(): void
    {
        $encoder = $this->encoder;

        $callable = function ($password) use ($encoder) {
            return $encoder->encodePassword($password, '');
        };

        // Create encrypted password manually for user
        $this->entity->setPassword($callable, 'test_test');

        // Set plain password so that listener can make a real one
        $this->entity->setPlainPassword('test_test');

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
            $this->encoder->isPasswordValid($this->entity->getPassword(), 'test_test', ''),
            'Changed password is not valid.'
        );
    }
}
