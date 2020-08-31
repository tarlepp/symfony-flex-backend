<?php
declare(strict_types = 1);
/**
 * /src/ArgumentResolver/LoggedInUserValueResolver.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\ArgumentResolver;

use App\Entity\User;
use App\Security\UserTypeIdentification;
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
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LoggedInUserValueResolver implements ArgumentValueResolverInterface
{
    private TokenStorageInterface $tokenStorage;
    private UserTypeIdentification $userService;

    /**
     * LoggedInUserValueResolver constructor.
     */
    public function __construct(TokenStorageInterface $tokenStorage, UserTypeIdentification $userService)
    {
        $this->tokenStorage = $tokenStorage;
        $this->userService = $userService;
    }

    /**
     * {@inheritdoc}
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
            $securityUser = $this->userService->getSecurityUser();

            if ($securityUser === null && $argument->isNullable() === false) {
                throw new MissingTokenException('JWT Token not found');
            }

            $output = true;
        }

        return $output;
    }

    /**
     * {@inheritdoc}
     *
     * @throws Throwable
     */
    public function resolve(Request $request, ArgumentMetadata $argument): Generator
    {
        $token = $this->tokenStorage->getToken();

        if ($token === null) {
            throw new MissingTokenException('JWT Token not found');
        }

        yield $this->userService->getUser();
    }
}
