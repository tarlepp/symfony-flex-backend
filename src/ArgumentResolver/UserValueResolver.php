<?php
declare(strict_types = 1);
/**
 * /src/ArgumentResolver/UserValueResolver.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\ArgumentResolver;

use App\Entity\User;
use App\Resource\UserResource;
use App\Security\SecurityUser;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Generator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Class UserValueResolver
 *
 * @package App\ArgumentResolver
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserValueResolver implements ArgumentValueResolverInterface
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var UserResource
     */
    private $userResource;

    /**
     * UserValueResolver constructor.
     *
     * @param TokenStorageInterface $tokenStorage
     * @param UserResource          $userResource
     */
    public function __construct(TokenStorageInterface $tokenStorage, UserResource $userResource)
    {
        $this->tokenStorage = $tokenStorage;
        $this->userResource = $userResource;
    }

    /**
     * Whether this resolver can resolve the value for the given ArgumentMetadata.
     *
     * @param Request          $request
     * @param ArgumentMetadata $argument
     *
     * @return bool
     */
    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        $output = false;
        $token = $this->tokenStorage->getToken();

        // only security user implementations are supported
        if ($token instanceof TokenInterface
            && $argument->getType() === User::class
            && $token->getUser() instanceof SecurityUser
        ) {
            $output = true;
        }

        return $output;
    }

    /**
     * Returns the possible value(s).
     *
     * @param Request          $request
     * @param ArgumentMetadata $argument
     *
     * @return Generator
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): Generator
    {
        /** @noinspection NullPointerExceptionInspection */
        /**
         * @var SecurityUser
         * @psalm-suppress PossiblyNullReference
         */
        $securityUser = $this->tokenStorage->getToken()->getUser();

        yield $this->userResource->findOne($securityUser->getUsername());
    }
}
