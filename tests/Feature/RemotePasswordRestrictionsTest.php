<?php

namespace Tests\Feature;

use App\Models\RemotePassword;
use App\Models\RemotePasswordRestriction;
use App\Services\RemotePasswordManager;
use App\Services\ReturnTypes\PasswordResult;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\Shared;
use Tests\TestCase;

class RemotePasswordRestrictionsTest extends TestCase
{
    use RefreshDatabase, WithFaker, Shared;

    public function test_BasicRestrictions()
    {
        $user = $this->createUser();
        $password = $this->createPassword($user->getId());

        $this->assertEquals(0, $password->getRestrictions()->count());
    }

    public function test_MaxUses()
    {
        $user = $this->createUser();
        $password = $this->createPassword($user->getId());

        $maxUses = 5;
        $restriction = $this->createEmptyRestriction($password->getId(), null);
        $this->assertFalse($restriction->setMaxUses(-1));
        $this->assertTrue($restriction->setMaxUses($maxUses));
        $this->assertTrue($restriction->save());
        $restriction = $this->getFirstPasswordRestriction($password);
        $this->assertTrue($restriction->hasMaxUsageRestrictions());
        $this->assertEquals($maxUses, $restriction->getMaxUses());

        /** @var RemotePasswordManager $passwordManager */
        $passwordManager = app()->make('passwordManager');

        // All of these should succeed.
        for ($i = 0; $i < $maxUses; $i++) {
            $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate());
            $this->assertNotFalse($passwordManager->retrievePassword($password->token1, $password->token2, ''));
        }

        // This one should fail.
        $this->assertFalse($passwordManager->retrievePassword($password->token1, $password->token2, ''));
        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_MAXUSES, $restriction->evaluate());

        $password->resetUseCount(true);
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate());
        $this->assertNotFalse($passwordManager->retrievePassword($password->token1, $password->token2, ''));
    }

    public function test_UserAgent()
    {
        $user = $this->createUser();
        $password = $this->createPassword($user->getId());

        $userAgentOne = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:98.0) Gecko/20100101 Firefox/98.0';
        $userAgentTwo = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:98.0) Gecko/20100101 Firefox/99.0';
        $restriction = $this->createEmptyRestriction($password->getId(), null);
        $this->assertTrue($restriction->addUserAgent($userAgentOne));
        $this->assertTrue($restriction->save());
        $restriction = $this->getFirstPasswordRestriction($password);
        $this->assertTrue($restriction->hasUserAgentRestrictions());
        $this->assertEquals($userAgentOne, $restriction->getUserAgentString());

        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_USERAGENT, $restriction->evaluate(userAgent: 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:98.0) Gecko/20100101 Firefox/97.0'));
        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_USERAGENT, $restriction->evaluate());
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(userAgent: $userAgentOne));

        $this->assertTrue($restriction->addUserAgent($userAgentTwo));
        $this->assertTrue($restriction->save());
        $restriction = $this->getFirstPasswordRestriction($password);
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(userAgent: $userAgentOne));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(userAgent: $userAgentTwo));

        $this->assertFalse($restriction->removeUserAgent('does-not-exist'));
        $this->assertTrue($restriction->removeUserAgent($userAgentOne));
        $this->assertTrue($restriction->save());
        $restriction = $this->getFirstPasswordRestriction($password);
        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_USERAGENT, $restriction->evaluate(userAgent: $userAgentOne));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(userAgent: $userAgentTwo));

        $this->assertTrue($restriction->clearUserAgents());
        $this->assertTrue($restriction->save());
        $restriction = $this->getFirstPasswordRestriction($password);
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(userAgent: $userAgentOne));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(userAgent: $userAgentTwo));
    }

    public function test_IpRestrictions()
    {
        $user = $this->createUser();
        $password = $this->createPassword($user->getId());

        $restriction = $this->createEmptyRestriction($password->getId(), null);

        $this->assertFalse($restriction->addIpAddressOrRange("invalid"));
        $this->assertFalse($restriction->addIpAddressOrRange("127.0.0"));
        $this->assertFalse($restriction->addIpAddressOrRange("127.0.0.1/33"));
        $this->assertFalse($restriction->addIpAddressOrRange("127.0.0.999/32"));
        $this->assertFalse($restriction->addIpAddressOrRange("127.0.0.1/A"));
        $this->assertFalse($restriction->addIpAddressOrRange("11.12.13.14//"));
        $this->assertFalse($restriction->addIpAddressOrRange("255.255.255.256"));
        $this->assertFalse($restriction->addIpAddressOrRange("11.11.11.999"));

        $this->assertTrue($restriction->addIpAddressOrRange("127.0.0.1"));
        $this->assertTrue($restriction->addIpAddressOrRange("127.0.1.1/24"));
        $this->assertTrue($restriction->addIpAddressOrRange("11.12.13.14"));
        $this->assertTrue($restriction->addIpAddressOrRange("11.12.13.14")); // Add again.
        $this->assertTrue($restriction->save());

        $restriction = $this->getFirstPasswordRestriction($password);
        $this->assertTrue($restriction->hasIpRestrictions());
        $this->assertNotEmpty($restriction->getIPString());

        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_IP, $restriction->evaluate('128.0.0.1'));
        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_IP, $restriction->evaluate('11.12.13.15'));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate('11.12.13.14'));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate('127.0.0.1'));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate('127.0.1.100'));

        $this->assertFalse($restriction->removeIpAddressOrRange('127.0.1.1/23'));
        $this->assertTrue($restriction->removeIpAddressOrRange('127.0.1.1/24'));
        $this->assertTrue($restriction->save());
        $restriction = $this->getFirstPasswordRestriction($password);

        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_IP, $restriction->evaluate('127.0.1.100'));

        $this->assertTrue($restriction->clearIpRestrictions());
        $this->assertTrue($restriction->save());
        $restriction = $this->getFirstPasswordRestriction($password);
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate('128.0.0.1'));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate('11.12.13.15'));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate('11.12.13.14'));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate('127.0.0.1'));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate('127.0.1.100'));
    }

    public function test_WeekDayRestrictions()
    {
        $testTimezone = 'Europe/Athens';
        $now = Carbon::createFromFormat('Y-m-d', '2021-03-12', $testTimezone); // Saturday.

        $user = $this->createUser();
        $password = $this->createPassword($user->getId());

        $restriction = $this->createEmptyRestriction($password->getId(), $now);
        $this->assertFalse($restriction->setWeekDays(['in', 'val', 'id']));
        $this->assertTrue($restriction->setWeekDays(['mon', 'tue', 'wed']));
        $this->assertTrue($restriction->save());
        $restriction = $this->getFirstPasswordRestriction($password);
        $this->assertTrue($restriction->hasDayRestrictions());
        $this->assertEquals(['mon', 'tue', 'wed'], $restriction->getWeekdays());

        $saturday = Carbon::createFromFormat('Y-m-d', '2022-03-12', $testTimezone); // Saturday.
        $monday = Carbon::createFromFormat('Y-m-d', '2022-03-14', $testTimezone); // Monday.
        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_DAY, $restriction->evaluate(now: $saturday));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: $monday));
    }

    public function test_TimeRestrictions()
    {
        $testTimezone = 'Europe/Athens';
        $now = Carbon::createFromFormat('H:i', '15:00', $testTimezone);

        $user = $this->createUser();
        $password = $this->createPassword($user->getId());

        // Both times set.
        $restriction = $this->createEmptyRestriction($password->getId(), $now);
        $this->assertFalse($restriction->addTimeRange('hello', null));
        $this->assertFalse($restriction->addTimeRange(null, 'hello'));
        $this->assertFalse($restriction->addTimeRange(null, null));
        $this->assertTrue($restriction->addTimeRange('13:00', '17:00'));
        $this->assertTrue($restriction->addTimeRange('13:00', '17:00')); // Adding again.
        $this->assertTrue($restriction->save());
        $restriction = $this->getFirstPasswordRestriction($password);
        $this->assertTrue($restriction->hasTimeRestrictions());

        $this->assertEquals($testTimezone, $restriction->getTimezone());
        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_TIME, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '11:00', $testTimezone)));
        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_TIME, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '12:59', $testTimezone)));
        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_TIME, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '18:00', $testTimezone)));
        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_TIME, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '17:01', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '13:00', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '17:00', $testTimezone)));

        // Set time from.
        $this->assertTrue($password->resetRestrictions());
        $restriction = $this->createEmptyRestriction($password->getId(), $now);
        $this->assertTrue($restriction->addTimeRange('14:00', null));
        $this->assertTrue($restriction->save());
        $restriction = $this->getFirstPasswordRestriction($password);

        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_TIME, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '13:59', $testTimezone)));
        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_TIME, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '00:00', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '14:00', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '23:59', $testTimezone)));

        // Set time to.
        $this->assertTrue($password->resetRestrictions());
        $restriction = $this->createEmptyRestriction($password->getId(), $now);
        $this->assertTrue($restriction->addTimeRange(null, '18:00'));
        $this->assertTrue($restriction->save());
        $restriction = $this->getFirstPasswordRestriction($password);

        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_TIME, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '18:01', $testTimezone)));
        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_TIME, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '23:59', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '18:00', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '17:59', $testTimezone)));

        // Add multiple times.
        $this->assertTrue($password->resetRestrictions());
        $restriction = $this->createEmptyRestriction($password->getId(), $now);
        $this->assertTrue($restriction->addTimeRange('13:00', '13:00'));
        $this->assertTrue($restriction->addTimeRange('14:00', '14:00'));
        $this->assertTrue($restriction->save());
        $restriction = $this->getFirstPasswordRestriction($password);
        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_TIME, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '13:01', $testTimezone)));
        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_TIME, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '14:59', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '13:00', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '14:00', $testTimezone)));

        $this->assertFalse($restriction->removeTimeRange('13:01', '13:01'));
        $this->assertTrue($restriction->removeTimeRange('13:00', '13:00'));
        $this->assertTrue($restriction->save());
        $restriction = $this->getFirstPasswordRestriction($password);
        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_TIME, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '13:01', $testTimezone)));
        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_TIME, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '14:59', $testTimezone)));
        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_TIME, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '13:00', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '14:00', $testTimezone)));

        $this->assertTrue($restriction->clearTimeRanges());
        $this->assertTrue($restriction->save());
        $restriction = $this->getFirstPasswordRestriction($password);
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '13:01', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '14:59', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '13:00', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '14:00', $testTimezone)));

        // Work with human friendly data, as used from the front-end.
        $this->assertTrue($password->resetRestrictions());
        $restriction = $this->createEmptyRestriction($password->getId(), $now);
        $this->assertFalse($restriction->addHumanFriendlyTimeRange('11-12'));
        $this->assertTrue($restriction->addHumanFriendlyTimeRange('15:00'));
        $this->assertTrue($restriction->save());
        $restriction = $this->getFirstPasswordRestriction($password);
        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_TIME, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '14:59', $testTimezone)));
        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_TIME, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '15:01', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '15:00', $testTimezone)));

        $this->assertTrue($password->resetRestrictions());
        $restriction = $this->createEmptyRestriction($password->getId(), $now);
        $this->assertTrue($restriction->addHumanFriendlyTimeRange('19:30 to 19:45'));
        $this->assertTrue($restriction->save());
        $restriction = $this->getFirstPasswordRestriction($password);
        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_TIME, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '19:29', $testTimezone)));
        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_TIME, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '19:46', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '19:30', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '19:45', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '19:37', $testTimezone)));

        $this->assertTrue($password->resetRestrictions());
        $restriction = $this->createEmptyRestriction($password->getId(), $now);
        $this->assertTrue($restriction->addHumanFriendlyTimeRange('from 18:00'));
        $this->assertTrue($restriction->save());
        $restriction = $this->getFirstPasswordRestriction($password);
        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_TIME, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '17:59', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '18:00', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '23:00', $testTimezone)));

        $this->assertTrue($password->resetRestrictions());
        $restriction = $this->createEmptyRestriction($password->getId(), $now);
        $this->assertTrue($restriction->addHumanFriendlyTimeRange('to 23:00'));
        $this->assertTrue($restriction->save());
        $restriction = $this->getFirstPasswordRestriction($password);
        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_TIME, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '23:01', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '23:00', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('H:i', '9:00', $testTimezone)));
    }

    public function test_DateRestrictions()
    {
        $testTimezone = 'Europe/Athens';
        $now = Carbon::createFromFormat('Y-m-d', '2020-04-12', $testTimezone);

        $user = $this->createUser();
        $password = $this->createPassword($user->getId());

        // Set both dates.
        $restriction = $this->createEmptyRestriction($password->getId(), $now);
        $this->assertFalse($restriction->addDateRange('hello', null));
        $this->assertFalse($restriction->addDateRange(null, 'hello'));
        $this->assertFalse($restriction->addDateRange(null, null));
        $this->assertTrue($restriction->addDateRange('2020-04-01', '2020-04-20'));
        $this->assertTrue($restriction->addDateRange('2020-04-01', '2020-04-20')); // Adding again.
        $this->assertTrue($restriction->save());
        $restriction = $this->getFirstPasswordRestriction($password);
        $this->assertTrue($restriction->hasDateRestrictions());

        $this->assertEquals($testTimezone, $restriction->getTimezone());
        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_DATE, $restriction->evaluate(now: Carbon::createFromFormat('Y-m-d', '2020-03-31', $testTimezone)));
        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_DATE, $restriction->evaluate(now: Carbon::createFromFormat('Y-m-d', '2020-04-21', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('Y-m-d', '2020-04-01', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('Y-m-d', '2020-04-20', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('Y-m-d', '2020-04-15', $testTimezone)));

        // Set date from.
        $this->assertTrue($password->resetRestrictions());
        $restriction = $this->createEmptyRestriction($password->getId(), $now);
        $this->assertTrue($restriction->addDateRange('2020-02-01', null));
        $this->assertTrue($restriction->save());
        $restriction = $this->getFirstPasswordRestriction($password);

        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_DATE, $restriction->evaluate(now: Carbon::createFromFormat('Y-m-d', '2020-01-31', $testTimezone)));
        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_DATE, $restriction->evaluate(now: Carbon::createFromFormat('Y-m-d', '2019-01-31', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('Y-m-d', '2020-02-01', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('Y-m-d', '2023-02-01', $testTimezone)));

        // Set date to.
        $this->assertTrue($password->resetRestrictions());
        $restriction = $this->createEmptyRestriction($password->getId(), $now);
        $this->assertTrue($restriction->addDateRange(null, '2020-08-01'));
        $this->assertTrue($restriction->save());
        $restriction = $this->getFirstPasswordRestriction($password);

        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_DATE, $restriction->evaluate(now: Carbon::createFromFormat('Y-m-d', '2020-08-02', $testTimezone)));
        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_DATE, $restriction->evaluate(now: Carbon::createFromFormat('Y-m-d', '2021-01-31', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('Y-m-d', '2020-08-01', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('Y-m-d', '2018-02-01', $testTimezone)));

        // Add multiple dates.
        $this->assertTrue($password->resetRestrictions());
        $restriction = $this->createEmptyRestriction($password->getId(), $now);
        $this->assertTrue($restriction->addDateRange('2020-08-01', '2020-08-01'));
        $this->assertTrue($restriction->addDateRange('2020-09-01', '2020-09-01'));
        $this->assertTrue($restriction->save());
        $restriction = $this->getFirstPasswordRestriction($password);
        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_DATE, $restriction->evaluate(now: Carbon::createFromFormat('Y-m-d', '2020-08-02', $testTimezone)));
        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_DATE, $restriction->evaluate(now: Carbon::createFromFormat('Y-m-d', '2020-09-02', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('Y-m-d', '2020-08-01', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('Y-m-d', '2020-09-01', $testTimezone)));

        $this->assertFalse($restriction->removeDateRange('2020-10-01', '2020-10-01'));
        $this->assertTrue($restriction->removeDateRange('2020-09-01', '2020-09-01'));
        $this->assertTrue($restriction->save());
        $restriction = $this->getFirstPasswordRestriction($password);
        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_DATE, $restriction->evaluate(now: Carbon::createFromFormat('Y-m-d', '2020-08-02', $testTimezone)));
        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_DATE, $restriction->evaluate(now: Carbon::createFromFormat('Y-m-d', '2020-09-02', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('Y-m-d', '2020-08-01', $testTimezone)));
        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_DATE, $restriction->evaluate(now: Carbon::createFromFormat('Y-m-d', '2020-09-01', $testTimezone)));

        $this->assertTrue($restriction->clearDateRanges());
        $this->assertTrue($restriction->save());
        $restriction = $this->getFirstPasswordRestriction($password);
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('Y-m-d', '2020-08-02', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('Y-m-d', '2020-09-02', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('Y-m-d', '2020-08-01', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('Y-m-d', '2020-09-01', $testTimezone)));

        // Work with human friendly data, as used from the front-end.
        $this->assertTrue($password->resetRestrictions());
        $restriction = $this->createEmptyRestriction($password->getId(), $now);
        $this->assertFalse($restriction->addHumanFriendlyDateRange('2020/03/30'));
        $this->assertTrue($restriction->addHumanFriendlyDateRange('2020-03-30'));
        $this->assertTrue($restriction->save());
        $restriction = $this->getFirstPasswordRestriction($password);
        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_DATE, $restriction->evaluate(now: Carbon::createFromFormat('Y-m-d', '2020-03-29', $testTimezone)));
        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_DATE, $restriction->evaluate(now: Carbon::createFromFormat('Y-m-d', '2020-03-31', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('Y-m-d', '2020-03-30', $testTimezone)));

        $this->assertTrue($password->resetRestrictions());
        $restriction = $this->createEmptyRestriction($password->getId(), $now);
        $this->assertTrue($restriction->addHumanFriendlyDateRange('2020-03-30 to 2020-04-29'));
        $this->assertTrue($restriction->save());
        $restriction = $this->getFirstPasswordRestriction($password);
        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_DATE, $restriction->evaluate(now: Carbon::createFromFormat('Y-m-d', '2020-03-29', $testTimezone)));
        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_DATE, $restriction->evaluate(now: Carbon::createFromFormat('Y-m-d', '2020-04-30', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('Y-m-d', '2020-03-30', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('Y-m-d', '2020-04-29', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('Y-m-d', '2020-04-11', $testTimezone)));

        $this->assertTrue($password->resetRestrictions());
        $restriction = $this->createEmptyRestriction($password->getId(), $now);
        $this->assertTrue($restriction->addHumanFriendlyDateRange('from 2020-03-15'));
        $this->assertTrue($restriction->save());
        $restriction = $this->getFirstPasswordRestriction($password);
        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_DATE, $restriction->evaluate(now: Carbon::createFromFormat('Y-m-d', '2020-03-14', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('Y-m-d', '2020-03-15', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('Y-m-d', '2020-03-16', $testTimezone)));

        $this->assertTrue($password->resetRestrictions());
        $restriction = $this->createEmptyRestriction($password->getId(), $now);
        $this->assertTrue($restriction->addHumanFriendlyDateRange('to 2020-03-15'));
        $this->assertTrue($restriction->save());
        $restriction = $this->getFirstPasswordRestriction($password);
        $this->assertEquals(PasswordResult::RESTRICTION_FAILED_DATE, $restriction->evaluate(now: Carbon::createFromFormat('Y-m-d', '2020-03-16', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('Y-m-d', '2020-03-15', $testTimezone)));
        $this->assertEquals(PasswordResult::SUCCESS, $restriction->evaluate(now: Carbon::createFromFormat('Y-m-d', '2020-03-14', $testTimezone)));
    }

    public function test_GetRestriction()
    {
        $testTimezone = 'Europe/Athens';
        $now = Carbon::createFromFormat('Y-m-d', '2020-04-12', $testTimezone);

        $user = $this->createUser();
        $password = $this->createPassword($user->getId());

        // Set both dates.
        $restriction = $this->createEmptyRestriction($password->getId(), $now);
        $this->assertFalse($restriction->addDateRange('hello', null));
        $this->assertFalse($restriction->addDateRange(null, 'hello'));
        $this->assertFalse($restriction->addDateRange(null, null));
        $this->assertTrue($restriction->addDateRange('2020-04-01', '2020-04-20'));
        $this->assertTrue($restriction->addDateRange('2020-04-01', '2020-04-20')); // Adding again.
        $this->assertTrue($restriction->save());
        $restriction = $this->getFirstPasswordRestriction($password);

        $this->assertFalse($password->getRestrictionById(-100));
        $createdRestriction = $password->getRestrictionById($restriction->getId());
        $this->assertEquals('App\Models\RemotePasswordRestriction', get_class($createdRestriction));
        $this->assertEquals($restriction->getId(), $createdRestriction->getId());

        $this->assertTrue($password->deleteRestrictionById($restriction->getId()));
        $this->assertFalse($password->getRestrictionById($restriction->getId()));
    }

    protected function getFirstPasswordRestriction(RemotePassword $password) : RemotePasswordRestriction
    {
        $restrictions = $password->getRestrictions();
        $this->assertEquals(1, $restrictions->count());
        /** @var RemotePasswordRestriction $restriction */
        return $restrictions->first();
    }

    protected function createEmptyRestriction(int $passwordId, Carbon|null $now) : RemotePasswordRestriction
    {
        /** @var RemotePasswordManager $passwordManager */
        $passwordManager = app()->make('passwordManager');
        $restriction = $passwordManager->createEmptyRestriction($passwordId);
        if ($now != null) {
            $this->assertTrue($restriction->setTimezone($now->getTimezone()));
        }
        return $restriction;
    }
}
// phpcs:ignoreFile
