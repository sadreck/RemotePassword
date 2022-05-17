<?php
namespace App\Models;

class RestrictionItem
{
    /** @var string */
    protected string $timezone = '';

    /** @var array */
    protected array $dates;

    /** @var array */
    protected array $times;

    /** @var array */
    protected array $weekDays;

    /** @var int */
    protected int $maxUses;

    /** @var array */
    protected array $userAgents;

    /** @var array */
    protected array $ipAddresses;

    public function __construct()
    {
        $this->reset();
    }

    public function reset()
    {
        $this->timezone = '';
        $this->dates = [];
        $this->times = [];
        $this->weekDays = [];
        $this->maxUses = 0;
        $this->userAgents = [];
        $this->ipAddresses = [];
    }

    /**
     * @return string
     */
    public function getTimezone(): string
    {
        return $this->timezone;
    }

    /**
     * @param string $timezone
     * @return RestrictionItem
     */
    public function setTimezone(string $timezone): RestrictionItem
    {
        $this->timezone = $timezone;
        return $this;
    }

    /**
     * @return array
     */
    public function getDates() : array
    {
        return $this->dates;
    }

    /**
     * @param array $dates
     * @return $this
     */
    public function setDates(array $dates) : RestrictionItem
    {
        $this->dates = $dates;
        return $this;
    }

    /**
     * @return array
     */
    public function getTimes(): array
    {
        return $this->times;
    }

    /**
     * @param array $times
     * @return RestrictionItem
     */
    public function setTimes(array $times): RestrictionItem
    {
        $this->times = $times;
        return $this;
    }

    /**
     * @return array
     */
    public function getWeekDays(): array
    {
        return $this->weekDays;
    }

    /**
     * @param array $weekDays
     * @return RestrictionItem
     */
    public function setWeekDays(array $weekDays): RestrictionItem
    {
        $this->weekDays = $weekDays;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxUses(): int
    {
        return $this->maxUses;
    }

    /**
     * @param int $maxUses
     * @return RestrictionItem
     */
    public function setMaxUses(int $maxUses): RestrictionItem
    {
        $this->maxUses = $maxUses;
        return $this;
    }

    /**
     * @return array
     */
    public function getUserAgents(): array
    {
        return $this->userAgents;
    }

    /**
     * @param array $userAgents
     * @return RestrictionItem
     */
    public function setUserAgents(array $userAgents): RestrictionItem
    {
        $this->userAgents = $userAgents;
        return $this;
    }

    /**
     * @return array
     */
    public function getIpAddresses(): array
    {
        return $this->ipAddresses;
    }

    /**
     * @param array $ipAddresses
     * @return RestrictionItem
     */
    public function setIpAddresses(array $ipAddresses): RestrictionItem
    {
        $this->ipAddresses = $ipAddresses;
        return $this;
    }
}
