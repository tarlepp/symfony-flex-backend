<?php
declare(strict_types = 1);
/**
 * /src/ArgumentResolver/LoggedInUserValueResolver.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\ArgumentResolver;

use App\Entity\User;
use App\Security\UserTypeIdentification;
use Generator;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\MissingTokenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Throwable;

/**
 * Class LoggedInUserValueResolver
 *
 * Example how to use this within your controller;
 *
 *  #[Route(path: 'some-path')]
 *  #[IsGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY)]
 *  public function someMethod(\App\Entity\User $loggedInUser): Response
 *  {
 *      ...
 *  }
 *
 * This will automatically convert your security user to actual User entity that
 * you can use within your controller as you like.
 *
 * @package App\ArgumentResolver
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class LoggedInUserValueResolver implements ArgumentValueResolverInterface
{
    public function __construct(
        private UserTypeIdentification $userService,
    ) {
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        $output = false;

        // only security user implementations are supported
        if ($argument->getName() === 'loggedInUser' && $argument->getType() === User::class) {
            $securityUser = $this->userService->getSecurityUser();

            if ($securityUser === null && $argument->isNullable() === false) {
                throw new MissingTokenException('JWT Token not found');
            }

            $output = true;
        }

        return $output;
    }

    /**
     * @throws Throwable
     *
     * @return Generator<User|null>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): Generator
    {
        yield $this->userService->getUser();
    }
}
