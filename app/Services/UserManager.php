<?php
namespace App\Services;

use App\Events\UserActivated;
use App\Events\UserCreated;
use App\Events\UserLocked;
use App\Events\UserPasswordChanged;
use App\Models\User;
use App\Models\UserToken;
use App\Notifications\UserActionTokenNotification;
use App\Services\ReturnTypes\UserTokenType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

class UserManager
{
    /** @var int */
    protected int $lockoutThreshold = 10;

    /**
     * @return int
     */
    public function getLockoutThreshold(): int
    {
        return $this->lockoutThreshold;
    }

    /**
     * @param int $lockoutThreshold
     * @return UserManager
     */
    public function setLockoutThreshold(int $lockoutThreshold): UserManager
    {
        $this->lockoutThreshold = $lockoutThreshold;
        return $this;
    }

    /**
     * @param PasswordComplexity $passwordManager
     */
    public function __construct(protected PasswordComplexity $passwordManager)
    {
        // Nothing.
    }

    /**
     * @return void
     */
    public function disablePasswordComplexity() : void
    {
        $this->passwordManager->disable();
    }

    /**
     * Should only be called if disablePasswordComplexity() was called first. Otherwise, it's redundant as the default
     * is to have it enabled.
     * @return void
     */
    public function enablePasswordCompexity() : void
    {
        $this->passwordManager->enable();
    }

    /**
     * @param int $id
     * @return User|null
     */
    public function getUser(int $id) : User|null
    {
        return $this->loadUserData(User::find($id));
    }

    /**
     * @return Collection
     */
    public function all() : Collection
    {
        return $this->loadUserData(User::all());
    }

    /**
     * @param Collection|User|null $oneOrMany
     * @return Collection|User|null
     */
    protected function loadUserData(Collection|User|null $oneOrMany) : Collection|User|null
    {
        if ($oneOrMany instanceof User) {
            $oneOrMany = $this->setCustomUserData($oneOrMany);
        } elseif ($oneOrMany instanceof Collection) {
            /** @var User $item */
            foreach ($oneOrMany as $item) {
                $item = $this->setCustomUserData($item);
            }
        }
        return $oneOrMany;
    }

    /**
     * @param User $user
     * @return User
     */
    protected function setCustomUserData(User $user) : User
    {
        $user->setLockoutThreshold($this->getLockoutThreshold());
        return $user;
    }

    /**
     * @param string|null $username
     * @param string|null $email
     * @return User|null
     */
    public function findUser(string|null $username, string|null $email) : User|null
    {
        $where = [];
        if (isset($username) && !empty($username)) {
            $where['username'] = $username;
        }
        if (isset($email) && !empty($email)) {
            $where['email'] = $email;
        }
        if (count($where) == 0) {
            return null;
        }
        return $this->loadUserData(User::where($where)->first());
    }

    /**
     * @param string $username
     * @param string $email
     * @param string $password
     * @param bool $activated
     * @param bool $enabled
     * @return User|bool
     */
    public function createUser(
        string $username,
        string $email,
        string $password,
        bool $activated,
        bool $enabled
    ) : User|bool {
        $username = strtolower($username);
        $email = strtolower($email);

        $usernameExists = User::where('username', $username)->exists();
        $emailExists = User::where('email', $email)->exists();
        if ($usernameExists || $emailExists) {
            return false;
        } elseif (!$this->validatePassword($password)) {
            // You should really be making this check before calling createUser().
            return false;
        }

        $user = new User([
            'username' => $username,
            'email' => $email,
            'password' => Hash::make($password),
            'activated' => $activated,
            'enabled' => $enabled,
            'otp_secret' => $this->generate2FASecretKey(),
            'otp_backup_codes' => json_encode($this->generate2FABackupCodes())
        ]);
        $user->save();
        UserCreated::dispatch($user);
        return $user;
    }

    public function updateUser(
        string|int|User $user,
        string $username,
        string $email,
        bool $enabled,
        bool $activated,
        bool $admin,
        bool $locked
    ) : User|bool {
        if (is_int($user)) {
            $user = $this->getUser($user);
        } elseif (is_string($user)) {
            $user = $this->findUser($user, null);
        }
        if (!$user) {
            // User does not exist.
            return false;
        }

        // Only update properties that have changed.
        if ($user->username != $username) {
            $duplicateUsername = $this->findUser($username, null);
            if ($duplicateUsername && $duplicateUsername->getId() != $user->getId()) {
                // Another user with this username already exists.
                return false;
            }
            $user->username = $username;
        }

        if ($user->email != $email) {
            $duplicateEmail = $this->findUser(null, $email);
            if ($duplicateEmail && $duplicateEmail->getId() != $user->getId()) {
                // Another user with this email already exists.
                return false;
            }
            $user->email = $email;
        }

        if ($user->isEnabled() != $enabled) {
            $user->enabled = $enabled;
        }

        if ($user->isActivated() != $activated) {
            $user->activated = $activated;
        }

        if ($user->isAdmin() != $admin) {
            $user->admin = $admin;
        }

        if ($user->isLocked() != $locked) {
            $user->setLoginAttempts($locked ? $this->getLockoutThreshold() + 1 : 0);
        }

        return $user->save() ? $user : false;
    }

    /**
     * @param $password
     * @return bool
     */
    public function validatePassword($password) : bool
    {
        return $this->passwordManager->validate($password);
    }

    /**
     * @param string|null $username
     * @param string|null $email
     * @return bool
     */
    public function sendActivationToken(string|null $username, string|null $email) : bool
    {
        $user = $this->findUser($username, $email);
        if (!$user) {
            return false;
        } elseif ($user->isActivated()) {
            return true;
        }

        if (!$this->createUserToken($user, UserTokenType::ACCOUNT_ACTIVATION, 32)) {
            // @codeCoverageIgnoreStart
            return false;
            // @codeCoverageIgnoreEnd
        }

        try {
            $user->notify(new UserActionTokenNotification($user, UserTokenType::ACCOUNT_ACTIVATION));
            // @codeCoverageIgnoreStart
        } catch (\Exception $e) {
            /** @var PasswordLogManager $logsManager */
            $logsManager = app()->make('passwordLogManager');
            $logsManager->logError(
                $user->getId(),
                \Request()->ip(),
                \Request()->userAgent(),
                $e->getMessage(),
                $e->getTraceAsString()
            );
            return false;
            // @codeCoverageIgnoreEnd
        }
        return true;
    }

    /**
     * @param string|null $username
     * @param string|null $email
     * @return bool
     */
    public function sendPasswordResetToken(string|null $username, string|null $email) : bool
    {
        $user = $this->findUser($username, $email);
        if (!$user) {
            return false;
        }

        $this->createUserToken($user, UserTokenType::PASSWORD_RESET);
        try {
            $user->notify(new UserActionTokenNotification($user, UserTokenType::PASSWORD_RESET));
            // @codeCoverageIgnoreStart
        } catch (\Exception $e) {
            /** @var PasswordLogManager $logsManager */
            $logsManager = app()->make('passwordLogManager');
            $logsManager->logError(
                $user->getId(),
                \Request()->ip(),
                \Request()->userAgent(),
                $e->getMessage(),
                $e->getTraceAsString()
            );
            return false;
            // @codeCoverageIgnoreEnd
        }
        return true;
    }

    /**
     * @param User $user
     * @param UserTokenType $type
     * @param int $tokenLength
     * @return bool
     */
    protected function createUserToken(User $user, UserTokenType $type, int $tokenLength = 64) : bool
    {
        // Disable any previous tokens.
        UserToken
            ::where('user_id', $user->getId())
            ->where('type', $type)
            ->update(['active' => false]);

        // Create a new one.
        $resetToken = UserToken::create([
            'user_id' => $user->getId(),
            'type' => $type,
            'token' => $this->generateToken($tokenLength),
            'used' => false,
            'active' => true
        ]);

        return true;
    }

    /**
     * @param int $length
     * @return string
     */
    protected function generateToken(int $length) : string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyz';
        $output = [];
        for ($i = 0; $i < $length; $i++) {
            $output[] = $chars[rand(0, strlen($chars) - 1)];
        }
        return implode('', $output);
    }

    /**
     * @param string $token
     * @param string $email
     * @param UserTokenType $type
     * @return array|bool
     */
    public function getUserTokenData(string $token, string $email, UserTokenType $type) : array|bool
    {
        // Check if there's any pending activation.
        $record = UserToken
            ::where('active', true)
            ->where('used', false)
            ->where('token', $token)
            ->where('type', $type)
            ->first();
        if (!$record) {
            // No such token found, return.
            return false;
        }

        $user = $this->getUser($record->user_id);
        if (!$user) {
            // This shouldn't have happened, but hey ho.
            // @codeCoverageIgnoreStart
            return false;
            // @codeCoverageIgnoreEnd
        } elseif (strtolower($user->email) != strtolower($email)) {
            return false;
        } elseif ($token != $record->token) {
            // Annoyingly, the above SELECT query is case-insensitive,
            // so we need to also manually check the token,just in case.
            return false;
        }

        return [$user, $record];
    }

    /**
     * @param string $token
     * @param string $email
     * @return bool
     */
    public function activate(string $token, string $email) : bool
    {
        $data = $this->getUserTokenData($token, $email, UserTokenType::ACCOUNT_ACTIVATION);
        if (is_bool($data)) {
            return $data;
        }
        [$user, $record] = $data;

        $record->used = true;
        $record->save();

        $user->activated = true;
        $user->save();

        UserActivated::dispatch($user);
        return true;
    }

    /**
     * @param User|int $user
     * @param $enabled
     * @return bool
     */
    public function toggleUserStatus(User|int $user, $enabled) : bool
    {
        if (is_int($user)) {
            if (!$user = $this->getUser($user)) {
                return false;
            }
        }

        $user->enabled = $enabled;
        $user->save();
        return true;
    }

    /**
     * @param User|int $user
     * @param string|null $previousPassword
     * @param string|null $newPassword
     * @param bool $confirmPreviousPassword
     * @return bool
     */
    public function updateUserPassword(
        User|int $user,
        string|null $previousPassword,
        string|null $newPassword,
        bool $confirmPreviousPassword
    ) : bool {
        if (is_int($user)) {
            if (!$user = $this->getUser($user)) {
                return false;
            }
        }

        if (!$this->validatePassword($newPassword)) {
            return false;
        }

        if ($confirmPreviousPassword) {
            if (!Hash::check($previousPassword, $user->password)) {
                return false;
            }
        }

        $user->password = Hash::make($newPassword);
        $user->save();
        UserPasswordChanged::dispatch($user);
        return true;
    }

    /**
     * @param string|null $email
     * @param string|null $token
     * @param $newPassword
     * @return bool
     */
    public function resetUserPassword(string|null $email, string|null $token, $newPassword) : bool
    {
        $user = $this->findUser(null, $email);
        if (!$user) {
            return false;
        }

        if ($user->getPasswordResetToken() != $token) {
            return false;
        }

        if (!$this->updateUserPassword($user, null, $newPassword, false)) {
            return false;
        }

        // Make sure all tokens are marked as used.
        UserToken
            ::where('user_id', $user->getId())
            ->where('type', UserTokenType::PASSWORD_RESET)
            ->update(['used' => true]);
        return true;
    }

    /**
     * @param User|int $user
     * @param string $timezone
     * @return bool
     */
    public function setTimezone(User|int $user, string $timezone) : bool
    {
        if (is_int($user)) {
            if (!$user = $this->getUser($user)) {
                return false;
            }
        }

        if (!in_array($timezone, timezone_identifiers_list())) {
            return false;
        }

        $user->settings()->set('timezone', $timezone);
        return $user->save();
    }

    /**
     * @param User|int $user
     * @param string $format
     * @return bool
     */
    public function setDateTimeFormat(User|int $user, string $format) : bool
    {
        if (is_int($user)) {
            if (!$user = $this->getUser($user)) {
                return false;
            }
        }

        $user->settings()->set('dateformat', $format);
        return $user->save();
    }

    /**
     * @return string
     */
    public function generate2FASecretKey() : string
    {
        try {
            /** @var Google2FA $google2fa */
            $google2fa = app('pragmarx.google2fa');
            return $google2fa->generateSecretKey();
            // @codeCoverageIgnoreStart
        } catch (\Exception $e) {
            return '';
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * @param int $amount
     * @param int $length
     * @return array
     */
    public function generate2FABackupCodes(int $amount = 8, int $length = 32) : array
    {
        $codes = [];
        for ($i = 0; $i < $amount; $i++) {
            $codes[] = rtrim(chunk_split($this->generateToken($length), 8, '-'), '-');
        }
        return $codes;
    }

    /**
     * @param string $username
     * @param string $code
     * @return bool
     */
    public function validate2FA(string $username, string $code) : bool
    {
        $user = $this->findUser($username, null);
        if (!$user) {
            return false;
        }

        if ($user->getLastUsedOTP() == $code) {
            // Re-play?
            return false;
        }

        if ($user->isValid2FACode($code)) {
            return $user->saveLastUsedOTP($code);
        }

        if (!$user->isValid2FABackupCode($code)) {
            return false;
        }
        return $user->use2FABackupCode($code);
    }

    /**
     * @param string $username
     * @param bool $countAsAttempt
     * @param bool $notifyUser
     * @return bool
     */
    public function isUserLockedOut(string $username, bool $countAsAttempt, bool $notifyUser) : bool
    {
        $user = $this->findUser($username, null);
        if (!$user) {
            return false;
        } elseif (!$user->isActivated()) {
            return false;
        } elseif (!$user->isEnabled()) {
            return false;
        }

        if ($countAsAttempt) {
            $user->increaseLoginAttemptCount();
        }

        if ($user->isLocked()) {
            if ($notifyUser) {
                UserLocked::dispatch($user);
            }
            return true;
        }
        return false;
    }

    /**
     * @param string $username
     * @param string $email
     * @return bool
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function sendUnlockEmail(string $username, string $email) : bool
    {
        $user = $this->findUser($username, $email);
        if (!$user) {
            return false;
        } elseif (!$this->isUserLockedOut($user->username, false, false)) {
            return false;
        }

        $this->createUserToken($user, UserTokenType::UNLOCK_ACCOUNT);
        try {
            $user->notify(new UserActionTokenNotification($user, UserTokenType::UNLOCK_ACCOUNT));
            // @codeCoverageIgnoreStart
        } catch (\Exception $e) {
            /** @var PasswordLogManager $logsManager */
            $logsManager = app()->make('passwordLogManager');
            $logsManager->logError(
                $user->getId(),
                \Request()->ip(),
                \Request()->userAgent(),
                $e->getMessage(),
                $e->getTraceAsString()
            );
            return false;
            // @codeCoverageIgnoreEnd
        }
        return true;
    }

    /**
     * @param string $username
     * @param string $email
     * @return bool
     */
    public function unlockUserAccount(string $username, string $email) : bool
    {
        $user = $this->findUser($username, $email);
        if (!$user) {
            return false;
        }
        $user->setLoginAttempts(0);
        return true;
    }

    /**
     * @return int
     */
    public function getUserCount() : int
    {
        return User::count();
    }
}
