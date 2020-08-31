<?php
declare(strict_types = 1);
/**
 * /src/Security/Voter/IsUserHimselfVoter.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Security\Voter;

use App\Entity\User;
use App\Security\Interfaces\SecurityUserInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Class IsUserHimselfVoter
 *
 * @package App\Security
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class IsUserHimselfVoter extends Voter
{
    private const ATTRIBUTE = 'IS_USER_HIMSELF';

    /**
     * {@inheritdoc}
     */
    protected function supports(string $attribute, $subject): bool
    {
        return $attribute === self::ATTRIBUTE && $subject instanceof User;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        return $token->isAuthenticated()
            && $user instanceof SecurityUserInterface
            && $user->getUuid() === $subject->getId();
    }
}
