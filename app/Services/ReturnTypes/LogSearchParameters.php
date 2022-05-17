<?php
namespace App\Services\ReturnTypes;

class LogSearchParameters
{
    /** @var array */
    protected array $passwordIds = [];

    /** @var int */
    protected int $userId = 0;

    /** @var array */
    protected array $results = [];

    /** @var string|null */
    protected string|null $ipAddress = '';

    /** @var string|null */
    protected string|null $dateFrom = '';

    /** @var string|null */
    protected string|null $dateTo = '';

    /** @var string|null */
    protected string|null $timeFrom = '';

    /** @var string|null */
    protected string|null $timeTo = '';

    /** @var string  */
    protected string $userTimezone = 'UTC';

    /** @var int */
    protected int $page = 1;

    /** @var int */
    protected int $perPage = 30;

    /**
     * @return array
     */
    public function getCurrentFilters() : array
    {
        return [
            'password' => $this->getPasswordIds(),
            'result' => array_map(
                function ($result) {
                    return $result->value;
                },
                $this->getResults()
            ),
            'ip' => $this->getIpAddress(),
            'date_from' => $this->getDateFrom(),
            'date_to' => $this->getDateTo(),
            'time_from' => $this->getTimeFrom(),
            'time_to' => $this->getTimeTo()
        ];
    }

    /**
     * @return $this
     */
    public function clear() : LogSearchParameters
    {
        $this
            ->setPasswordIds([])
            ->setUserId(0)
            ->setResults([])
            ->setIpAddress('')
            ->setDateFrom('')
            ->setDateTo('')
            ->setTimeFrom('')
            ->setTimeTo('')
            ->setUserTimezone('UTC')
            ->setPage(1)
            ->setPerPage(30);
        return $this;
    }

    /**
     * @return array
     */
    public function getPasswordIds(): array
    {
        return $this->passwordIds;
    }

    /**
     * @param array $passwordId
     * @return LogSearchParameters
     */
    public function setPasswordIds(array $passwordIds): LogSearchParameters
    {
        $this->passwordIds = $passwordIds;
        return $this;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return LogSearchParameters
     */
    public function setUserId(int $userId): LogSearchParameters
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * @return array
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * @param array $results
     * @return $this
     */
    public function setResults(array $results): LogSearchParameters
    {
        $this->results = $results;
        return $this;
    }

    /**
     * @param int $type
     * @return bool
     */
    public function inResults(int $type) : bool
    {
        $type = PasswordResult::tryFrom($type);
        if (!$type) {
            return false;
        }
        return in_array($type, $this->results);
    }

    /**
     * @return string|null
     */
    public function getIpAddress(): string|null
    {
        return $this->ipAddress;
    }

    /**
     * @param string|null $ipAddress
     * @return LogSearchParameters
     */
    public function setIpAddress(string|null $ipAddress): LogSearchParameters
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDateFrom(): ?string
    {
        return $this->dateFrom;
    }

    /**
     * @param string|null $dateFrom
     * @return LogSearchParameters
     */
    public function setDateFrom(?string $dateFrom): LogSearchParameters
    {
        $this->dateFrom = $dateFrom;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDateTo(): ?string
    {
        return $this->dateTo;
    }

    /**
     * @param string|null $dateTo
     * @return LogSearchParameters
     */
    public function setDateTo(?string $dateTo): LogSearchParameters
    {
        $this->dateTo = $dateTo;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTimeFrom(): ?string
    {
        return $this->timeFrom;
    }

    /**
     * @param string|null $timeFrom
     * @return LogSearchParameters
     */
    public function setTimeFrom(?string $timeFrom): LogSearchParameters
    {
        $this->timeFrom = $timeFrom;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTimeTo(): ?string
    {
        return $this->timeTo;
    }

    /**
     * @param string|null $timeTo
     * @return LogSearchParameters
     */
    public function setTimeTo(?string $timeTo): LogSearchParameters
    {
        $this->timeTo = $timeTo;
        return $this;
    }

    /**
     * @return string
     */
    public function getUserTimezone(): string
    {
        return $this->userTimezone;
    }

    /**
     * @param string $userTimezone
     * @return LogSearchParameters
     */
    public function setUserTimezone(string $userTimezone): LogSearchParameters
    {
        $this->userTimezone = $userTimezone;
        return $this;
    }

    /**
     * @return int
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * @param int $page
     * @return LogSearchParameters
     */
    public function setPage(int $page): LogSearchParameters
    {
        $this->page = $page;
        return $this;
    }

    /**
     * @return int
     */
    public function getPerPage(): int
    {
        return $this->perPage;
    }

    /**
     * @param int $perPage
     * @return LogSearchParameters
     */
    public function setPerPage(int $perPage): LogSearchParameters
    {
        $this->perPage = $perPage;
        return $this;
    }
}
