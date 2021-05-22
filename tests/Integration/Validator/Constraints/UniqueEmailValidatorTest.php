<?php
declare(strict_types = 1);
/**
 * /src/Validator/Constraints/UniqueEmailValidatorTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Validator\Constraints;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Validator\Constraints\UniqueEmail;
use App\Validator\Constraints\UniqueEmailValidator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;
use Throwable;

/**
 * Class UniqueEmailValidatorTest
 *
 * @package App\Validator\Constraints
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UniqueEmailValidatorTest extends KernelTestCase
{
    /**
     * @throws Throwable
     *
     * @testdox Test that `UniqueEmailValidator::validate` method calls expected service methods
     */
    public function testThatValidateCallsExpectedMethods(): void
    {
        // Create new user
        $user = (new User())
            ->setEmail('john.doe@test.com');

        [$constraintViolationBuilderMock, $executionContextMock, $userRepositoryMock] = $this->getMocks();

        $userRepositoryMock
            ->expects(static::once())
            ->method('isEmailAvailable')
            ->with($user->getEmail(), $user->getId())
            ->willReturn(false);

        $executionContextMock
            ->expects(static::once())
            ->method('buildViolation')
            ->with(UniqueEmail::MESSAGE)
            ->willReturn($constraintViolationBuilderMock);

        $constraintViolationBuilderMock
            ->expects(static::once())
            ->method('setCode')
            ->with(UniqueEmail::IS_UNIQUE_EMAIL_ERROR)
            ->willReturn($constraintViolationBuilderMock);

        $constraintViolationBuilderMock
            ->expects(static::once())
            ->method('addViolation');

        // Run validator
        $validator = new UniqueEmailValidator($userRepositoryMock);
        $validator->initialize($executionContextMock);
        $validator->validate($user, new UniqueEmail());
    }

    /**
     * @return array{
     *      0: \PHPUnit\Framework\MockObject\MockObject&ConstraintViolationBuilderInterface,
     *      1: \PHPUnit\Framework\MockObject\MockObject&ExecutionContext,
     *      2: \PHPUnit\Framework\MockObject\MockObject&UserRepository,
     *  }
     */
    private function getMocks(): array
    {
        return [
            $this->getMockBuilder(ConstraintViolationBuilderInterface::class)->getMock(),
            $this->getMockBuilder(ExecutionContext::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock(),
        ];
    }
}
