<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Validator/Constraints/UniqueUsernameValidatorTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Validator\Constraints;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Validator\Constraints\UniqueUsername;
use App\Validator\Constraints\UniqueUsernameValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;
use Throwable;

/**
 * Class UniqueUsernameValidatorTest
 *
 * @package App\Tests\Integration\Validator\Constraints
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UniqueUsernameValidatorTest extends KernelTestCase
{
    private UniqueUsername $constraint;

    /**
     * @var MockObject|ExecutionContext
     */
    private $context;

    /**
     * @var MockObject|ConstraintViolationBuilderInterface
     */
    private $builder;

    /**
     * @throws Throwable
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->constraint = new UniqueUsername();
        $this->context = $this->getMockBuilder(ExecutionContext::class)->disableOriginalConstructor()->getMock();
        $this->builder = $this->getMockBuilder(ConstraintViolationBuilderInterface::class)->getMock();
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @throws Throwable
     */
    public function testThatValidateCallsExpectedMethods(): void
    {
        // Create new user
        $user = (new User())
            ->setUsername('john');

        /**
         * @var MockObject|UserRepository $repository
         */
        $repository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository
            ->expects(static::once())
            ->method('isUsernameAvailable')
            ->with($user->getUsername(), $user->getId())
            ->willReturn(false);

        $this->context
            ->expects(static::once())
            ->method('buildViolation')
            ->with(UniqueUsername::MESSAGE)
            ->willReturn($this->builder);

        $this->builder
            ->expects(static::once())
            ->method('setCode')
            ->with(UniqueUsername::IS_UNIQUE_USERNAME_ERROR)
            ->willReturn($this->builder);

        $this->builder
            ->expects(static::once())
            ->method('addViolation');

        // Run validator
        $validator = new UniqueUsernameValidator($repository);
        $validator->initialize($this->context);
        $validator->validate($user, $this->constraint);
    }
}
