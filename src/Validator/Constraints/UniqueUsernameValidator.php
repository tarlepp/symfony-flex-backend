<?php
declare(strict_types = 1);
/**
 * /src/Validator/Constraints/UniqueUsernameValidator.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Validator\Constraints;

use App\Entity\Interfaces\UserInterface;
use App\Repository\UserRepository;
use Doctrine\ORM\NonUniqueResultException;
use Override;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @package App\Validator\Constraints
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UniqueUsernameValidator extends ConstraintValidator
{
    public function __construct(
        private readonly UserRepository $repository,
    ) {
    }

    /**
     * {@inheritdoc}
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
