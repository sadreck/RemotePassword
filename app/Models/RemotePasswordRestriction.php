<?php

namespace App\Models;

use App\Services\PasswordLogManager;
use App\Services\ReturnTypes\PasswordResult;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\IpUtils;

class RemotePasswordRestriction extends Model
{
    use HasFactory;

    /** @var string */
    protected $table = 'remote_password_restrictions';

    /** @var string[] */
    protected $fillable = [
        'password_id'
    ];

    /** @var RestrictionItem */
    protected RestrictionItem $restrictions;

    /**
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->loadRestrictions();
    }

    /**
     * @return int
     */
    public function getId() : int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getPasswordId() : int
    {
        return $this->password_id;
    }

    /**
     * @return bool
     */
    public function hasIpRestrictions() : bool
    {
        return count($this->restrictions->getIpAddresses()) > 0;
    }

    /**
     * @return bool
     */
    public function hasDateRestrictions() : bool
    {
        return count($this->restrictions->getDates()) > 0;
    }

    /**
     * @return bool
     */
    public function hasTimeRestrictions() : bool
    {
        return count($this->restrictions->getTimes()) > 0;
    }

    /**
     * @return bool
     */
    public function hasDayRestrictions() : bool
    {
        return count($this->restrictions->getWeekDays()) > 0;
    }

    /**
     * @return bool
     */
    public function hasUserAgentRestrictions() : bool
    {
        return count($this->restrictions->getUserAgents()) > 0;
    }

    /**
     * @return bool
     */
    public function hasMaxUsageRestrictions() : bool
    {
        return $this->restrictions->getMaxUses() > 0;
    }

    /**
     * @return int
     */
    public function getMaxUses() : int
    {
        return $this->restrictions->getMaxUses();
    }

    /**
     * @param array $data
     * @return string
     */
    protected function getTimeDateString(array $data) : string
    {
        $output = [];
        foreach ($data as $element) {
            $item = '';
            if (!empty($element->from) && !empty($element->to)) {
                if ($element->from == $element->to) {
                    $item = $element->from;
                } else {
                    $item = "{$element->from} to {$element->to}";
                }
            } elseif (!empty($element->from)) {
                $item = "from {$element->from}";
            } elseif (!empty($element->to)) {
                $item = "to {$element->to}";
            }
            $output[] = $item;
        }
        return implode(PHP_EOL, $output);
    }

    /**
     * @return string
     */
    public function getDatesString() : string
    {
        return $this->getTimeDateString($this->restrictions->getDates());
    }

    /**
     * @return string
     */
    public function getTimeString() : string
    {
        return $this->getTimeDateString($this->restrictions->getTimes());
    }

    /**
     * @return string
     */
    public function getUserAgentString() : string
    {
        return implode(PHP_EOL, $this->restrictions->getUserAgents());
    }

    /**
     * @return string
     */
    public function getIPString() : string
    {
        return implode(PHP_EOL, $this->restrictions->getIpAddresses());
    }

    /**
     * @return array
     */
    public function getWeekdays() : array
    {
        return $this->restrictions->getWeekDays();
    }

    /**
     * @param string $timezone
     * @return bool
     */
    public function setTimezone(string $timezone) : bool
    {
        if (in_array($timezone, timezone_identifiers_list())) {
            $this->restrictions->setTimezone($timezone);
            return true;
        }
        return false;
    }

    /**
     * @param string $input
     * @return bool
     */
    public function addHumanFriendlyDateRange(string $input) : bool
    {
        /*
         * 2022-03-01               ^([0-9]{4}-[0-9]{2}-[0-9]{2})$ /gmi
         * 2022-03-01 to 2022-03-10 ^([0-9]{4}-[0-9]{2}-[0-9]{2})\s+(to)\s+([0-9]{4}-[0-9]{2}-[0-9]{2})$ /gmi
         * from 2022-03-01          ^(from)\s+([0-9]{4}-[0-9]{2}-[0-9]{2})$ /gmi
         * to 2022-03-10            ^(to)\s+([0-9]{4}-[0-9]{2}-[0-9]{2})$ /gmi
         */

        $dateRegularExpression = "[0-9]{4}-[0-9]{2}-[0-9]{2}";
        return $this->addHumanFriendlyDateTimeRange($input, $dateRegularExpression, 'addDateRange');
    }

    /**
     * @param string $input
     * @return bool
     */
    public function addHumanFriendlyTimeRange(string $input) : bool
    {
        /*
         * 14:30            ^([0-9]{2}:[0-9]{2})$
         * 14:30 to 16:20   ^([0-9]{2}:[0-9]{2})\s+(to)\s+([0-9]{2}:[0-9]{2})$
         * from 16:30       ^(from)\s+([0-9]{2}:[0-9]{2})$
         * to 18:00         ^(to)\s+([0-9]{2}:[0-9]{2})$
         */

        $timeRegularExpression = "[0-9]{2}:[0-9]{2}";
        return $this->addHumanFriendlyDateTimeRange($input, $timeRegularExpression, 'addTimeRange');
    }

    /**
     * @param string $input
     * @param string $formatRegEx
     * @param string $functionName
     * @return bool
     */
    protected function addHumanFriendlyDateTimeRange(string $input, string $formatRegEx, string $functionName) : bool
    {
        $regularExpressions = (object)[
            'date' => "^({$formatRegEx})$",
            'range' => "^({$formatRegEx})\s+(to)\s+({$formatRegEx})$",
            'from' => "^(from)\s+({$formatRegEx})$",
            'to' => "^(to)\s+({$formatRegEx})$"
        ];
        $input = trim(strtolower($input));
        $from = $to = null;
        if (preg_match("/{$regularExpressions->date}/i", $input, $matches)) {
            $from = $to = $matches[1];
        } elseif (preg_match("/{$regularExpressions->range}/i", $input, $matches)) {
            $from = $matches[1];
            $to = $matches[3];
        } elseif (preg_match("/{$regularExpressions->from}/i", $input, $matches)) {
            $from = $matches[2];
        } elseif (preg_match("/{$regularExpressions->to}/i", $input, $matches)) {
            $to = $matches[2];
        }
        return $this->{$functionName}($from, $to);
    }

    /**
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @return bool
     */
    public function addDateRange(string|null $dateFrom, string|null $dateTo) : bool
    {
        // We need to make both checks before trying to set any of the 2 values.
        if (!empty($dateFrom) && !Carbon::canBeCreatedFromFormat($dateFrom, 'Y-m-d')) {
            return false;
        } elseif (!empty($dateTo) && !Carbon::canBeCreatedFromFormat($dateTo, 'Y-m-d')) {
            return false;
        }

        if (empty($dateFrom) && empty($dateTo)) {
            return false;
        }

        if (empty($dateFrom)) {
            $dateFrom = null;
        }

        if (empty($dateTo)) {
            $dateTo = null;
        }

        if ($this->dateRangeExists($dateFrom, $dateTo)) {
            return true;
        }
        $dates = $this->restrictions->getDates();
        $dates[] = (object)[
            'from' => $dateFrom,
            'to' => $dateTo
        ];
        $this->restrictions->setDates($dates);
        return true;
    }

    /**
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @return bool
     */
    protected function dateRangeExists(string|null $dateFrom, string|null $dateTo) : bool
    {
        $dates = $this->restrictions->getDates();
        $alreadyExists = false;
        foreach ($dates as $date) {
            if ($date->from == $dateFrom && $date->to == $dateTo) {
                $alreadyExists = true;
                break;
            }
        }
        return $alreadyExists;
    }

    /**
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @return bool
     */
    public function removeDateRange(string|null $dateFrom, string|null $dateTo) : bool
    {
        $dates = $this->restrictions->getDates();
        $index = false;
        foreach ($dates as $i => $date) {
            if ($date->from == $dateFrom && $date->to == $dateTo) {
                $index = $i;
                break;
            }
        }

        if ($index === false) {
            return false;
        }
        unset($dates[$index]);
        $this->restrictions->setDates(array_values($dates));
        return true;
    }

    /**
     * @return bool
     */
    public function clearDateRanges() : bool
    {
        $this->restrictions->setDates([]);
        return true;
    }

    /**
     * @param string|null $timeFrom
     * @param string|null $timeTo
     * @return bool
     */
    public function addTimeRange(string|null $timeFrom, string|null $timeTo) : bool
    {
        // We need to make both checks before trying to set any of the 2 values.
        if (!empty($timeFrom) && !Carbon::canBeCreatedFromFormat($timeFrom, 'H:i')) {
            return false;
        } elseif (!empty($timeTo) && !Carbon::canBeCreatedFromFormat($timeTo, 'H:i')) {
            return false;
        }

        if (empty($timeFrom) && empty($timeTo)) {
            return false;
        }

        if (empty($timeFrom)) {
            $timeFrom = null;
        }

        if (empty($timeTo)) {
            $timeTo = null;
        }

        if ($this->timeRangeExists($timeFrom, $timeTo)) {
            return true;
        }
        $times = $this->restrictions->getTimes();
        $times[] = (object)[
            'from' => $timeFrom,
            'to' => $timeTo
        ];
        $this->restrictions->setTimes($times);
        return true;
    }

    /**
     * @param string|null $timeFrom
     * @param string|null $timeTo
     * @return bool
     */
    protected function timeRangeExists(string|null $timeFrom, string|null $timeTo) : bool
    {
        $times = $this->restrictions->getTimes();
        $alreadyExists = false;
        foreach ($times as $time) {
            if ($time->from == $timeFrom && $time->to == $timeTo) {
                $alreadyExists = true;
                break;
            }
        }
        return $alreadyExists;
    }

    /**
     * @param string|null $timeFrom
     * @param string|null $timeTo
     * @return bool
     */
    public function removeTimeRange(string|null $timeFrom, string|null $timeTo) : bool
    {
        $times = $this->restrictions->getTimes();
        $index = false;
        foreach ($times as $i => $time) {
            if ($time->from == $timeFrom && $time->to == $timeTo) {
                $index = $i;
                break;
            }
        }

        if ($index === false) {
            return false;
        }
        unset($times[$index]);
        $this->restrictions->setTimes(array_values($times));
        return true;
    }

    /**
     * @return bool
     */
    public function clearTimeRanges() : bool
    {
        $this->restrictions->setTimes([]);
        return true;
    }

    /**
     * @param array $days
     * @return bool
     */
    public function setWeekDays(array $days) : bool
    {
        $validDays = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
        $allValid = true;
        foreach ($days as $day) {
            if (!in_array(strtolower($day), $validDays)) {
                $allValid = false;
                break;
            }
        }

        if (!$allValid) {
            return false;
        }
        $this->restrictions->setWeekDays(array_unique($days));
        return true;
    }

    /**
     * @param int $count
     * @return bool
     */
    public function setMaxUses(int $count) : bool
    {
        if ($count < 0) {
            return false;
        }
        $this->restrictions->setMaxUses($count);
        return true;
    }

    /**
     * @param array $userAgents
     * @return bool
     */
    public function setUserAgents(array $userAgents) : bool
    {
        $this->restrictions->setUserAgents($userAgents);
        return true;
    }

    /**
     * @param string $userAgent
     * @return bool
     */
    public function addUserAgent(string $userAgent) : bool
    {
        $userAgents = $this->restrictions->getUserAgents();
        if (!in_array($userAgent, $userAgents)) {
            $userAgents[] = $userAgent;
        }
        $this->restrictions->setUserAgents($userAgents);
        return true;
    }

    /**
     * @param string $userAgent
     * @return bool
     */
    public function removeUserAgent(string $userAgent) : bool
    {
        $userAgents = $this->restrictions->getUserAgents();
        $index = array_search($userAgent, $userAgents);
        if ($index === false) {
            return false;
        }
        unset($userAgents[$index]);
        $this->restrictions->setUserAgents($userAgents);
        return true;
    }

    /**
     * @return bool
     */
    public function clearUserAgents() : bool
    {
        $this->restrictions->setUserAgents([]);
        return true;
    }

    /**
     * @return bool
     */
    public function clearIpRestrictions() : bool
    {
        $this->restrictions->setIpAddresses([]);
        return true;
    }

    /**
     * @param string $ipAddressOrRange
     * @return bool
     */
    public function addIpAddressOrRange(string $ipAddressOrRange) : bool
    {
        if (!$this->isValidIpAddressOrRange($ipAddressOrRange)) {
            return false;
        }
        $ipAddresses = $this->restrictions->getIpAddresses();
        if (in_array($ipAddressOrRange, $ipAddresses)) {
            return true;
        }
        $ipAddresses[] = $ipAddressOrRange;
        $this->restrictions->setIpAddresses($ipAddresses);
        return true;
    }

    /**
     * @param string $ipAddressOrRange
     * @return bool
     */
    public function removeIpAddressOrRange(string $ipAddressOrRange) : bool
    {
        $ipAddresses = $this->restrictions->getIpAddresses();
        $index = array_search($ipAddressOrRange, $ipAddresses);
        if ($index === false) {
            return false;
        }
        unset($ipAddresses[$index]);
        $this->restrictions->setIpAddresses(array_values($ipAddresses));
        return true;
    }

    /**
     * @param string $ipAddressOrRange
     * @return bool
     */
    protected function isValidIpAddressOrRange(string $ipAddressOrRange) : bool
    {
        if (str_contains($ipAddressOrRange, '/')) {
            return $this->validateIPRange($ipAddressOrRange);
        }
        return $this->validateIP($ipAddressOrRange);
    }

    /**
     * @param string $ip
     * @return bool
     */
    protected function validateIP(string $ip) : bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP);
    }

    /**
     * @param string $ipRange
     * @return bool
     */
    protected function validateIPRange(string $ipRange) : bool
    {
        $data = explode('/', $ipRange);
        if (count($data) != 2) {
            return false;
        }

        $ip = $data[0];
        $subnet = $data[1];
        if (!$this->validateIP($ip)) {
            return false;
        }

        if (!ctype_digit($subnet)) {
            return false;
        }
        $subnet = (int)$subnet;
        return $subnet >= 0 && $subnet <= 32;
    }

    /**
     * @return void
     */
    public function loadRestrictions() : void
    {
        $this->restrictions = new RestrictionItem();
        if (empty($this->data)) {
            return;
        }
        $data = json_decode($this->data);
        if (!($data instanceof \stdClass)) {
            // @codeCoverageIgnoreStart
            return;
            // @codeCoverageIgnoreEnd
        }

        if (property_exists($data, 'timezone') && is_string($data->timezone)) {
            $this->setTimezone($data->timezone);
        }

        if (property_exists($data, 'dates') && is_array($data->dates)) {
            foreach ($data->dates as $date) {
                $this->addDateRange($date->from, $date->to);
            }
        }

        if (property_exists($data, 'times') && is_array($data->times)) {
            foreach ($data->times as $time) {
                $this->addTimeRange($time->from, $time->to);
            }
        }

        if (property_exists($data, 'weekDays') && is_array($data->weekDays)) {
            $this->setWeekDays($data->weekDays);
        }

        if (property_exists($data, 'maxUses') && is_int($data->maxUses)) {
            $this->setMaxUses($data->maxUses);
        }

        if (property_exists($data, 'userAgents') && is_array($data->userAgents)) {
            $this->setUserAgents($data->userAgents);
        }

        if (property_exists($data, 'ipAddresses') && is_array($data->ipAddresses)) {
            foreach ($data->ipAddresses as $ipAddress) {
                $this->addIpAddressOrRange($ipAddress);
            }
        }
    }

    /**
     * @param array $options
     * @return bool
     */
    public function save(array $options = [])
    {
        if (isset($this->restrictions)) {
            $data = [
                'timezone' => $this->restrictions->getTimezone(),
                'dates' => array_values($this->restrictions->getDates()),
                'times' => array_values($this->restrictions->getTimes()),
                'weekDays' => array_values($this->restrictions->getWeekDays()),
                'maxUses' => $this->restrictions->getMaxUses(),
                'userAgents' => array_values($this->restrictions->getUserAgents()),
                'ipAddresses' => array_values($this->restrictions->getIpAddresses())
            ];
            $this->data = json_encode($data);
        }
        return parent::save($options);
    }

    /**
     * @return string
     */
    public function getTimezone() : string
    {
        $timezone = $this->restrictions->getTimezone();
        if (empty($timezone)) {
            $remotePassword = RemotePassword::find($this->getPasswordId());
            if ($remotePassword) {
                $user = User::find($remotePassword->user_id);
                if ($user) {
                    $timezone = $user->timezone;
                }
            }
        }

        // If it's still empty, return the default.
        if (empty($timezone)) {
            $timezone = config('app.timezone', 'UTC');
        }
        return $timezone;
    }

    /**
     * @param string|null $ipAddress
     * @param string|null $userAgent
     * @param Carbon|null $now
     * @param bool $log
     * @return PasswordResult
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function evaluate(
        string $ipAddress = null,
        string $userAgent = null,
        Carbon|null $now = null,
        bool $log = true,
    ) : PasswordResult {
        /** @var PasswordLogManager $logManager */
        $logManager = app()->make('passwordLogManager');
        $ipAddress = $ipAddress ?? request()->ip();
        $userAgent = $userAgent ?? request()->userAgent();

        // To make sure we evaluate the times right, we need to check the timezone first. If the password does not
        // have a timezone, we'll use the user's. If the user doesn't have one, we'll use the default.
        $now = $now ?? Carbon::now();
        $nowTimezone = $now->copy()->setTimezone($this->getTimezone());

        if (!$this->evaluateIP($ipAddress)) {
            if ($log) {
                $logManager->log(
                    $this->getPasswordId(),
                    PasswordResult::RESTRICTION_FAILED_IP,
                    "IP Address: {$ipAddress}" . PHP_EOL .
                    "IP Restrictions: " . implode(", ", $this->restrictions->getIpAddresses()),
                    $ipAddress,
                    $now
                );
            }
            return PasswordResult::RESTRICTION_FAILED_IP;
        }

        if (!$this->evaluateDates($now)) {
            if ($log) {
                $logManager->log(
                    $this->getPasswordId(),
                    PasswordResult::RESTRICTION_FAILED_DATE,
                    "Current DateTime: " . $nowTimezone->toDateTimeString() . PHP_EOL .
                    "Date Restrictions: " . $this->getDatesString(),
                    $ipAddress,
                    $now
                );
            }
            return PasswordResult::RESTRICTION_FAILED_DATE;
        }

        if (!$this->evaluateTimes($now)) {
            if ($log) {
                $logManager->log(
                    $this->getPasswordId(),
                    PasswordResult::RESTRICTION_FAILED_TIME,
                    "Current DateTime: " . $nowTimezone->toDateTimeString() . PHP_EOL .
                    "Time Restrictions: " . $this->getTimeString(),
                    $ipAddress,
                    $now
                );
            }
            return PasswordResult::RESTRICTION_FAILED_TIME;
        }

        if (!is_null($this->restrictions->getWeekDays()) && count($this->restrictions->getWeekDays()) > 0) {
            if (!in_array(strtolower($now->format('D')), $this->restrictions->getWeekDays())) {
                if ($log) {
                    $logManager->log(
                        $this->getPasswordId(),
                        PasswordResult::RESTRICTION_FAILED_DAY,
                        "Current DateTime: " . $nowTimezone->toDateTimeString() . PHP_EOL .
                        "Day Restrictions: " . implode(", ", $this->restrictions->getWeekDays()),
                        $ipAddress,
                        $now
                    );
                }
                return PasswordResult::RESTRICTION_FAILED_DAY;
            }
        }

        if (!empty($this->restrictions->getUserAgents()) && count($this->restrictions->getUserAgents()) > 0) {
            if (!in_array($userAgent, $this->restrictions->getUserAgents())) {
                if ($log) {
                    $logManager->log(
                        $this->getPasswordId(),
                        PasswordResult::RESTRICTION_FAILED_USERAGENT,
                        "Current UserAgent: " . $userAgent . PHP_EOL .
                        "UserAgent Restrictions: " . implode(', ', $this->restrictions->getUserAgents()),
                        $ipAddress,
                        $now
                    );
                }
                return PasswordResult::RESTRICTION_FAILED_USERAGENT;
            }
        }

        if (!is_null($this->restrictions->getMaxUses()) && $this->restrictions->getMaxUses() > 0) {
            if ($logManager->getPasswordUses($this->getPasswordId()) >= $this->restrictions->getMaxUses()) {
                if ($log) {
                    $logManager->log(
                        $this->getPasswordId(),
                        PasswordResult::RESTRICTION_FAILED_MAXUSES,
                        "Current Uses: " . $logManager->getPasswordUses($this->getPasswordId()) . PHP_EOL .
                        "Max Use Restrictions: " . $this->restrictions->getMaxUses(),
                        $ipAddress,
                        $now
                    );
                }
                return PasswordResult::RESTRICTION_FAILED_MAXUSES;
            }
        }
        return PasswordResult::SUCCESS;
    }

    /**
     * @param string $userIPAddress
     * @return bool
     */
    protected function evaluateIP(string $userIPAddress) : bool
    {
        if (!is_array($this->restrictions->getIpAddresses()) || count($this->restrictions->getIpAddresses()) == 0) {
            return true;
        }

        $allow = false;
        foreach ($this->restrictions->getIpAddresses() as $ipAddressOrRange) {
            if (!$this->isValidIpAddressOrRange($ipAddressOrRange)) {
                // @codeCoverageIgnoreStart
                continue;
                // @codeCoverageIgnoreEnd
            }

            if (IpUtils::checkIp($userIPAddress, $ipAddressOrRange)) {
                $allow = true;
                break;
            }
        }

        return $allow;
    }

    /**
     * @param Carbon $now
     * @return bool
     */
    protected function evaluateDates(Carbon $now) : bool
    {
        if (!is_array($this->restrictions->getDates()) || count($this->restrictions->getDates()) == 0) {
            return true;
        }

        $now = strtotime($now->toDateString());
        return $this->compareDateTimeRanges($this->restrictions->getDates(), $now);
    }

    /**
     * @param Carbon $now
     * @return bool
     */
    protected function evaluateTimes(Carbon $now) : bool
    {
        if (!is_array($this->restrictions->getTimes()) || count($this->restrictions->getTimes()) == 0) {
            return true;
        }

        $now = strtotime($now->toTimeString('minute'));
        return $this->compareDateTimeRanges($this->restrictions->getTimes(), $now);
    }

    /**
     * @param array $ranges
     * @param int $now
     * @return bool
     */
    protected function compareDateTimeRanges(array $ranges, int $now) : bool
    {
        $inRange = false;
        foreach ($ranges as $range) {
            $from = strtotime($range->from);
            $to = strtotime($range->to);

            if (!empty($from) && !empty($to)) {
                if ($now >= $from && $now <= $to) {
                    $inRange = true;
                    break;
                }
            } elseif (!empty($from)) {
                if ($now >= $from) {
                    $inRange = true;
                    break;
                }
            } elseif (!empty($to)) {
                if ($now <= $to) {
                    $inRange = true;
                    break;
                }
            }
        }
        return $inRange;
    }
}
