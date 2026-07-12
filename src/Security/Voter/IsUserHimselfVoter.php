<?php
declare(strict_types = 1);

/**
 * /src/Security/Voter/IsUserHimselfVoter.php
 */

namespace App\Security\Voter;

use App\Entity\User;
use App\Security\SecurityUser;
use Override;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @template TAttribute of string
 * @template TSubject of mixed
 *
 * @extends Voter<TAttribute, TSubject>
 */
class IsUserHimselfVoter extends Voter
{
    private const string ATTRIBUTE = 'IS_USER_HIMSELF';

    /**
     * {@inheritDoc}
     */
    #[Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::ATTRIBUTE && $subject instanceof User;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null,
    ): bool {
        $user = $token->getUser();

        return $user instanceof SecurityUser && $subject instanceof User && $user->getUuid() === $subject->getId();
    }
}
