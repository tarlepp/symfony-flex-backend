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
        $repositoryMock = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $contextMock = $this->getMockBuilder(ExecutionContext::class)->disableOriginalConstructor()->getMock();
        $builderMock = $this->getMockBuilder(ConstraintViolationBuilderInterface::class)->getMock();

        // Create new user
        $user = (new User())
            ->setUsername('john');

        $repositoryMock
            ->expects(self::once())
            ->method('isUsernameAvailable')
            ->with($user->getUsername(), $user->getId())
            ->willReturn(false);

        $contextMock
            ->expects(self::once())
            ->method('buildViolation')
            ->with(UniqueUsername::MESSAGE)
            ->willReturn($builderMock);

        $builderMock
            ->expects(self::once())
            ->method('setCode')
            ->with(UniqueUsername::IS_UNIQUE_USERNAME_ERROR)
            ->willReturn($builderMock);

        $builderMock
            ->expects(self::once())
            ->method('addViolation');

        // Run validator
        $validator = new UniqueUsernameValidator($repositoryMock);
        $validator->initialize($contextMock);
        $validator->validate($user, new UniqueUsername());
    }
}
