<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Validator/Constraints/UniqueUsernameValidatorTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Validator\Constraints;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Validator\Constraints\UniqueUsername;
use App\Validator\Constraints\UniqueUsernameValidator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;
use Throwable;

/**
 * Class UniqueUsernameValidatorTest
 *
 * @package App\Tests\Integration\Validator\Constraints
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UniqueUsernameValidatorTest extends KernelTestCase
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
            ->setUsername('john');

        [$constraintViolationBuilderMock, $executionContextMock, $userRepositoryMock] = $this->getMocks();

        $userRepositoryMock
            ->expects(static::once())
            ->method('isUsernameAvailable')
            ->with($user->getUsername(), $user->getId())
            ->willReturn(false);

        $executionContextMock
            ->expects(static::once())
            ->method('buildViolation')
            ->with(UniqueUsername::MESSAGE)
            ->willReturn($constraintViolationBuilderMock);

        $constraintViolationBuilderMock
            ->expects(static::once())
            ->method('setCode')
            ->with(UniqueUsername::IS_UNIQUE_USERNAME_ERROR)
            ->willReturn($constraintViolationBuilderMock);

        $constraintViolationBuilderMock
            ->expects(static::once())
            ->method('addViolation');

        // Run validator
        $validator = new UniqueUsernameValidator($userRepositoryMock);
        $validator->initialize($executionContextMock);
        $validator->validate($user, new UniqueUsername());
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
