<?php

namespace App\Models;

use App\Services\Core\UserSettings;
use App\Services\RemotePasswordManager;
use App\Services\ReturnTypes\UserTokenType;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use PragmaRX\Google2FAQRCode\Google2FA;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /** @var UserSettings */
    protected UserSettings $settings;

    /** @var int */
    protected int $lockoutThreshold = 0;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'activated',
        'enabled',
        'otp_secret',
        'otp_backup_codes',
        'admin',
        'login_attempts'
    ];

    /** @var string[] */
    protected $casts = [
        'activated' => 'boolean',
        'enabled' => 'boolean',
        'admin' => 'boolean',
        'login_at' => 'datetime'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password'
    ];

    /**
     * @return int
     */
    public function getLockoutThreshold(): int
    {
        return $this->lockoutThreshold;
    }

    /**
     * @param int $lockoutThreshold
     * @return User
     */
    public function setLockoutThreshold(int $lockoutThreshold): User
    {
        $this->lockoutThreshold = $lockoutThreshold;
        return $this;
    }

    /**
     * @return bool
     */
    public function isLocked() : bool
    {
        return $this->getLoginAttempts() > $this->getLockoutThreshold();
    }

    /**
     * @return bool
     */
    public function isActivated() : bool
    {
        return $this->activated;
    }

    /**
     * @return bool
     */
    public function isEnabled() : bool
    {
        return $this->enabled;
    }

    /**
     * @return int
     */
    public function getId() : int
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function isAdmin() : bool
    {
        return $this->admin ?? false;
    }

    /**
     * @return string
     */
    public function getActivationToken() : string
    {
        return $this->getUserToken(UserTokenType::ACCOUNT_ACTIVATION);
    }

    /**
     * @return string
     */
    public function getPasswordResetToken() : string
    {
        return $this->getUserToken(UserTokenType::PASSWORD_RESET);
    }

    public function getUnlockToken() : string
    {
        return $this->getUserToken(UserTokenType::UNLOCK_ACCOUNT);
    }

    /**
     * @param UserTokenType $type
     * @return string
     */
    protected function getUserToken(UserTokenType $type) : string
    {
        $token = UserToken
            ::where('user_id', $this->getId())
            ->where('active', true)
            ->where('used', false)
            ->where('type', $type)
            ->first();
        return $token ? $token->token : '';
    }

    /**
     * @return string
     */
    public function getTimezone() : string
    {
        $timezone = $this->settings()->get('timezone', null);
        return empty($timezone) ? 'UTC' : $timezone;
    }

    /**
     * @return string
     */
    public function getDateTimeFormat() : string
    {
        $format = $this->settings()->get('dateformat', null);
        return empty($format) ? 'Y-m-d H:i:s' : $format;
    }

    /**
     * @return string
     */
    public function getDateTimenow() : string
    {
        return Carbon::now()->setTimezone($this->getTimezone())->format($this->getDateTimeFormat());
    }

    /**
     * @return string
     */
    public function getOTPSecret() : string
    {
        return $this->otp_secret;
    }

    /**
     * @return string
     * @throws \PragmaRX\Google2FAQRCode\Exceptions\MissingQrCodeServiceException
     */
    public function get2FAQRImage() : string
    {
        $qr2fa = new Google2FA();
        return $qr2fa->getQRCodeInline(
            config('app.name'),
            $this->username,
            $this->getOTPSecret()
        );
    }

    /**
     * @return array
     */
    public function get2FABackupCodes() : array
    {
        return json_decode($this->otp_backup_codes);
    }

    /**
     * @param string $code
     * @return bool
     */
    public function isValid2FABackupCode(string $code) : bool
    {
        return in_array($code, $this->get2FABackupCodes());
    }

    /**
     * @param string $code
     * @return bool
     */
    public function use2FABackupCode(string $code) : bool
    {
        $codes = $this->get2FABackupCodes();
        $index = array_search($code, $codes);
        if ($index !== false) {
            unset($codes[$index]);
            $codes = array_values($codes);
        }
        return $this->save2FABackupCodes($codes);
    }

    /**
     * @param array $codes
     * @return bool
     */
    public function save2FABackupCodes(array $codes) : bool
    {
        $this->otp_backup_codes = json_encode($codes);
        return $this->save();
    }

    /**
     * @param string $code
     * @return bool
     */
    public function isValid2FACode(string $code) : bool
    {
        try {
            /** @var \PragmaRX\Google2FA\Google2FA $google2fa */
            $google2fa = app('pragmarx.google2fa');
            return $google2fa->verify($code, $this->getOTPSecret());
            // @codeCoverageIgnoreStart
        } catch (\Exception $e) {
            return false;
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * @param string $code
     * @return bool
     */
    public function saveLastUsedOTP(string $code) : bool
    {
        $this->settings()->set('otp_last_used', $code);
        return $this->save();
    }

    /**
     * @return string
     */
    public function getLastUsedOTP() : string
    {
        return $this->settings()->get('otp_last_used', '');
    }

    /**
     * @return void
     */
    public function increaseLoginAttemptCount() : void
    {
        $count = $this->settings()->get('login_attempts', 0, 'int');
        $this->settings()->set('login_attempts', ++$count);
    }

    /**
     * @param int $count
     * @return void
     */
    public function setLoginAttempts(int $count) : void
    {
        $this->settings()->set('login_attempts', $count);
    }

    /**
     * @return int
     */
    public function getLoginAttempts() : int
    {
        return $this->settings()->get('login_attempts', 0, 'int');
    }

    /**
     * @return UserSettings
     */
    public function settings() : UserSettings
    {
        if (!isset($this->settings)) {
            $this->settings = new UserSettings($this->getId());
        }
        return $this->settings;
    }

    /**
     * @return string
     */
    public function getGlobalSlackWebhook() : string
    {
        return $this->settings()->get('slack_webhook_url', '');
    }

    /**
     * @return string
     */
    public function getGlobalDiscordWebhook() : string
    {
        return $this->settings()->get('discord_webhook_url', '');
    }

    /**
     * @param string $timezone
     * @param string $dateTimeFormat
     * @return string
     */
    public function getLastLogin(string $timezone = 'UTC', string $dateTimeFormat = 'Y-m-d H:i:s') : string
    {
        $timezone = empty($timezone) ? 'UTC' : $timezone;
        $dateTimeFormat = empty($dateTimeFormat) ? 'Y-m-d H:i:s' : $dateTimeFormat;
        $lastLoginLog = UserLoginLog
            ::where('user_id', $this->getId())
            ->orderBy('id', 'desc')
            ->limit(1)
            ->first();
        return $lastLoginLog
            ? $lastLoginLog->login_at->setTimezone($timezone)->format($dateTimeFormat)
            : '';
    }

    /**
     * @return bool
     */
    public function getDefaultSlackState() : bool
    {
        return $this->settings()->get('slack_default_enabled', false, 'bool');
    }

    /**
     * @return bool
     */
    public function getDefaultDiscordState() : bool
    {
        return $this->settings()->get('discord_default_enabled', false, 'bool');
    }

    /**
     * @return Collection
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function getPasswords() : Collection
    {
        /** @var RemotePasswordManager $passwordManager */
        $passwordManager = app()->make('passwordManager');
        return $passwordManager->getUserPasswords($this->getId());
    }
}
