<?php
declare(strict_types = 1);
/**
 * /src/EventSubscriber/BlameableDecorator.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\EventSubscriber;

use App\Resource\UserResource;
use App\Security\SecurityUser;
use Gedmo\Blameable\BlameableListener;
use Throwable;

/**
 * Class BlameableDecorator
 *
 * @package App\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class BlameableDecorator extends BlameableListener
{
    private UserResource $userResource;

    /**
     * BlameableDecorator constructor.
     */
    public function __construct(UserResource $userResource)
    {
        parent::__construct();

        $this->userResource = $userResource;
    }

    /**
     * @param mixed $user
     *
     * @throws Throwable
     */
    public function setUserValue($user): void
    {
        if ($user instanceof SecurityUser) {
            $user = $this->userResource->getReference($user->getUuid());
        }

        parent::setUserValue($user);
    }
}
