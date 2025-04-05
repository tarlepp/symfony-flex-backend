<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Security/Voter/IsUserHimselfVoterTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Security\Voter;

use App\Entity\User;
use App\Security\SecurityUser;
use App\Security\Voter\IsUserHimselfVoter;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @package App\Tests\Integration\Security\Voter
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class IsUserHimselfVoterTest extends KernelTestCase
{
    #[TestDox('Test that `vote` method returns `Voter::ACCESS_ABSTAIN` when subject is not supported')]
    public function testThatVoteReturnsExpectedIfSubjectIsNotSupported(): void
    {
        $token = new UsernamePasswordToken(new SecurityUser(new User()), 'firewall');

        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->getVoter()->vote($token, 'subject', ['IS_USER_HIMSELF']),
        );
    }

    #[TestDox('Test that `vote` method returns `Voter::ACCESS_DENIED` when subject mismatch with given token user')]
    public function testThatVoteReturnsExpectedWhenUserMismatch(): void
    {
        $token = new UsernamePasswordToken(new SecurityUser(new User()), 'firewall');

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->getVoter()->vote($token, new User(), ['IS_USER_HIMSELF']),
        );
    }

    #[TestDox('Test that `vote` method returns `Voter::ACCESS_GRANTED` when subject match with given token user')]
    public function testThatVoteReturnsExpectedWhenUserMatch(): void
    {
        $user = new User();
        $token = new UsernamePasswordToken(new SecurityUser($user), 'firewall');

        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->getVoter()->vote($token, $user, ['IS_USER_HIMSELF']));
    }

    private function getVoter(): IsUserHimselfVoter
    {
        return new IsUserHimselfVoter();
    }
}
