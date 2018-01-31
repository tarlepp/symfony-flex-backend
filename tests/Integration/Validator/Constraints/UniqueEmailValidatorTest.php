<?php
declare(strict_types=1);
/**
 * /src/Validator/Constraints/UniqueEmailValidatorTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Validator\Constraints;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Validator\Constraints\UniqueEmail;
use App\Validator\Constraints\UniqueEmailValidator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * Class UniqueEmailValidatorTest
 *
 * @package App\Validator\Constraints
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UniqueEmailValidatorTest extends KernelTestCase
{
    /**
     * @var UniqueEmail
     */
    private $constraint;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ExecutionContext
     */
    private $context;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ConstraintViolationBuilderInterface
     */
    private $builder;

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function testThatValidateCallsExpectedMethods(): void
    {
        // Create new user
        $user = new User();
        $user->setEmail('john.doe@test.com');

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|UserRepository $repository
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
            ->with($this->constraint->message)
            ->willReturn($this->builder);

        $this->builder
            ->expects(static::once())
            ->method('addViolation');

        // Run validator
        $validator = new UniqueEmailValidator($repository);
        $validator->initialize($this->context);
        $validator->validate($user, $this->constraint);

        unset($validator, $repository, $user);
    }

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->constraint = new UniqueEmail();
        $this->context = $this->getMockBuilder(ExecutionContext::class)->disableOriginalConstructor()->getMock();
        $this->builder = $this->getMockBuilder(ConstraintViolationBuilderInterface::class)->getMock();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->constraint, $this->context, $this->builder);
    }
}
