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
     * @param UserResource $userResource
     *
     * @return BlameableDecorator
     */
    public function setUserResource(UserResource $userResource): self
    {
        $this->userResource = $userResource;

        return $this;
    }

    /**
     * @param mixed $user
     *
     * @throws Throwable
     */
    public function setUserValue($user): void
    {
        if ($user instanceof SecurityUser) {
            $user = $this->userResource->findOne($user->getUuid());
        }

        parent::setUserValue($user);
    }
}
