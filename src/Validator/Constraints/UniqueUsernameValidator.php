<?php
declare(strict_types = 1);

/**
 * /src/Validator/Constraints/UniqueUsernameValidator.php
 */

namespace App\Validator\Constraints;

use App\Entity\Interfaces\UserInterface;
use App\Repository\UserRepository;
use Doctrine\ORM\NonUniqueResultException;
use Override;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueUsernameValidator extends ConstraintValidator
{
    public function __construct(
        private readonly UserRepository $repository,
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * @throws NonUniqueResultException
     */
    #[Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if ($value instanceof UserInterface
            && !$this->repository->isUsernameAvailable($value->getUsername(), $value->getId())
        ) {
            $this->context
                ->buildViolation(UniqueUsername::MESSAGE)
                ->setCode(UniqueUsername::IS_UNIQUE_USERNAME_ERROR)
                ->addViolation();
        }
    }
}
