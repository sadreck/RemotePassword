<?php

namespace App\Http\Controllers;

use App\Events\PasswordUpdated;
use App\Services\ConfigHelper;
use App\Services\KeyManager;
use App\Services\PasswordLogManager;
use App\Services\RemotePasswordManager;
use App\Services\ReturnTypes\NotificationChannel;
use App\Services\UserManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PasswordController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'auth.session', 'has.users']);
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function index()
    {
        /** @var RemotePasswordManager $passwordManager */
        $passwordManager = app()->make('passwordManager');

        return view(
            'manage.passwords.index',
            [
                'remotePasswords' => $passwordManager->getUserPasswords(Auth::id())
            ]
        );
    }

    /**
     * @param int $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function editPassword(int $id)
    {
        /** @var RemotePasswordManager $passwordManager */
        $passwordManager = app()->make('passwordManager');
        if ($id > 0 && !$passwordManager->isOwner($id, Auth::id())) {
            return redirect(route('home'))->with('error', __('Access Denied'));
        }

        /** @var KeyManager $keyManager */
        $keyManager = app()->make('keyManager');

        return view(
            'manage.passwords.edit',
            [
                'passwordId' => $id,
                'remotePassword' => $passwordManager->getPassword($id, Auth::id()),
                'publicKeys' => $keyManager->getUserKeys(Auth::id())
            ]
        );
    }

    /**
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function editPasswordSave(Request $request, int $id)
    {
        /** @var RemotePasswordManager $passwordManager */
        $passwordManager = app()->make('passwordManager');
        if ($id > 0 && !$passwordManager->isOwner($id, Auth::id())) {
            return redirect(route('home'))->with('error', __('Access Denied'));
        }

        $label = trim($request->post('label', ''));
        $description = trim($request->post('description', ''));
        $data = trim($request->post('data', ''));
        $enabled = (int)$request->post('enabled', 0) == 1;
        $publicKeyId = trim($request->post('public_key_id', ''));

        if ($label == "") {
            return redirect(route('managePasswordsEdit', ['id' => $id]))
                ->withErrors(['label' => __('Label cannot be empty')])
                ->withInput();
        } elseif ($data == "") {
            return redirect(route('managePasswordsEdit', ['id' => $id]))
                ->withErrors(['data' => __('Data cannot be empty')])
                ->withInput();
        } elseif ($publicKeyId == "") {
            return redirect(route('managePasswordsEdit', ['id' => $id]))
                ->withErrors(['data' => __('The Public Key ID cannot be empty - this will help you identify which key was used to encrypt the password.')])
                ->withInput();
        }

        $result = $id > 0
            ? $passwordManager->updatePassword($id, $label, $description, $data, $publicKeyId, $enabled)
            : $passwordManager->createPassword(Auth::id(), $label, $description, $data, $publicKeyId, $enabled);

        if (!$result) {
            return redirect(route('managePasswordsEdit', ['id' => $id]))
                ->with('error', __('Could not save password'))
                ->withInput();
        }

        return redirect(route('managePasswords'))->with('success', __('Saved'));
    }

    /**
     * @param int $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function deletePassword(int $id)
    {
        /** @var RemotePasswordManager $passwordManager */
        $passwordManager = app()->make('passwordManager');
        if (!$passwordManager->isOwner($id, Auth::id())) {
            return redirect(route('home'))->with('error', __('Access Denied'));
        }

        if (!$passwordManager->deletePassword($id, Auth::id())) {
            return redirect(route('managePasswords'))->with('error', __('Could not delete password'));
        }

        return redirect(route('managePasswords'))->with('success', __('Password Deleted'));
    }

    /**
     * @param int $id
     * @param string $view
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function view(int $id, string $view)
    {
        if (!$this->isValidView($view)) {
            return redirect(route('managePasswords'))->with('error', __('Invalid View'));
        }

        /** @var ConfigHelper $configHelper */
        $configHelper = app()->make('configHelper');

        /** @var RemotePasswordManager $passwordManager */
        $passwordManager = app()->make('passwordManager');
        if (!$passwordManager->isOwner($id, Auth::id())) {
            return redirect(route('home'))->with('error', __('Access Denied'));
        }

        $password = $passwordManager->getPassword($id, Auth::id());

        /** @var UserManager $userManager */
        $userManager = app()->make('userManager');

        $logs = null;
        $notifications = new \stdClass();
        switch ($view) {
            case 'logs':
                /** @var PasswordLogManager $logsManager */
                $logsManager = app()->make('passwordLogManager');

                $logs = $logsManager->getPasswordLogs($id, paginateBy: 10);
                break;
            case 'notifications':
                if (!$configHelper->hasAnyNotificationChannels(Auth::user())) {
                    return redirect(route('home'))->with('error', __('There are no enabled notification channels'));
                }

                $channels = [
                    'Email' => NotificationChannel::EMAIL,
                    'Slack' => NotificationChannel::SLACK,
                    'Discord' => NotificationChannel::DISCORD
                ];

                foreach ($channels as $label => $channel) {
                    if (!$configHelper->isNotificationChannelEnabled($channel, Auth::user())) {
                        continue;
                    }

                    $notifications->{$channel->value} = (object)[
                        'label' => $label,
                        'settings' => (object)[
                            'enabled' => (object)[
                                'label' => __('Enabled'),
                                'value' => $password->isNotificationChannelEnabled($channel)
                            ],
                            'onsuccess' => (object)[
                                'label' => __('On Success'),
                                'value' => $password->hasSuccessNotifications($channel)
                            ],
                            'onerror' => (object)[
                                'label' => __('On Error'),
                                'value' => $password->hasErrorNotifications($channel)
                            ],
                        ]
                    ];
                }
                break;
        }

        return view(
            'manage.passwords.view',
            [
                'user' => $userManager->getUser(Auth::id()),
                'password' => $password,
                'view' => $view,
                'viewToInclude' => 'manage.passwords.includes.view_' . $view,
                'logs' => $logs,
                'notifications' => $notifications,
                'hasNotificationChannels' => $configHelper->hasAnyNotificationChannels(Auth::user())
            ]
        );
    }

    /**
     * @param string $view
     * @return bool
     */
    protected function isValidView(string $view) : bool
    {
        return in_array($view, ['details', 'access', 'logs', 'restrictions', 'notifications']);
    }

    /**
     * @param int $id
     * @param int $restrictionId
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function editRestriction(int $id, int $restrictionId)
    {
        /** @var ConfigHelper $configHelper */
        $configHelper = app()->make('configHelper');

        /** @var RemotePasswordManager $passwordManager */
        $passwordManager = app()->make('passwordManager');
        if (!$passwordManager->isOwner($id, Auth::id())) {
            return redirect(route('home'))->with('error', __('Access Denied'));
        }

        $password = $passwordManager->getPassword($id, Auth::id());
        $restriction = $password->getRestrictionById($restrictionId);
        if ($restrictionId > 0 && $restriction === false) {
            return redirect(route('home'))->with('error', __('Access Denied'));
        }

        return view(
            'manage.passwords.view',
            [
                'password' => $password,
                'view' => 'restrictions',
                'viewToInclude' => 'manage.passwords.includes.view_restrictions_edit',
                'restriction' => $restriction,
                'restrictionId' => $restrictionId,
                'hasNotificationChannels' => $configHelper->hasAnyNotificationChannels(Auth::user())
            ]
        );
    }

    /**
     * @param Request $request
     * @param int $id
     * @param int $restrictionId
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function saveRestrictions(Request $request, int $id, int $restrictionId)
    {
        /** @var RemotePasswordManager $passwordManager */
        $passwordManager = app()->make('passwordManager');
        if (!$passwordManager->isOwner($id, Auth::id())) {
            return redirect(route('home'))->with('error', __('Access Denied'));
        }

        $password = $passwordManager->getPassword($id, Auth::id());
        $restriction = $password->getRestrictionById($restrictionId);
        if ($restrictionId > 0 && $restriction === false) {
            return redirect(route('home'))->with('error', __('Access Denied'));
        }

        if ($restrictionId == 0) {
            $restriction = $passwordManager->createEmptyRestriction($password->getId());
        }

        $allowedIPs = $this->toArrayAndClean(trim($request->post('allowed_ips', '')));
        $allowedDates = $this->toArrayAndClean(trim($request->post('allowed_dates', '')));
        $allowedTimes = $this->toArrayAndClean(trim($request->post('allowed_times', '')));
        $allowedDays = $this->cleanArray($request->post('allowed_days', []));
        $allowedUserAgents = $this->toArrayAndClean(trim($request->post('allowed_useragent', '')));
        $allowedMaxUses = (int)$request->post('allowed_maxuses', 0);

        if (count($allowedIPs) == 0 && count($allowedDates) == 0 && count($allowedTimes) == 0
            && count($allowedDays) == 0 && count($allowedUserAgents) == 0 && $allowedMaxUses  <= 0
        ) {
            return redirect(
                route(
                    'managePasswordRestrictionEdit',
                    ['id' => $password->getId(), 'restrictionId' => $restrictionId]
                )
            )->with('error', __('No restrictions set'))->withInput();
        }

        $restriction->clearIpRestrictions();
        foreach ($allowedIPs as $ipOrRange) {
            if (!$restriction->addIpAddressOrRange($ipOrRange)) {
                return redirect(
                    route(
                        'managePasswordRestrictionEdit',
                        ['id' => $password->getId(), 'restrictionId' => $restrictionId]
                    )
                )->with('error', __('Invalid IP address or range'))->withInput();
            }
        }

        $restriction->clearDateRanges();
        foreach ($allowedDates as $allowedDate) {
            if (!$restriction->addHumanFriendlyDateRange($allowedDate)) {
                return redirect(
                    route(
                        'managePasswordRestrictionEdit',
                        ['id' => $password->getId(), 'restrictionId' => $restrictionId]
                    )
                )->with('error', __('Invalid date or range'))->withInput();
            }
        }

        $restriction->clearTimeRanges();
        foreach ($allowedTimes as $allowedTime) {
            if (!$restriction->addHumanFriendlyTimeRange($allowedTime)) {
                return redirect(
                    route(
                        'managePasswordRestrictionEdit',
                        ['id' => $password->getId(), 'restrictionId' => $restrictionId]
                    )
                )->with('error', __('Invalid time or range'))->withInput();
            }
        }

        $restriction->setWeekDays([]);
        if (!$restriction->setWeekDays($allowedDays)) {
            return redirect(
                route(
                    'managePasswordRestrictionEdit',
                    ['id' => $password->getId(), 'restrictionId' => $restrictionId]
                )
            )->with('error', __('Invalid days'))->withInput();
        }

        $restriction->clearUserAgents();
        foreach ($allowedUserAgents as $allowedUserAgent) {
            if (!$restriction->addUserAgent($allowedUserAgent)) {
                return redirect(
                    route(
                        'managePasswordRestrictionEdit',
                        ['id' => $password->getId(), 'restrictionId' => $restrictionId]
                    )
                )->with('error', __('Invalid user agent'))->withInput();
            }
        }

        if (!$restriction->setMaxUses($allowedMaxUses)) {
            if (!$restriction->setMaxUses($allowedMaxUses)) {
                return redirect(
                    route(
                        'managePasswordRestrictionEdit',
                        ['id' => $password->getId(), 'restrictionId' => $restrictionId]
                    )
                )->with('error', __('Invalid max uses'))->withInput();
            }
        }

        // Now save.
        $restriction->save();
        PasswordUpdated::dispatch($password, $password);

        return redirect(
            route('managePassword', ['id' => $password->getId(), 'view' => 'restrictions'])
        )->with('success', __('Saved'));
    }

    /**
     * @param string $value
     * @param string $separator
     * @return array
     */
    protected function toArrayAndClean(string $value, string $separator = PHP_EOL) : array
    {
        return $this->cleanArray(explode($separator, $value));
    }

    /**
     * @param array $value
     * @return array
     */
    protected function cleanArray(array $value) : array
    {
        $value = array_map(
            function ($v) {
                return trim($v);
            },
            $value
        );
        return array_filter(
            $value,
            function ($v) {
                return !empty($v);
            }
        );
    }

    /**
     * @param int $id
     * @param int $restrictionId
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function deleteRestrictions(int $id, int $restrictionId)
    {
        /** @var RemotePasswordManager $passwordManager */
        $passwordManager = app()->make('passwordManager');
        if (!$passwordManager->isOwner($id, Auth::id())) {
            return redirect(route('home'))->with('error', __('Access Denied'));
        }

        $password = $passwordManager->getPassword($id, Auth::id());
        $restriction = $password->getRestrictionById($restrictionId);
        if ($restriction === false) {
            return redirect(route('home'))->with('error', __('Access Denied'));
        }

        if (!$password->deleteRestrictionById($restriction->getId())) {
            return redirect(
                route(
                    'managePassword',
                    ['id' => $password->getId(), 'view' => 'restrictions']
                )
            )->with('error', __('Could not delete restriction'));
        }

        PasswordUpdated::dispatch($password, $password);

        return redirect(
            route(
                'managePassword',
                ['id' => $password->getId(), 'view' => 'restrictions']
            )
        )->with('success', __('Restriction Deleted'));
    }

    /**
     * @param int $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function resetUseCount(int $id)
    {
        /** @var RemotePasswordManager $passwordManager */
        $passwordManager = app()->make('passwordManager');
        if (!$passwordManager->isOwner($id, Auth::id())) {
            return redirect(route('home'))->with('error', __('Access Denied'));
        }

        $password = $passwordManager->getPassword($id, Auth::id());
        $password->resetUseCount(true);

        return redirect(
            route('managePassword', ['id' => $password->getId(), 'view' => 'details'])
        )->with('success', __('Usage has been reset'));
    }

    /**
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function saveNotifications(Request $request, int $id)
    {
        /** @var ConfigHelper $configHelper */
        $configHelper = app()->make('configHelper');
        if (!$configHelper->hasAnyNotificationChannels(Auth::user())) {
            return redirect(route('home'))->with('error', __('There are no enabled notification channels'));
        }

        /** @var RemotePasswordManager $passwordManager */
        $passwordManager = app()->make('passwordManager');
        if (!$passwordManager->isOwner($id, Auth::id())) {
            return redirect(route('home'))->with('error', __('Access Denied'));
        }

        $password = $passwordManager->getPassword($id, Auth::id());

        $properties = ['enabled', 'onsuccess', 'onerror'];

        $channels = [
            NotificationChannel::EMAIL->value => [],
            NotificationChannel::SLACK->value => [],
            NotificationChannel::DISCORD->value => [],
        ];

        // Got the values.
        foreach ($channels as $channel => $data) {
            foreach ($properties as $property) {
                $channels[$channel][$property] = (int)$request->post("{$channel}_{$property}", 0) == 1;
            }
        }

        // Now set the values.
        foreach ($channels as $channel => $properties) {
            if (!$configHelper->isNotificationChannelEnabled(NotificationChannel::from($channel), Auth::user())) {
                continue;
            }
            $password->setNotifications(
                NotificationChannel::from($channel),
                $properties['enabled'],
                $properties['onsuccess'],
                $properties['onerror']
            );
        }

        return redirect(
            route('managePassword', ['id' => $id, 'view' => 'notifications'])
        )->with('success', __('Saved'));
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function export()
    {
        /** @var RemotePasswordManager $passwordManager */
        $passwordManager = app()->make('passwordManager');

        return view(
            'manage.passwords.export',
            [
                'passwords' => $passwordManager->getUserPasswords(Auth::id())
            ]
        );
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Routing\Redirector|mixed
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \League\Csv\CannotInsertRecord
     * @throws \League\Csv\Exception
     */
    public function exportRun(Request $request)
    {
        /** @var RemotePasswordManager $passwordManager */
        $passwordManager = app()->make('passwordManager');

        $ids = $request->post('id', []);
        if (!is_array($ids)) {
            $ids = [];
        }

        if (count($ids) == 0) {
            return redirect(route('managePasswordsExport'))->with('error', __('No items selected for export'));
        }

        $csvData = $passwordManager->exportPasswords($ids, Auth::id());
        $downloadAs = "rpass_export_" . Carbon::now()->toDateString() . ".csv";

        return response()->make(
            $csvData,
            200,
            [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="'. $downloadAs .'"'
            ]
        );
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function import()
    {
        /** @var RemotePasswordManager $passwordManager */
        $passwordManager = app()->make('passwordManager');

        return view(
            'manage.passwords.import',
            [
                'fieldNames' => implode(', ', $passwordManager->getImportExportFieldNames())
            ]
        );
    }

    public function importRun(Request $request)
    {
        /** @var RemotePasswordManager $passwordManager */
        $passwordManager = app()->make('passwordManager');

        $csvFile = $request->file('csv');
        if (!$csvFile) {
            return redirect(route('managePasswordsImport'))->with('error', __('No CSV file uploaded'));
        }
        $csvData = $csvFile->get();
        if (empty($csvData)) {
            return redirect(route('managePasswordsImport'))->with('error', __('No CSV file uploaded'));
        }

        try {
            $passwordManager->importPasswords($csvData, Auth::id());
        } catch (\Exception $e) {
            return redirect(route('managePasswordsImport'))->with('error', $e->getMessage());
        }

        return redirect(route('managePasswords'))->with('success', __('Import complete'));
    }
}
