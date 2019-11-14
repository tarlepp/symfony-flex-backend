<?php
declare(strict_types = 1);
/**
 * /src/ArgumentResolver/LoggedInUserValueResolver.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\ArgumentResolver;

use App\Entity\User;
use App\Resource\UserResource;
use App\Security\SecurityUser;
use Generator;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\MissingTokenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Throwable;

/**
 * Class LoggedInUserValueResolver
 *
 * Example how to use this within your controller;
 *  /**
 *   * @Symfony\Component\Routing\Annotation\Route(path="some_path_to_your_route")
 *   * @Sensio\Bundle\FrameworkExtraBundle\Configuration\Security("is_granted('IS_AUTHENTICATED_FULLY')")
 *   *\/
 *  public function someMethod(\App\Entity\User $loggedInUser): Response
 *  {
 *      ...
 *  }
 *
 * This will automatically convert your security user to actual User entity that
 * you can use within your controller as you like.
 *
 * @package App\ArgumentResolver
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LoggedInUserValueResolver implements ArgumentValueResolverInterface
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
     * LoggedInUserValueResolver constructor.
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
            && $argument->getName() === 'loggedInUser'
            && $argument->getType() === User::class
        ) {
            if ($argument->isNullable() === false && !($token->getUser() instanceof SecurityUser)) {
                throw new MissingTokenException('JWT Token not found');
            }

            $output = $token->getUser() instanceof SecurityUser;
        }

        return $output;
    }

    /**
     * Returns the possible value(s).
     *
     * @param Request          $request
     * @param ArgumentMetadata $argumentMetadata
     *
     * @return Generator
     *
     * @throws Throwable
     */
    public function resolve(Request $request, ArgumentMetadata $argumentMetadata): Generator
    {
        $token = $this->tokenStorage->getToken();

        if ($token === null) {
            throw new MissingTokenException('JWT Token not found');
        }

        /** @var SecurityUser $securityUser */
        $securityUser = $token->getUser();

        yield $this->userResource->findOne($securityUser->getUsername());
    }
}
