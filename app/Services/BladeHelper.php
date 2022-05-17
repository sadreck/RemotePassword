<?php
namespace App\Services;

use App\Models\RemotePassword;
use App\Services\ReturnTypes\PasswordResult;

class BladeHelper
{
    /**
     * @param RemotePassword $password
     * @return string
     */
    public function generateScriptAddCommand(RemotePassword $password) : string
    {
        $command = [
            'rpass',
            'add',
            '--name',
            escapeshellarg($password->label),
            '--token1',
            escapeshellarg($password->token1),
            '--token2',
            escapeshellarg($password->token2),
            '--checksum',
            escapeshellarg($password->getChecksum()),
            '--key',
            escapeshellarg($password->public_key_id)
        ];
        return implode(' ', $command);
    }

    /**
     * @param RemotePassword $password
     * @param string $format
     * @return string
     */
    public function generateDirectAccessUrl(RemotePassword $password, string $format) : string
    {
        return route(
            'accessPasswordGet',
            [
                'token1' => $password->token1,
                'token2' => $password->token2,
                'format' => $format
            ]
        );
    }

    /**
     * @param PasswordResult $result
     * @return string
     */
    public function getFriendlyAccessResult(PasswordResult $result) : string
    {
        $results = [
            PasswordResult::SUCCESS->value => __('Success'),
            PasswordResult::DISABLED->value => __('Disabled'),
            PasswordResult::RESTRICTION_FAILED_IP->value => __('IP Restriction'),
            PasswordResult::RESTRICTION_FAILED_DATE->value => __('Date Restriction'),
            PasswordResult::RESTRICTION_FAILED_TIME->value => __('Time Restriction'),
            PasswordResult::RESTRICTION_FAILED_DAY->value => __('Day Restriction'),
            PasswordResult::RESTRICTION_FAILED_USERAGENT->value => __('User Agent Restriction'),
            PasswordResult::RESTRICTION_FAILED_MAXUSES->value => __('Max Uses Restriction'),
        ];

        return $results[$result->value] ?? '';
    }

    /**
     * @param string $short
     * @return string
     */
    public function getWeekDayName(string $short) : string
    {
        $days = [
            'mon' => 'Monday',
            'tue' => 'Tuesday',
            'wed' => 'Wednesday',
            'thu' => 'Thursday',
            'fri' => 'Friday',
            'sat' => 'Saturday',
            'sun' => 'Sunday'
        ];
        return $days[$short] ?? '';
    }
}
