<?php
namespace App\Services;

use App\Events\PasswordAccessed;
use App\Events\PasswordCreated;
use App\Events\PasswordDeleted;
use App\Events\PasswordFailedAccess;
use App\Events\PasswordUpdated;
use App\Models\RemotePassword;
use App\Models\RemotePasswordRestriction;
use App\Services\ReturnTypes\NotificationChannel;
use App\Services\ReturnTypes\PasswordResult;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;
use League\Csv\Writer;

class RemotePasswordManager
{
    /** @var int */
    protected int $tokenLength = 32;

    /**
     * @return int
     */
    public function getTokenLength(): int
    {
        return $this->tokenLength;
    }

    /**
     * @param int $tokenLength
     * @return RemotePasswordManager
     */
    public function setTokenLength(int $tokenLength): RemotePasswordManager
    {
        $this->tokenLength = $tokenLength;
        return $this;
    }

    /**
     * @return PasswordLogManager
     */
    public function getLogManager(): PasswordLogManager
    {
        return $this->logManager;
    }

    /**
     * @param KeyManager $keyManager
     * @param PasswordLogManager $logManager
     */
    public function __construct(
        protected KeyManager $keyManager,
        protected PasswordLogManager $logManager
    ) {
        // Nothing.
    }

    /**
     * @param int $passwordId
     * @param int $userId
     * @return RemotePassword|bool
     */
    public function getPassword(int $passwordId, int $userId = 0) : RemotePassword|bool
    {
        $where = ['id' => $passwordId];
        if ($userId > 0) {
            $where['user_id'] = $userId;
        }
        $password = RemotePassword::where($where)->first();
        return $password ?? false;
    }

    /**
     * @param int $userId
     * @return Collection
     */
    public function getUserPasswords(int $userId) : Collection
    {
        return RemotePassword::where('user_id', $userId)->orderBy('label')->get();
    }

    /**
     * @param string $token
     * @return bool
     */
    protected function tokenExists(string $token) : bool
    {
        return RemotePassword::where('token1', $token)->orWhere('token2', $token)->exists();
    }

    /**
     * @param int $length
     * @return string
     */
    protected function generateRandomString(int $length) : string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $output = [];
        for ($i = 0; $i < $length; $i++) {
            $output[] = $chars[rand(0, strlen($chars) - 1)];
        }
        return implode('', $output);
    }

    /**
     * @param int $length
     * @return string
     */
    protected function generateToken(int $length) : string
    {
        do {
            $token = $this->generateRandomString($length);
        } while ($this->tokenExists($token));
        return $token;
    }

    /**
     * @param int $passwordId
     * @param int $userId
     * @return bool
     */
    public function isOwner(int $passwordId, int $userId) : bool
    {
        return RemotePassword
            ::where('id', $passwordId)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * @param int $passwordId
     * @param int $userId
     * @return bool
     */
    public function deletePassword(int $passwordId, int $userId = 0) : bool
    {
        $password = $this->getPassword($passwordId, $userId);
        $result = false;
        if ($password) {
            $result = $password->delete();
            PasswordDeleted::dispatch($password);
        }
        return $result;
    }

    /**
     * @param int $userId
     * @param string $label
     * @param string $description
     * @param string $data
     * @param string $publicKeyId
     * @param bool $enabled
     * @param string|null $token1
     * @param string|null $token2
     * @param bool $suppressEvents
     * @return RemotePassword|bool
     */
    public function createPassword(
        int $userId,
        string $label,
        string $description,
        string $data,
        string $publicKeyId,
        bool $enabled,
        string $token1 = null,
        string $token2 = null,
        bool $suppressEvents = false
    ) : RemotePassword|bool {
        // Now check if the passed token is the right length.
        if ($token1 != null && strlen($token1) != $this->getTokenLength()) {
            return false;
        } elseif ($token2 != null && strlen($token2) != $this->getTokenLength()) {
            return false;
        }

        $password = new RemotePassword([
            'user_id' => $userId,
            'label' => $label,
            'description' => $description,
            'data' => $data,
            'public_key_id' => $publicKeyId,
            'enabled' => $enabled,
            'token1' => ($token1 != null) ? $token1 : $this->generateToken($this->getTokenLength()),
            'token2' => ($token2 != null) ? $token2 : $this->generateToken($this->getTokenLength()),
        ]);
        $password->save();
        // Set its notifications if required.
        $this->setPasswordDefaultNotifications($password);

        if (!$suppressEvents) {
            PasswordCreated::dispatch($password);
        }
        return $password;
    }

    /**
     * @param RemotePassword $password
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function setPasswordDefaultNotifications(RemotePassword $password) : void
    {
        /** @var ConfigHelper $configHelper */
        $configHelper = app()->make('configHelper');

        $user = $password->getUser();
        $channels = [NotificationChannel::SLACK, NotificationChannel::DISCORD];
        foreach ($channels as $channel) {
            if ($configHelper->isNotificationChannelEnabledByDefault($channel, $user)) {
                $password->setNotifications($channel, true, true, true);
            }
        }
    }

    /**
     * @param int $passwordId
     * @return RemotePasswordRestriction
     */
    public function createEmptyRestriction(int $passwordId) : RemotePasswordRestriction
    {
        return new RemotePasswordRestriction(['password_id' => $passwordId]);
    }

    /**
     * @param int $passwordId
     * @param string $label
     * @param string $description
     * @param string $data
     * @param string $publicKeyId
     * @param bool $enabled
     * @return RemotePassword|bool
     */
    public function updatePassword(
        int $passwordId,
        string $label,
        string $description,
        string $data,
        string $publicKeyId,
        bool $enabled
    ) : RemotePassword|bool {
        $password = $this->getPassword($passwordId);
        if ($password === false) {
            return false;
        }

        $originalPassword = clone $password;

        $password->label = $label;
        $password->description = $description;
        $password->data = $data;
        $password->public_key_id = $publicKeyId;
        $password->enabled = $enabled;
        $password->save();
        PasswordUpdated::dispatch($originalPassword, $password);
        return $password;
    }

    /**
     * @param string $format
     * @return bool
     */
    protected function isValidFormat(string $format) : bool
    {
        return in_array($format, ['raw', 'base64', 'json', 'xml', 'checksum']);
    }

    /**
     * @param string $token1
     * @param string $token2
     * @param string $format
     * @param string $ipAddress
     * @param string $userAgent
     * @param Carbon|null $now
     * @return string|bool
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function retrievePassword(
        string $token1,
        string $token2,
        string $format,
        string $ipAddress = '',
        string $userAgent = '',
        Carbon|null $now = null
    ) : string|bool {
        if ($format === "") {
            $format = 'raw';
        }

        $ipAddress = empty($ipAddress) ? \Request()->ip() : $ipAddress;
        $userAgent = empty($userAgent) ? \Request()->userAgent() : $userAgent;

        if (!$this->isValidFormat($format)) {
            $this->getLogManager()->logInvalid(
                $ipAddress,
                "Reason: Invalid Format" . PHP_EOL .
                "IP Address: {$ipAddress}" . PHP_EOL .
                "User Agent: {$userAgent}" . PHP_EOL .
                "Data: {$token1}/{$token2}/{$format}"
            );
            return false;
        }

        // Find the tokens' first password.
        /** @var RemotePassword $password */
        $password = RemotePassword
            ::where('token1', $token1)
            ->where('token2', $token2)
            ->first();
        if (!$password) {
            $this->getLogManager()->logInvalid(
                $ipAddress,
                "Reason: No Match" . PHP_EOL .
                "IP Address: {$ipAddress}" . PHP_EOL .
                "User Agent: {$userAgent}" . PHP_EOL .
                "Data: {$token1}/{$token2}/{$format}"
            );
            return false;
        }

        // Compare for case-sensitivity.
        if ($password->token1 !== $token1 || $password->token2 !== $token2) {
            $this->getLogManager()->logInvalid(
                $ipAddress,
                "Reason: Case Sensitive" . PHP_EOL .
                "IP Address: {$ipAddress}" . PHP_EOL .
                "User Agent: {$userAgent}" . PHP_EOL .
                "Data: {$token1}/{$token2}/{$format}"
            );
            return false;
        }

        // Now check if the password is enabled. The reason why I don't do it above (query) is for more verbose logging.
        if (!$password->enabled) {
            $this->getLogManager()->log(
                $password,
                PasswordResult::DISABLED,
                "IP Address: {$ipAddress}" . PHP_EOL .
                "User Agent: {$userAgent}",
                $ipAddress
            );
            PasswordFailedAccess::dispatch(
                $password,
                PasswordResult::DISABLED,
                ['ipAddress' => $ipAddress, 'userAgent' => $userAgent]
            );
            return false;
        }

        $accessResult = $password->canAccess(true, $now);
        if ($accessResult !== PasswordResult::SUCCESS) {
            PasswordFailedAccess::dispatch(
                $password,
                $accessResult,
                ['ipAddress' => $ipAddress, 'userAgent' => $userAgent]
            );
            return false;
        }

        $data = '';
        switch ($format) {
            case 'raw':
                $data = $password->data;
                break;
            case 'base64':
                $data = base64_encode($password->data);
                break;
            case 'json':
                $data = json_encode([
                    'password' => $password->data,
                    'checksum' => $password->getChecksum()
                ]);
                break;
            case 'xml':
                $data = [
                    '<?xml version="1.0" encoding="UTF-8"?>',
                    '<rpass>',
                    '<password>'. htmlentities($password->data) .'</password>',
                    '<checksum>'. htmlentities($password->getChecksum()) .'</checksum>',
                    '</rpass>'
                ];
                $data = implode(PHP_EOL, $data);
                break;
            case 'checksum':
                $data = $password->getChecksum();
                break;
        }

        $password->increaseUseCount(true);
        PasswordAccessed::dispatch($password, ['ipAddress' => $ipAddress, 'userAgent' => $userAgent]);
        return $data;
    }

    /**
     * @return array
     */
    public function getImportExportFieldNames() : array
    {
        return [
            __('Label'),
            __('Description'),
            __('Enabled'),
            __('Data'),
            __('Public Key'),
            __('Token 1'),
            __('Token 2'),
        ];
    }

    /**
     * @param array $passwordIds
     * @param int|null $userId
     * @return string
     * @throws \League\Csv\CannotInsertRecord
     * @throws \League\Csv\Exception
     */
    public function exportPasswords(array $passwordIds, int|null $userId) : string
    {
        if (is_int($userId) && $userId > 0) {
            // If a userId is passed, filter out anything they don't own.
            $passwordIds = array_filter(
                $passwordIds,
                function ($id) use ($userId) {
                    return $this->isOwner($id, $userId);
                }
            );
        }

        $csv = Writer::createFromString('');
        $csv->insertOne($this->getImportExportFieldNames());

        foreach ($passwordIds as $passwordId) {
            $password = $this->getPassword($passwordId);
            if (!$password) {
                continue;
            }
            $csv->insertOne([
                $password->label,
                $password->description,
                $password->enabled,
                $password->data,
                $password->public_key_id,
                $password->token1,
                $password->token2,
            ]);
        }

        return $csv->toString();
    }

    /**
     * @param string $csvData
     * @param int $userId
     * @return bool
     * @throws \League\Csv\Exception
     * @throws \League\Csv\UnableToProcessCsv
     */
    public function importPasswords(string $csvData, int $userId) : bool
    {
        $csv = Reader::createFromString($csvData);
        $headers = $csv->fetchOne();
        $result = $this->validateCsvHeaders($this->getImportExportFieldNames(), $headers);
        if ($result !== true) {
            throw new \Exception(__('The following fields are missing: :fields', ['fields' => implode(', ', $result)]));
        }

        $csv->setHeaderOffset(0);
        $passwords = [];
        foreach ($csv as $line) {
            $token1 = $line[__('Token 1')];
            $token2 = $line[__('Token 2')];
            if ($this->tokenExists($token1)) {
                throw new \Exception(__('Token :value already exists', ['value' => $token1]));
            } elseif ($this->tokenExists($token2)) {
                throw new \Exception(__('Token :value already exists', ['value' => $token2]));
            }

            $passwords[] = (object)[
                'label' => $line[__('Label')],
                'description' => $line[__('Description')],
                'enabled' => (int)$line[__('Enabled')] == 1,
                'data' => $line[__('Data')],
                'public_key_id' => $line[__('Public Key')],
                'token1' => $line[__('Token 1')],
                'token2' => $line[__('Token 2')],
            ];
        }

        $newPasswords = $this->bulkCreatePasswords($passwords, $userId);

        return true;
    }

    /**
     * @param array $passwords
     * @param int $userId
     * @return array
     * @throws \Exception
     */
    protected function bulkCreatePasswords(array $passwords, int $userId) : array
    {
        DB::beginTransaction();
        $objects = [];
        foreach ($passwords as $password) {
            $newPassword = $this->createPassword(
                $userId,
                $password->label,
                $password->description,
                $password->data,
                $password->public_key_id,
                $password->enabled,
                $password->token1,
                $password->token2,
                true
            );
            if (!$password) {
                throw new \Exception(__('Could not import password: :name', ['name' => $password->label]));
            }
            $objects[] = $newPassword;
        }
        DB::commit();
        return $objects;
    }

    /**
     * @param array $expectedHeaders
     * @param array $actualHeaders
     * @return array|bool
     */
    protected function validateCsvHeaders(array $expectedHeaders, array $actualHeaders) : array|bool
    {
        $missingHeaders = array_filter(
            $expectedHeaders,
            function ($header) use ($actualHeaders) {
                return !in_array($header, $actualHeaders);
            }
        );
        return count($missingHeaders) == 0 ? true : $missingHeaders;
    }
}
