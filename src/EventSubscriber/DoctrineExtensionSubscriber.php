<?php
declare(strict_types = 1);
/**
 * /src/EventSubscriber/DoctrineExtensionSubscriber.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\EventSubscriber;

use App\Security\UserTypeIdentification;
use Doctrine\ORM\NonUniqueResultException;
use Gedmo\Blameable\BlameableListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class DoctrineExtensionSubscriber
 *
 * @package App\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class DoctrineExtensionSubscriber implements EventSubscriberInterface
{
    private BlameableListener $blameableListener;
    private UserTypeIdentification $userTypeIdentification;

    public function __construct(BlameableListener $blameableListener, UserTypeIdentification $userTypeIdentification)
    {
        $this->blameableListener = $blameableListener;
        $this->userTypeIdentification = $userTypeIdentification;
    }

    /**
     * {@inheritdoc}
     *
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'onKernelRequest',
        ];
    }

    /**
     * @throws NonUniqueResultException
     */
    public function onKernelRequest(): void
    {
        $user = $this->userTypeIdentification->getUser();

        if ($user !== null) {
            $this->blameableListener->setUserValue($user);
        }
    }
}
