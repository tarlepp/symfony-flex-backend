<?php
declare(strict_types = 1);
/**
 * /tests/Integration/EventSubscriber/LockedUserSubscriberTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\EventSubscriber;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class LockedUserSubscriberTest
 *
 * @package App\Tests\Integration\EventSubscriber
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LockedUserSubscriberTest extends KernelTestCase
{
    public function testThatEventLexikJwtAuthenticationOnAuthenticationFailureCallsExpectedServiceMethod(): void
    {
        static::markTestIncomplete('TODO implemented this test');
    }

    public function testThatEventLexikJwtAuthenticationOnJwtAuthenticatedCallsExpectedServiceMethod(): void
    {
        static::markTestIncomplete('TODO implemented this test');
    }

    public function testThatSecurityAuthenticationSuccessCallsExpectedServiceMethod(): void
    {
        static::markTestIncomplete('TODO implemented this test');
    }
}
