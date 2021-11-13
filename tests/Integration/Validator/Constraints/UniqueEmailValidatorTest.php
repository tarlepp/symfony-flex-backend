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
        $repositoryMock = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
        $contextMock = $this->getMockBuilder(ExecutionContext::class)->disableOriginalConstructor()->getMock();
        $builderMock = $this->getMockBuilder(ConstraintViolationBuilderInterface::class)->getMock();

        // Create new user
        $user = (new User())
            ->setEmail('john.doe@test.com');

        $repositoryMock
            ->expects(self::once())
            ->method('isEmailAvailable')
            ->with($user->getEmail(), $user->getId())
            ->willReturn(false);

        $contextMock
            ->expects(self::once())
            ->method('buildViolation')
            ->with(UniqueEmail::MESSAGE)
            ->willReturn($builderMock);

        $builderMock
            ->expects(self::once())
            ->method('setCode')
            ->with(UniqueEmail::IS_UNIQUE_EMAIL_ERROR)
            ->willReturn($builderMock);

        $builderMock
            ->expects(self::once())
            ->method('addViolation');

        // Run validator
        $validator = new UniqueEmailValidator($repositoryMock);
        $validator->initialize($contextMock);
        $validator->validate($user, new UniqueEmail());
    }
}
