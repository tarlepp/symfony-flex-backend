<?php
declare(strict_types = 1);
/**
 * /src/Validator/Constraints/UniqueUsernameValidator.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Validator\Constraints;

use App\Entity\Interfaces\UserInterface;
use App\Repository\UserRepository;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Class UniqueUsernameValidator
 *
 * @package App\Validator\Constraints
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UniqueUsernameValidator extends ConstraintValidator
{
    private UserRepository $repository;

    /**
     * UniqueUsernameValidator constructor.
     */
    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * {@inheritdoc}
     *
     * @throws NonUniqueResultException
     */
    public function validate($value, Constraint $constraint): void
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
