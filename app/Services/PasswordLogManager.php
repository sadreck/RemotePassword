<?php
namespace App\Services;

use App\Models\ErrorLog;
use App\Models\PasswordAccessLog;
use App\Models\PasswordInvalidAccessLog;
use App\Models\RemotePassword;
use App\Models\User;
use App\Models\UserLoginLog;
use App\Services\ReturnTypes\LogSearchParameters;
use App\Services\ReturnTypes\PasswordResult;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PasswordLogManager
{
    /**
     * @param RemotePassword|int $password
     * @param PasswordResult $resultCode
     * @param string $info
     * @param string $ipAddress
     * @param Carbon|null $accessedAt
     * @return void
     */
    public function log(
        RemotePassword|int $password,
        PasswordResult $resultCode,
        string $info = '',
        string $ipAddress = '',
        Carbon|null $accessedAt = null
    ) : void {
        $log = new PasswordAccessLog([
            'password_id' => is_int($password) ? $password : $password->getId(),
            'ip' => empty($ipAddress) ? \Request::ip() : $ipAddress,
            'accessed_at' => empty($accessedAt) ? Carbon::now() : $accessedAt,
            'result' => $resultCode,
            'info' => $info ?? ''
        ]);
        $log->save();
    }

    /**
     * @param string $ipAddress
     * @param string $info
     * @param Carbon|null $accessedAt
     * @return void
     */
    public function logInvalid(string $ipAddress, string $info, Carbon|null $accessedAt = null) : void
    {
        $log = new PasswordInvalidAccessLog([
            'ip' => empty($ipAddress) ? \Request::ip() : $ipAddress,
            'accessed_at' => empty($accessedAt) ? Carbon::now() : $accessedAt,
            'info' => $info
        ]);
        $log->save();
    }

    /**
     * @param int $userId
     * @param string $ipAddress
     * @param string $userAgent
     * @param string $error
     * @param string $details
     * @return void
     */
    public function logError(
        int $userId,
        string $ipAddress,
        string $userAgent,
        string $error,
        string $details
    ) : void {
        $log = new ErrorLog([
            'user_id' => $userId,
            'ip' => $ipAddress,
            'user_agent' => $userAgent,
            'error' => $error,
            'details' => $details
        ]);
        $log->save();
    }

    /**
     * @param int|null $userId
     * @param int|array|null $passwordIds
     * @param PasswordResult|array|null $filterByResult
     * @param string $orderByField
     * @param string $orderByType
     * @param int|null $paginateBy
     * @param int|null $page
     * @param string|null $ipAddress
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @param string|null $timeFrom
     * @param string|null $timeTo
     * @return Collection|LengthAwarePaginator
     */
    protected function queryLogs(
        int $userId = null,
        int|array $passwordIds = null,
        PasswordResult|array $filterByResult = null,
        string $orderByField = 'id',
        string $orderByType = 'desc',
        int $paginateBy = null,
        int $page = null,
        string $ipAddress = null,
        string $dateFrom = null,
        string $dateTo = null,
        string $timeFrom = null,
        string $timeTo = null
    ) : Collection|LengthAwarePaginator {
        $query = PasswordAccessLog::orderBy($orderByField, $orderByType)
            ->select('password_access_logs.*');

        if ($userId !== null && $userId > 0) {
            $query->join('remote_passwords', 'remote_passwords.id', '=', 'password_access_logs.password_id');
            $query->where('remote_passwords.user_id', $userId);
        }

        if ($passwordIds !== null) {
            if (is_int($passwordIds)) {
                $passwordIds = [$passwordIds];
            }

            if (is_array($passwordIds) && count($passwordIds) > 0) {
                $query->whereIn('password_id', $passwordIds);
            }
        }

        if ($filterByResult !== null) {
            if (!is_array($filterByResult)) {
                $filterByResult = [$filterByResult];
            }

            if (is_array($filterByResult) && count($filterByResult) > 0) {
                $query->whereIn('result', $filterByResult);
            }
        }

        if (!empty($ipAddress)) {
            $query->where('ip', $ipAddress);
        }

        $query = $this->setDateTimeFilters($query, $dateFrom, $dateTo, $timeFrom, $timeTo, 'accessed_at');

        if (!empty($paginateBy) && $paginateBy > 0) {
            return $query->paginate($paginateBy, ['*'], 'page', $page);
        }

        return $query->get();
    }

    /**
     * @param string $orderByField
     * @param string $orderByType
     * @param int|null $paginateBy
     * @param int|null $page
     * @param string|null $ipAddress
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @param string|null $timeFrom
     * @param string|null $timeTo
     * @return Collection|LengthAwarePaginator
     */
    protected function queryInvalidLogs(
        string $orderByField = 'id',
        string $orderByType = 'desc',
        int $paginateBy = null,
        int $page = null,
        string $ipAddress = null,
        string $dateFrom = null,
        string $dateTo = null,
        string $timeFrom = null,
        string $timeTo = null
    ) : Collection|LengthAwarePaginator {
        $query = PasswordInvalidAccessLog::orderBy($orderByField, $orderByType);

        if (!empty($ipAddress)) {
            $query->where('ip', $ipAddress);
        }

        $query = $this->setDateTimeFilters($query, $dateFrom, $dateTo, $timeFrom, $timeTo, 'accessed_at');

        if (!empty($paginateBy) && $paginateBy > 0) {
            return $query->paginate($paginateBy, ['*'], 'page', $page);
        }

        return $query->get();
    }

    /**
     * @param string $orderByField
     * @param string $orderByType
     * @param int|null $paginateBy
     * @param int|null $page
     * @param string|null $ipAddress
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @param string|null $timeFrom
     * @param string|null $timeTo
     * @return Collection|LengthAwarePaginator
     */
    protected function queryErrorLogs(
        string $orderByField = 'id',
        string $orderByType = 'desc',
        int $paginateBy = null,
        int $page = null,
        string $ipAddress = null,
        string $dateFrom = null,
        string $dateTo = null,
        string $timeFrom = null,
        string $timeTo = null
    ) : Collection|LengthAwarePaginator {
        $query = ErrorLog::orderBy($orderByField, $orderByType);

        if (!empty($ipAddress)) {
            $query->where('ip', $ipAddress);
        }

        $query = $this->setDateTimeFilters($query, $dateFrom, $dateTo, $timeFrom, $timeTo, 'created_at');

        if (!empty($paginateBy) && $paginateBy > 0) {
            return $query->paginate($paginateBy, ['*'], 'page', $page);
        }

        return $query->get();
    }

    /**
     * @param Builder $query
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @param string|null $timeFrom
     * @param string|null $timeTo
     * @param string $dateField
     * @return Builder
     */
    protected function setDateTimeFilters(
        Builder $query,
        string $dateFrom = null,
        string $dateTo = null,
        string $timeFrom = null,
        string $timeTo = null,
        string $dateField = 'created_at'
    ) : Builder {
        $dateField = in_array($dateField, ['created_at', 'updated_at', 'accessed_at']) ? $dateField : 'created_at';

        if (!empty($dateFrom) || !empty($dateTo) || !empty($timeFrom) || !empty($timeTo)) {
            if (!empty($dateFrom)) {
                $query->where(DB::raw("DATE({$dateField})"), '>=', $dateFrom);
            }

            if (!empty($dateTo)) {
                $query->where(DB::raw("DATE({$dateField})"), '<=', $dateTo);
            }

            if (!empty($timeFrom)) {
                $query->where(DB::raw("TIME({$dateField})"), '>=', $timeFrom . ':00');
            }

            if (!empty($timeTo)) {
                $query->where(DB::raw("TIME({$dateField})"), '<=', $timeTo . ':59');
            }
        }
        return $query;
    }

    /**
     * @param int $userId
     * @param PasswordResult|array $filterByResult
     * @param string $orderByField
     * @param string $orderByType
     * @param int|null $paginateBy
     * @param int|null $page
     * @return Collection|LengthAwarePaginator
     */
    public function getUserPasswordAccessLogs(
        int $userId,
        PasswordResult|array $filterByResult = [],
        string $orderByField = 'id',
        string $orderByType = 'desc',
        int $paginateBy = null,
        int $page = null
    ) : Collection|LengthAwarePaginator {
        return $this->queryLogs(
            $userId,
            null,
            $filterByResult,
            $orderByField,
            $orderByType,
            $paginateBy,
            $page
        );
    }

    /**
     * @param int $passwordId
     * @param PasswordResult|array $filterByResult
     * @param string $orderByField
     * @param string $orderByType
     * @param int|null $paginateBy
     * @param int|null $page
     * @return Collection|LengthAwarePaginator
     */
    public function getPasswordLogs(
        int $passwordId,
        PasswordResult|array $filterByResult = [],
        string $orderByField = 'id',
        string $orderByType = 'desc',
        int $paginateBy = null,
        int $page = null
    ) : Collection|LengthAwarePaginator {
        return $this->queryLogs(
            null,
            $passwordId,
            $filterByResult,
            $orderByField,
            $orderByType,
            $paginateBy,
            $page
        );
    }

    /**
     * @param int $passwordId
     * @return int
     */
    public function getPasswordUses(int $passwordId) : int
    {
        $password = RemotePassword::where('id', $passwordId)->first();
        return $password ? $password->getUses() : 0;
    }

    /**
     * @param LogSearchParameters $parameters
     * @return Collection|LengthAwarePaginator
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function searchErrors(LogSearchParameters $parameters) : Collection|LengthAwarePaginator
    {
        $parameters = $this->validateSearchParameters($parameters);

        $timeFrom = empty($parameters->getTimeFrom())
            ? null
            : $this->convertTimeToTimezone($parameters->getTimeFrom(), $parameters->getUserTimezone(), 'UTC');

        $timeTo = empty($parameters->getTimeTo())
            ? null
            : $this->convertTimeToTimezone($parameters->getTimeTo(), $parameters->getUserTimezone(), 'UTC');

        $result = $this->queryErrorLogs(
            'id',
            'desc',
            $parameters->getPerPage(),
            $parameters->getPage(),
            $parameters->getIpAddress(),
            $parameters->getDateFrom(),
            $parameters->getDateTo(),
            $timeFrom,
            $timeTo
        );

        if (!($result instanceof Collection)) {
            $result->appends($parameters->getCurrentFilters());
        }
        return $result;
    }

    /**
     * @param LogSearchParameters $parameters
     * @return Collection|LengthAwarePaginator
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function searchInvalid(LogSearchParameters $parameters) : Collection|LengthAwarePaginator
    {
        $parameters = $this->validateSearchParameters($parameters);

        $timeFrom = empty($parameters->getTimeFrom())
            ? null
            : $this->convertTimeToTimezone($parameters->getTimeFrom(), $parameters->getUserTimezone(), 'UTC');

        $timeTo = empty($parameters->getTimeTo())
            ? null
            : $this->convertTimeToTimezone($parameters->getTimeTo(), $parameters->getUserTimezone(), 'UTC');

        $result = $this->queryInvalidLogs(
            'id',
            'desc',
            $parameters->getPerPage(),
            $parameters->getPage(),
            $parameters->getIpAddress(),
            $parameters->getDateFrom(),
            $parameters->getDateTo(),
            $timeFrom,
            $timeTo
        );

        if (!($result instanceof Collection)) {
            $result->appends($parameters->getCurrentFilters());
        }
        return $result;
    }

    /**
     * @param LogSearchParameters $parameters
     * @return Collection|LengthAwarePaginator
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function search(LogSearchParameters $parameters) : Collection|LengthAwarePaginator
    {
        $parameters = $this->validateSearchParameters($parameters);

        $timeFrom = empty($parameters->getTimeFrom())
            ? null
            : $this->convertTimeToTimezone($parameters->getTimeFrom(), $parameters->getUserTimezone(), 'UTC');

        $timeTo = empty($parameters->getTimeTo())
            ? null
            : $this->convertTimeToTimezone($parameters->getTimeTo(), $parameters->getUserTimezone(), 'UTC');

        $result = $this->queryLogs(
            $parameters->getUserId(),
            $parameters->getPasswordIds(),
            $parameters->getResults(),
            'id',
            'desc',
            $parameters->getPerPage(),
            $parameters->getPage(),
            $parameters->getIpAddress(),
            $parameters->getDateFrom(),
            $parameters->getDateTo(),
            $timeFrom,
            $timeTo
        );

        if (!($result instanceof Collection)) {
            $result->appends($parameters->getCurrentFilters());
        }
        return $result;
    }

    /**
     * @param Request $request
     * @param int $userId
     * @return LogSearchParameters
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function getSearchParameters(Request $request, int $userId) : LogSearchParameters
    {
        /** @var UserManager $userManager */
        $userManager = app()->make('userManager');

        $params = new LogSearchParameters();

        // User.
        $params->setUserId($userId)->setUserTimezone($userManager->getUser($userId)->getTimezone());

        // Password.
        $passwordIds = $request->get('password', []);
        if (!is_array($passwordIds)) {
            $passwordIds = [];
        }
        $params->setPasswordIds($passwordIds);

        // Access Result.
        $results = $request->get('result', []);
        if (!is_array($results)) {
            $results = [];
        }
        $params->setResults($results);

        // IP Address.
        $params->setIpAddress($request->get('ip', ''));

        // DateTime.
        $fields = [
            'date_from' => 'Y-m-d',
            'date_to' => 'Y-m-d',
            'time_from' => 'H:i',
            'time_to' => 'H:i'
        ];
        $dateData = [];
        foreach ($fields as $name => $format) {
            $value = $request->get($name, '');
            if (!empty($value) && !Carbon::canBeCreatedFromFormat($value, $format)) {
                $value = null;
            }

            $dateData[$name] = $value;
        }

        $params
            ->setDateFrom($dateData['date_from'])
            ->setDateTo($dateData['date_to']);

        // Time From/To.
        $params
            ->setTimeFrom($dateData['time_from'])
            ->setTimeTo($dateData['time_to']);

        // Pagination.
        $params->setPage(
            max(
                (int)$request->get('page', 1),
                1
            )
        );

        $params->setPerPage(
            max(
                (int)$request->get('per_page', 30),
                5
            )
        );

        return $params;
    }

    /**
     * @param string $userTime
     * @param string $userTimezone
     * @param string $newTimezone
     * @return string
     * @throws \Exception
     */
    protected function convertTimeToTimezone(string $userTime, string $userTimezone, string $newTimezone) : string
    {
        $time = new \DateTime($userTime, new \DateTimeZone($userTimezone));
        $time->setTimezone(new \DateTimeZone($newTimezone));
        return $time->format('H:i');
    }

    /**
     * @param LogSearchParameters $parameters
     * @return LogSearchParameters
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function validateSearchParameters(LogSearchParameters $parameters) : LogSearchParameters
    {
        /** @var RemotePasswordManager $passwordManager */
        $passwordManager = app()->make('passwordManager');

        $userId = $parameters->getUserId();

        // Validate passed passwords.
        $passwordIds = array_filter(
            $parameters->getPasswordIds(),
            function ($id) use ($passwordManager, $userId) {
                return $passwordManager->isOwner((int)$id, $userId);
            }
        );
        $parameters->setPasswordIds($passwordIds);

        // Validate passed results.
        $results = array_filter(
            $parameters->getResults(),
            function ($result) {
                if ((int)$result == 0) {
                    // This would return '::NONE' and we don't want that here.
                    return false;
                }
                return PasswordResult::tryFrom((int)$result);
            }
        );

        // Now convert all passed results to the Enum.
        $results = array_map(
            function ($type) {
                return PasswordResult::from((int)$type);
            },
            $results
        );
        $parameters->setResults($results);

        return $parameters;
    }

    /**
     * @return array
     */
    public function getFriendlyPasswordResults() : array
    {
        return [
            PasswordResult::SUCCESS->value => __('Success'),
            PasswordResult::DISABLED->value => __('Disabled'),
            PasswordResult::RESTRICTION_FAILED_IP->value => __('IP Restriction'),
            PasswordResult::RESTRICTION_FAILED_DATE->value => __('Date Restriction'),
            PasswordResult::RESTRICTION_FAILED_TIME->value => __('Time Restriction'),
            PasswordResult::RESTRICTION_FAILED_DAY->value => __('Day Restriction'),
            PasswordResult::RESTRICTION_FAILED_USERAGENT->value => __('User Agent Restriction'),
            PasswordResult::RESTRICTION_FAILED_MAXUSES->value => __('Max Uses Restriction'),
        ];
    }

    /**
     * @param User|int $user
     * @param string $ipAddress
     * @param Carbon|null $accessedAt
     * @return void
     */
    public function logUserLogin(User|int $user, string $ipAddress = '', Carbon|null $accessedAt = null) : void
    {
        $log = new UserLoginLog([
            'user_id' => is_int($user) ? $user : $user->getId(),
            'ip' => empty($ipAddress) ? \Request::ip() : $ipAddress,
            'login_at' => empty($accessedAt) ? Carbon::now() : $accessedAt
        ]);
        $log->save();
    }
}
