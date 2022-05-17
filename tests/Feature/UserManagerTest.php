<?php

namespace Tests\Feature;

use App\Notifications\LockoutNotification;
use App\Notifications\UserActionTokenNotification;
use App\Services\Core\Settings;
use App\Services\ReturnTypes\UserTokenType;
use App\Services\UserManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use PragmaRX\Google2FA\Google2FA;
use Tests\Shared;
use Tests\TestCase;

class UserManagerTest extends TestCase
{
    use RefreshDatabase, Shared;

    /**
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function test_createUser()
    {
        /** @var UserManager $userManager */
        $userManager = app()->make('userManager');

        $firstUser = $userManager->createUser('pavel', 'pavel@example.com', 'HelloWorld!1!1', true, true);
        $this->assertEquals('App\Models\User', get_class($firstUser));
        $this->assertFalse($firstUser->isAdmin());

        // User already exists.
        $user = $userManager->createUser('pavel', 'pavel1@example.com', 'HelloWorld!1!1', true, true);
        $this->assertFalse($user);

        // Email already exists.
        $user = $userManager->createUser('pavel1', 'pavel@example.com', 'HelloWorld!1!1', true, true);
        $this->assertFalse($user);

        // Fail password validation.
        $user = $userManager->createUser('pavel1', 'pavel1@example.com', 'HelloWorld', true, true);
        $this->assertFalse($user);

        // Get the first user.
        $user = $userManager->getUser($firstUser->getId());
        $this->assertEquals('App\Models\User', get_class($user));

        $this->assertEquals(1, $userManager->all()->count());
        $this->assertEquals(1, $userManager->getUserCount());

        $user = $userManager->updateUser($user->getId(), 'new-username', 'new-email@example.com', false, false, true, true);
        $user = $userManager->getUser($firstUser->getId());
        $this->assertEquals('new-username', $user->username);
        $this->assertEquals('new-email@example.com', $user->email);
        $this->assertFalse($user->isEnabled());
        $this->assertFalse($user->isActivated());
        $this->assertTrue($user->isAdmin());
        $this->assertTrue($user->isLocked());

        $user = $userManager->updateUser($user->username, 'new-username', 'new-email@example.com', false, false, false, true);
        $user = $userManager->getUser($firstUser->getId());
        $this->assertFalse($user->isActivated());

        $newUser = $this->createUser();
        // Try to change their username/email to something that already exists.
        $this->assertFalse($userManager->updateUser($newUser, 'new-username', $newUser->email, true, true, true, true));
        $this->assertFalse($userManager->updateUser($newUser, $newUser->username, 'new-email@example.com', true, true, true, true));
        $this->assertFalse($userManager->updateUser(-123, $newUser->username, $newUser->email, true, true, true, true));
    }

    /**
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function test_Activation()
    {
        /** @var Settings $settings */
        $settings = app()->make('settings');
        $settings->set('uncompromised', false); // Disable password check.

        /** @var UserManager $userManager */
        $userManager = app()->make('userManager');
        $this->prepareMail();

        $user = $userManager->createUser('pavel', 'pavel@example.com', 'HelloWorld!1!1', true, true);
        $this->assertEquals('App\Models\User', get_class($user));

        // User is already activated.
        $userManager->sendActivationToken($user->username, $user->email);
        $this->assertEquals("", $user->getActivationToken());

        // Check if activation token exists.
        $user = $userManager->createUser('pavel1', 'pavel1@example.com', 'HelloWorld!1!1', false, true);
        $this->assertEquals('App\Models\User', get_class($user));

        $userManager->sendActivationToken($user->username, null);
        $originalToken = $user->getActivationToken();
        $this->assertEquals(32, strlen($originalToken));

        $userManager->sendActivationToken($user->username, null);
        $newToken = $user->getActivationToken();
        $this->assertEquals(32, strlen($newToken));

        $this->assertNotEquals($originalToken, $newToken);
    }

    /**
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function test_ActivationMail()
    {
        $this->prepareMail();

        /** @var UserManager $userManager */
        $userManager = app()->make('userManager');

        $user = $userManager->createUser('pavel', 'pavel@example.com', 'HelloWorld!1!1', false, true);
        $this->assertEquals('App\Models\User', get_class($user));

        Notification::assertNothingSent();

        $userManager->sendActivationToken($user->username, $user->email);
        Notification::assertSentTo(
            $user,
            UserActionTokenNotification::class,
            function ($notification, $channels) use ($user) {
                return count($channels) == 1
                    && in_array('mail', $channels)
                    && $notification->tokenType == UserTokenType::ACCOUNT_ACTIVATION
                    && $notification->user->getId() == $user->getId();
            }
        );
    }

    /**
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function test_ActivateUser()
    {
        $this->prepareMail();
        /** @var UserManager $userManager */
        $userManager = app()->make('userManager');

        $user = $userManager->createUser('pavel', 'pavel@example.com', 'HelloWorld!1!1', false, true);
        $this->assertEquals('App\Models\User', get_class($user));

        $userManager->sendActivationToken($user->username, $user->email);
        $originalToken = $user->getActivationToken();

        $this->assertFalse($userManager->activate('1234', $user->email));
        $this->assertFalse($userManager->activate(strtoupper($originalToken), $user->email));
        $this->assertFalse($userManager->activate($originalToken, 'invalid@example.com'));
        $this->assertTrue($userManager->activate($originalToken, $user->email));

        $user = $userManager->getUser($user->getId()); // Refresh user.
        $this->assertTrue($user->isActivated());
    }

    public function test_ReSendActivation()
    {
        $this->prepareMail();
        /** @var UserManager $userManager */
        $userManager = app()->make('userManager');

        $user = $userManager->createUser('pavel', 'pavel@example.com', 'HelloWorld!1!1', false, true);
        $this->assertEquals('App\Models\User', get_class($user));

        $userManager->sendActivationToken($user->username, $user->email);
        $originalToken = $user->getActivationToken();
        $this->assertFalse($userManager->sendActivationToken('', ''));
        $this->assertFalse($userManager->sendActivationToken(null, null));
        $this->assertFalse($userManager->sendActivationToken('some', 'thing'));
        $this->assertTrue($userManager->sendActivationToken($user->username, $user->email));
        $newToken = $user->getActivationToken();
        $this->assertNotEquals($originalToken, $newToken);
        $this->assertTrue($userManager->activate($newToken, $user->email));

        $user = $userManager->getUser($user->getId()); // Refresh user.
        $this->assertTrue($user->isActivated());
    }

    /**
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function test_UserStatus()
    {
        /** @var UserManager $userManager */
        $userManager = app()->make('userManager');

        $user = $userManager->createUser('pavel', 'pavel@example.com', 'HelloWorld!1!1', false, false);
        $this->assertEquals('App\Models\User', get_class($user));

        $this->assertFalse($user->isEnabled());

        $userManager->toggleUserStatus($user, true);
        $user = $userManager->getUser($user->getId());
        $this->assertTrue($user->isEnabled());

        $userManager->toggleUserStatus($user, false);
        $user = $userManager->getUser($user->getId());
        $this->assertFalse($user->isEnabled());

        // Check for inexistent user.
        $this->assertFalse($userManager->toggleUserStatus(123, false));
    }

    /**
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function test_PasswordUpdate()
    {
        /** @var UserManager $userManager */
        $userManager = app()->make('userManager');

        $originalPassword = 'HelloWorld!1!1';
        $newPassword = 'HelloWorld!2!2';

        $user = $userManager->createUser('pavel', 'pavel@example.com', $originalPassword, false, false);
        $this->assertEquals('App\Models\User', get_class($user));

        $this->assertFalse($userManager->updateUserPassword($user, "wrong-password", $newPassword, true));
        $this->assertFalse($userManager->updateUserPassword($user, $originalPassword, "short", true));

        $this->assertTrue($userManager->updateUserPassword($user, $originalPassword, $newPassword, true));
        //$user = $userManager->getUser($user->getId());
        $this->assertTrue($userManager->updateUserPassword($user->getId(), $newPassword, $originalPassword, true));
        // User does not exist.
        $this->assertFalse($userManager->updateUserPassword(1234, $newPassword, $originalPassword, true));
    }

    public function test_PasswordReset()
    {
        $this->prepareMail();

        /** @var UserManager $userManager */
        $userManager = app()->make('userManager');
        $userManager->disablePasswordComplexity();

        $user = $userManager->createUser('pavel', 'pavel@example.com', "1234", false, false);
        $this->assertEquals('App\Models\User', get_class($user));

        $this->assertFalse($userManager->sendPasswordResetToken("user-does-not-exist",  "pavel@example.com"));
        Mail::assertNothingSent();
        Notification::assertNothingSent();

        $this->assertFalse($userManager->sendPasswordResetToken("pavel",  "email-does-not-exist"));
        Mail::assertNothingSent();
        Notification::assertNothingSent();

        $this->assertTrue($userManager->sendPasswordResetToken("pavel", "pavel@example.com"));
        Notification::assertSentTo(
            $user,
            UserActionTokenNotification::class,
            function ($notification, $channels) use ($user) {
                return count($channels) == 1
                    && in_array('mail', $channels)
                    && $notification->tokenType == UserTokenType::PASSWORD_RESET
                    && $notification->user->getId() == $user->getId();
            }
        );

        $resetToken = $user->getPasswordResetToken();
        $this->assertEquals(64, strlen($resetToken));

        $this->assertFalse($userManager->resetUserPassword("pavel@example.com", "invalid-token", "password"));
        $this->assertFalse($userManager->resetUserPassword("invalid@example.com", $resetToken, "password"));

        $userManager->enablePasswordCompexity();
        $this->assertFalse($userManager->resetUserPassword("pavel@example.com", $resetToken, "password"));
        $this->assertTrue($userManager->resetUserPassword("pavel@example.com", $resetToken, "Some-Complex-Password1"));
        $this->assertEmpty($user->getPasswordResetToken());
    }

    public function test_UserDateTimeZone()
    {
        /** @var UserManager $userManager */
        $userManager = app()->make('userManager');

        $user = $userManager->createUser('pavel11', 'pavel@example.com', 'HelloWorld!1!1', false, false);
        $this->assertEquals('App\Models\User', get_class($user));

        $this->assertFalse($userManager->setTimezone($user->getId(), 'invalid-one'));
        $this->assertFalse($userManager->setTimezone(-1, 'invalid-one'));
        $this->assertTrue($userManager->setTimezone($user, 'Europe/London'));
        $this->assertFalse($userManager->setDateTimeFormat(-1, 'meh'));
        $this->assertTrue($userManager->setDateTimeFormat($user->getId(), 'meh'));
        $this->assertTrue($userManager->setDateTimeFormat($user, 'Y-m-d'));

        $user1 = $userManager->getUser($user->getId());
        $this->assertEquals('Europe/London', $user1->getTimezone());
        $this->assertEquals('Y-m-d', $user1->getDateTimeFormat());
    }

    public function test_TwoFactorAuthentication()
    {
        $username = 'pavel112';
        $backupCount = 8;

        /** @var UserManager $userManager */
        $userManager = app()->make('userManager');

        $user = $userManager->createUser($username, 'pavel2@example.com', 'HelloWorld!1!1', false, false);
        $this->assertEquals('App\Models\User', get_class($user));

        $this->assertEquals($backupCount, count($user->get2FABackupCodes()));
        $backupCode = $user->get2FABackupCodes()[0];
        $this->assertTrue(in_array($backupCode, $user->get2FABackupCodes()));
        $this->assertFalse($user->isValid2FABackupCode('should-not-exist'));
        $this->assertTrue($user->isValid2FABackupCode($backupCode));
        $this->assertEquals($backupCount, count($user->get2FABackupCodes()));
        $this->assertTrue($user->use2FABackupCode($backupCode));
        $this->assertFalse($user->isValid2FABackupCode($backupCode));
        $this->assertEquals(--$backupCount, count($user->get2FABackupCodes()));

        /** @var Google2FA $google2fa */
        $google2fa = app()->make('pragmarx.google2fa');
        $code = $google2fa->getCurrentOtp($user->getOTPSecret());
        $this->assertFalse($user->isValid2FACode('000000'));
        $this->assertFalse($google2fa->verify('000000', $user->getOTPSecret()));
        $this->assertTrue($user->isValid2FACode($code));
        $this->assertTrue($google2fa->verify($code, $user->getOTPSecret()));
        $this->assertNotEmpty($user->get2FAQRImage());

        $code = $google2fa->getCurrentOtp($user->getOTPSecret());
        $this->assertFalse($userManager->validate2FA('no-user', $code));
        $this->assertTrue($userManager->validate2FA($username, $code));
        $this->assertFalse($userManager->validate2FA($username, $code));    // Re-play.
        $this->assertFalse($userManager->validate2FA($username, 'invalid'));
        $this->assertTrue($userManager->validate2FA($username, $user->get2FABackupCodes()[0]));
    }

    public function test_userLockout()
    {
        /** @var UserManager $userManager */
        $userManager = app()->make('userManager');

        $user = $userManager->createUser('pavel999', 'pavel999@example.com', 'HelloWorld!1!1', true, true);
        $this->assertEquals('App\Models\User', get_class($user));

        $this->assertEquals(0, $user->getLoginAttempts());
        $this->assertFalse($userManager->isUserLockedOut($user->username, false, false));

        $threshold = 5;
        $userManager->setLockoutThreshold($threshold);
        $user->setLoginAttempts($threshold - 1);
        $this->assertFalse($userManager->isUserLockedOut($user->username, false, false));
        $this->assertFalse($userManager->isUserLockedOut($user->username, true, false));

        $this->prepareMail();
        $this->assertTrue($userManager->isUserLockedOut($user->username, true, true));
        Notification::assertSentTo(
            $user,
            LockoutNotification::class,
            function ($notification, $channels) use ($user) {
                return count($channels) == 1
                    && in_array('mail', $channels)
                    && $notification->user->getId() == $user->getId();
            }
        );

        $this->assertTrue($userManager->isUserLockedOut($user->username, false, false));
        $this->assertFalse($userManager->unlockUserAccount($user->username, 'does-not-exist'));
        $this->assertTrue($userManager->unlockUserAccount($user->username, ''));
        $this->assertFalse($userManager->isUserLockedOut($user->username, false, false));
        $user->refresh();
        $this->assertEquals(0, $user->getLoginAttempts());

        $this->assertFalse($userManager->isUserLockedOut('does-not-exist', '', false));

        $this->prepareMail();
        $this->assertFalse($userManager->sendUnlockEmail('does-not-exist', ''));
        $this->assertFalse($userManager->sendUnlockEmail($user->username, $user->email));
        // Lock user.
        $user->setLoginAttempts($threshold + 1);
        $this->assertTrue($userManager->isUserLockedOut($user->username, false, false));

        $this->assertTrue($userManager->sendUnlockEmail($user->username, $user->email));

        Notification::assertSentTo(
            $user,
            UserActionTokenNotification::class,
            function ($notification, $channels) use ($user) {
                return count($channels) == 1
                    && in_array('mail', $channels)
                    && $notification->tokenType == UserTokenType::UNLOCK_ACCOUNT
                    && $notification->user->getId() == $user->getId();
            }
        );

        $user->refresh();
        $this->assertNotEmpty($user->getUnlockToken());

        $userNotEnabled = $this->createUser();
        $userNotEnabled->enabled = false;
        $userNotEnabled->setLoginAttempts(999);
        $userNotEnabled->save();
        $this->assertFalse($userManager->isUserLockedOut($userNotEnabled->username, false, false));

        $userNotActivated = $this->createUser();
        $userNotActivated->activated = false;
        $userNotActivated->setLoginAttempts(999);
        $userNotActivated->save();
        $this->assertFalse($userManager->isUserLockedOut($userNotActivated->username, false, false));

        $userEnabled = $this->createUser();
        $userEnabled->setLoginAttempts(999);
        $this->assertTrue($userManager->isUserLockedOut($userEnabled->username, false, false));
    }
}
// phpcs:ignoreFile
