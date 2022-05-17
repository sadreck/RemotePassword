<?php

namespace Tests\Feature;

use App\Services\PasswordLogManager;
use App\Services\RemotePasswordManager;
use App\Services\ReturnTypes\LogSearchParameters;
use App\Services\ReturnTypes\PasswordResult;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Request;
use Tests\Shared;
use Tests\TestCase;

class PasswordLogManagerTest extends TestCase
{
    use RefreshDatabase, WithFaker, Shared;

    public function test_Logging()
    {
        // Otherwise the time queries are messed up due to retrievePassword().
        $now = Carbon::createFromFormat('Y-m-d H:i:s', '2021-04-21 14:40:12');

        /** @var PasswordLogManager $logManager */
        $logManager = app()->make('passwordLogManager');

        /** @var RemotePasswordManager $passwordManager */
        $passwordManager = app()->make('passwordManager');

        $user1 = $this->createUser();
        $user1Password1 = $this->createPassword($user1->getId());
        $user1Password2 = $this->createPassword($user1->getId());

        $user2 = $this->createUser();
        $user2Password1 = $this->createPassword($user2->getId());
        $user2Password2 = $this->createPassword($user2->getId());

        $user3 = $this->createUser();
        $user3Password1 = $this->createPassword($user3->getId());
        $user3Password2 = $this->createPassword($user3->getId());

        $time = Carbon::createFromFormat('Y-m-d H:i:s', '2021-04-17 13:40:12');
        for ($i = 0; $i < 11; $i++) {
            $time->addSeconds(31);
            $logManager->log($user1Password1, PasswordResult::SUCCESS, "", "127.0.0.2", $time);

            $time->subSeconds(7);
            $logManager->log($user1Password2, PasswordResult::RESTRICTION_FAILED_IP, "", "192.168.1.11", $time);

            $time->addMinutes(9);
            $logManager->log($user2Password1, PasswordResult::DISABLED, "", "10.22.93.110", $time);

            $time->addSeconds(91);
            $logManager->log($user3Password1, PasswordResult::SUCCESS, "", "172.18.3.1", $time);

            $time->addMinutes(8);
            $logManager->log($user2Password2, PasswordResult::RESTRICTION_FAILED_USERAGENT, "", "10.11.12.13", $time);

            $passwordManager->retrievePassword($user2Password1->token1, $user2Password1->token2, 'raw', now: $now);
        }

        $time = Carbon::createFromFormat('Y-m-d H:i:s', '2021-04-18 13:40:12');
        for ($i = 0; $i < 9; $i++) {
            $time->addSeconds(12);
            $logManager->logInvalid("127.0.0.4", "Random Info", $time);

            $time->addSeconds(99);
            $logManager->logInvalid("192.168.1.1", "Random Info 2", $time);

            $time->addSeconds(111);
            $logManager->logInvalid("10.22.0.1", "Random Info 3", $time);
        }

        $time = Carbon::createFromFormat('Y-m-d H:i:s', '2021-04-25 13:40:12');
        for ($i = 0; $i < 19; $i++) {
            $time->addMinutes(21);
            $logManager->log($user3Password2, PasswordResult::SUCCESS, "", "10.99.110.223", $time);
        }

        // Uses.
        $this->assertEquals(11, $logManager->getPasswordUses($user2Password1->getId()));

        // Valid logs.
        $this->assertEquals(22, $logManager->getUserPasswordAccessLogs($user1->getId())->count());
        $this->assertEquals(13, $logManager->getUserPasswordAccessLogs($user1->getId(), paginateBy: 13)->count());
        $this->assertEquals(11, $logManager->getUserPasswordAccessLogs($user1->getId(), PasswordResult::SUCCESS)->count());
        $this->assertEquals(11, $logManager->getPasswordLogs($user1Password2->getId())->count());
        $this->assertEquals(11, $logManager->getPasswordLogs($user1Password1->getId(), PasswordResult::SUCCESS)->count());

        // Search a user's specific password.
        $search = new LogSearchParameters();
        $search->setUserId($user1->getId())->setPasswordIds([$user1Password1->getId()]);
        $this->assertEquals(11, $logManager->search($search)->count());

        // Search per user and their IP.
        $search->setPasswordIds([])->setIpAddress("192.168.1.11");
        $this->assertEquals(11, $logManager->search($search)->count());

        $search->setIpAddress("127.0.0.1");
        $this->assertEquals(0, $logManager->search($search)->count());

        // User searches for pwd that does not belong to them.
        $search->clear()->setUserId($user1->getId())->setPasswordIds([$user3Password1->getId()]);
        // Should return the user's passwords count.
        $this->assertEquals(22, $logManager->search($search)->count());
        // Should return the specific password count.
        $search->setPasswordIds([$user1Password1->getId()]);
        $this->assertEquals(11, $logManager->search($search)->count());

        $search->clear()->setDateFrom('2021-04-25');
        $this->assertEquals(19, $logManager->search($search)->count());

        // Should return ALL.
        $search->clear()->setDateFrom('2021-04-17')->setPerPage(9999);
        $this->assertEquals(85, $logManager->search($search)->count());

        $search->clear()->setDateTo('2021-04-17')->setPerPage(999);
        $this->assertEquals(55, $logManager->search($search)->count());

        $search->clear()->setDateFrom('2021-04-18')->setDateTo('2021-05-01')->setPerPage(999);
        $this->assertEquals(30, $logManager->search($search)->count());

        $search->clear()->setTimeFrom('01:00')->setTimeTo('23:00')->setPerPage(999);
        $this->assertEquals(85, $logManager->search($search)->count());

        $search->clear()->setTimeFrom('14:00')->setPerPage(999);
        $this->assertEquals(78, $logManager->search($search)->count());

        $search->clear()->setTimeFrom('14:00')->setTimeTo('14:30')->setPerPage(999);
        $this->assertEquals(9, $logManager->search($search)->count());

        // Invalid logs.
        $search->clear()->setIpAddress('10.22.0.1');
        $this->assertEquals(9, $logManager->searchInvalid($search)->count());
        $search->setPerPage(4);
        $this->assertEquals(4, $logManager->searchInvalid($search)->count());
        $search->setPerPage(0);
        $this->assertEquals(9, $logManager->searchInvalid($search)->count());

        $search->clear()->setDateFrom('2021-04-18');
        $this->assertEquals(27, $logManager->searchInvalid($search)->count());
        $search->setIpAddress('192.168.1.1');
        $this->assertEquals(9, $logManager->searchInvalid($search)->count());

        $search->clear()->setDateTo('2021-04-19');
        $this->assertEquals(27, $logManager->searchInvalid($search)->count());

        $search->clear()->setTimeFrom('13:40:12');
        $this->assertEquals(27, $logManager->searchInvalid($search)->count());
        $search->setTimeTo('13:41:50');
        $this->assertEquals(1, $logManager->searchInvalid($search)->count());

        // Friendly results.
        $this->assertEquals(8, count($logManager->getFriendlyPasswordResults()));
    }

    public function test_SearchRequestParams()
    {
        /** @var PasswordLogManager $logManager */
        $logManager = app()->make('passwordLogManager');

        $user1 = $this->createUser();

        $request = new Request(
            [
                'password' => [199, -123],
                'result' => [2, 999],
                'ip' => '192.168.1.99',
                'date_from' => '1999-12-13',
                'date_to' => '2020-11-12',
                'time_from' => '14:19',
                'time_to' => '22:30',
                'page' => 20,
                'per_page' => 100
            ]
        );

        $search = $logManager->getSearchParameters($request, $user1->getId());
        $this->assertEquals(199, $search->getPasswordIds()[0]);
        $this->assertEquals(2, $search->getResults()[0]);
        $this->assertEquals('192.168.1.99', $search->getIpAddress());
        $this->assertEquals('1999-12-13', $search->getDateFrom());
        $this->assertEquals('2020-11-12', $search->getDateTo());
        $this->assertEquals('14:19', $search->getTimeFrom());
        $this->assertEquals('22:30', $search->getTimeTo());
        $this->assertEquals(20, $search->getPage());
        $this->assertEquals(100, $search->getPerPage());
        $this->assertFalse($search->inResults(999));

        $request = new Request(
            [
                'password' => 199,
                'result' => 2,
                'ip' => '192.168.1.99',
                'date_from' => '1999-12-13',
                'date_to' => 'invalid', // Invalid
                'time_from' => '14:19',
                'time_to' => 'invalid', // Invalid
                'page' => 20,
                'per_page' => 100
            ]
        );
        $search = $logManager->getSearchParameters($request, $user1->getId());
        $this->assertEquals(null, $search->getDateTo());
        $this->assertEquals(null, $search->getTimeTo());
    }

    public function test_UserLogin()
    {
        /** @var PasswordLogManager $logManager */
        $logManager = app()->make('passwordLogManager');

        $user = $this->createUser();
        $loginDate = Carbon::createFromFormat('Y-m-d H:i:s', '2021-04-17 13:40:12');
        $logManager->logUserLogin($user, '127.1.2.3', $loginDate);

        $lastLogin = $user->getLastLogin();
        $this->assertNotEmpty($lastLogin);
        $this->assertEquals('2021-04-17 13:40:12', $lastLogin);
    }

    public function test_ErrorLogs()
    {
        /** @var PasswordLogManager $logManager */
        $logManager = app()->make('passwordLogManager');

        $user = $this->createUser();
        $logManager->logError($user->getId(), '127.1.2.3', 'TestAgent1', 'Error Name1', 'Error Description1');
        $logManager->logError($user->getId(), '127.4.5.6', 'TestAgent2', 'Error Name2', 'Error Description2');

        $request = new Request(
            [
                'ip' => '',
                'date_from' => '',
                'date_to' => '',
                'time_from' => '',
                'time_to' => '',
                'page' => 1,
                'per_page' => 100
            ]
        );

        $search = $logManager->getSearchParameters($request, $user->getId());
        $errorResults = $logManager->searchErrors($search);
        $this->assertEquals(2, $errorResults->count());
        $this->assertEquals('127.4.5.6', $errorResults->first()->ip);

        $search->setPerPage(1);
        $errorResults = $logManager->searchErrors($search);
        $this->assertEquals(1, $errorResults->count());

        $search->setIpAddress('127.1.2.3');
        $errorResults = $logManager->searchErrors($search);
        $this->assertEquals(1, $errorResults->count());
        $this->assertEquals('127.1.2.3', $errorResults->first()->ip);

        $search->setPerPage(0)->setIpAddress('');
        $this->assertEquals(2, $logManager->searchErrors($search)->count());
    }
}
// phpcs:ignoreFile
