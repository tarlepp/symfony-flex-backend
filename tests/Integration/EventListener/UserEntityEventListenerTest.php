<?php
declare(strict_types=1);
/**
 * /tests/Integration/EventListener/UserEntityEventListenerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\EventListener;

use App\Entity\User;
use App\EventListener\UserEntityEventListener;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Throwable;

/**
 * Class UserEntityEventListenerTest
 *
 * @package App\Tests\Integration\EventSubscriber
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserEntityEventListenerTest extends KernelTestCase
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var ContainerInterface
     */
    private $testContainer;

    /**
     * @var User
     */
    protected $entity;

    /**
     * @var UserPasswordEncoderInterface
     */
    protected $encoder;

    /**
     * @var UserEntityEventListener
     */
    protected $subscriber;

    /** @noinspection PhpFullyQualifiedNameUsageInspection */
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

    /** @noinspection PhpFullyQualifiedNameUsageInspection */
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
            $this->encoder->isPasswordValid($this->entity, 'test_test'),
            'Changed password is not valid.'
        );
    }

    public function testListenerPreUpdateMethodWorksAsExpected(): void
    {
        $encoder = $this->encoder;

        $callable = function ($password) use ($encoder) {
            return $encoder->encodePassword($this->entity, $password);
        };

        // Create encrypted password manually for user
        $this->entity->setPassword($callable, 'test_test');

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
            $this->encoder->isPasswordValid($this->entity, 'test_test_test'),
            'Changed password is not valid.'
        );
    }

    protected function setUp(): void
    {
        gc_enable();

        parent::setUp();

        static::bootKernel();

        // Store container and entity manager
        $this->testContainer = static::$kernel->getContainer();

        /** @noinspection MissingService */
        $this->entityManager = $this->testContainer->get('doctrine.orm.default_entity_manager');

        $this->encoder = $this->testContainer->get('security.password_encoder');

        // Create listener
        $this->subscriber = new UserEntityEventListener($this->encoder);

        // Create new user but not store it at this time
        $this->entity = new User();
        $this->entity->setUsername('john_doe_the_tester');
        $this->entity->setEmail('john.doe_the_tester@test.com');
        $this->entity->setFirstname('John');
        $this->entity->setSurname('Doe');
    }


    /**
     * @throws Throwable
     */
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

        unset($this->entity, $this->subscriber, $this->encoder, $this->entityManager, $this->testContainer);

        gc_collect_cycles();
    }
}
