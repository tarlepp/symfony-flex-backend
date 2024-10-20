<?php
declare(strict_types = 1);
/**
 * /src/ValueResolver/LoggedInUserValueResolver.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\ValueResolver;

use App\Entity\User;
use App\Security\UserTypeIdentification;
use Generator;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\MissingTokenException;
use Override;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Throwable;

/**
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
 * @package App\ValueResolver
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class LoggedInUserValueResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly UserTypeIdentification $userService,
    ) {
    }

    public function supports(ArgumentMetadata $argument): bool
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
    #[Override]
    public function resolve(Request $request, ArgumentMetadata $argument): Generator
    {
        if (!$this->supports($argument)) {
            return [];
        }

        yield $this->userService->getUser();
    }
}
