<?php
declare(strict_types = 1);
/**
 * /src/Validator/Constraints/UniqueUsernameValidator.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Validator\Constraints;

use App\Entity\UserInterface;
use App\Repository\UserRepository;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Class UniqueUsernameValidator
 *
 * @package App\Validator\Constraints
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UniqueUsernameValidator extends ConstraintValidator
{
    /**
     * @var UserRepository
     */
    private $repository;

    /**
     * UniqueUsernameValidator constructor.
     *
     * @param UserRepository $repository
     */
    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Checks if the passed value is valid.
     *
     * In this case check if 'username' is available or not within User repository.
     *
     * @throws NonUniqueResultException
     *
     * @param UserInterface|mixed       $value      The value that should be validated
     * @param Constraint|UniqueUsername $constraint The constraint for the validation
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$this->repository->isUsernameAvailable($value->getUsername(), $value->getId())) {
            $this->context
                ->buildViolation(UniqueUsername::MESSAGE)
                ->setCode(UniqueUsername::IS_UNIQUE_USERNAME_ERROR)
                ->addViolation();
        }
    }
}
