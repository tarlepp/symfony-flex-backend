<?php
declare(strict_types = 1);
/**
 * /src/Validator/Constraints/UniqueEmailValidatorTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Validator\Constraints;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Validator\Constraints\UniqueEmail;
use App\Validator\Constraints\UniqueEmailValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;
use Throwable;

/**
 * Class UniqueEmailValidatorTest
 *
 * @package App\Validator\Constraints
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UniqueEmailValidatorTest extends KernelTestCase
{
    private UniqueEmail $constraint;

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

        $this->constraint = new UniqueEmail();
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
            ->setEmail('john.doe@test.com');

        /**
         * @var MockObject|UserRepository $repository
         */
        $repository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();

        $repository
            ->expects(static::once())
            ->method('isEmailAvailable')
            ->with($user->getEmail(), $user->getId())
            ->willReturn(false);

        $this->context
            ->expects(static::once())
            ->method('buildViolation')
            ->with(UniqueEmail::MESSAGE)
            ->willReturn($this->builder);

        $this->builder
            ->expects(static::once())
            ->method('setCode')
            ->with(UniqueEmail::IS_UNIQUE_EMAIL_ERROR)
            ->willReturn($this->builder);

        $this->builder
            ->expects(static::once())
            ->method('addViolation');

        // Run validator
        $validator = new UniqueEmailValidator($repository);
        $validator->initialize($this->context);
        $validator->validate($user, $this->constraint);
    }
}
