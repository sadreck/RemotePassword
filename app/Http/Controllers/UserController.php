<?php

namespace App\Http\Controllers;

use App\Rules\WebHookDiscord;
use App\Rules\WebHookSlack;
use App\Services\UserManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'auth.session', 'has.users']);
    }

    /**
     * @param string $view
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function view(string $view)
    {
        if (!$this->isValidView($view)) {
            return redirect(route('managePasswords'))->with('error', __('Invalid View'));
        }

        /** @var UserManager $userManager */
        $userManager = app()->make('userManager');

        return view(
            'user.view',
            [
                'user' => $userManager->getUser(Auth::id()),
                'view' => $view,
                'viewToInclude' => 'user.includes.view_' . $view,
                'timezones' => timezone_identifiers_list()
            ]
        );
    }

    /**
     * @param string $view
     * @return bool
     */
    protected function isValidView(string $view) : bool
    {
        return in_array($view, ['profile', 'password', 'notifications']);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function saveProfile(Request $request)
    {
        $timezone = $request->post('timezone', '');
        $datetimeFormat = $request->post('datetime_format', '');
        if (empty($timezone)) {
            $timezone = 'UTC';
        }

        if (empty($datetimeFormat)) {
            $datetimeFormat = 'Y-m-d H:i:s';
        }

        /** @var UserManager $userManager */
        $userManager = app()->make('userManager');

        if (!$userManager->setTimezone(Auth::id(), $timezone)) {
            return redirect(route('userAccount', ['view' => 'profile']))
                ->with('error', __('Invalid timezone'))
                ->withInput();
        }

        if (!$userManager->setDateTimeFormat(Auth::id(), $datetimeFormat)) {
            return redirect(route('userAccount', ['view' => 'profile']))
                ->with('error', __('Invalid Date/Time Format'))
                ->withInput();
        }

        return redirect(route('userAccount', ['view' => 'profile']))
            ->with('success', __('Saved'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function changePassword(Request $request)
    {
        $existingPassword = $request->post('password', '');
        $newPassword = $request->post('new_password', '');
        $confirmPassword = $request->post('confirm_password', '');

        /** @var UserManager $userManager */
        $userManager = app()->make('userManager');

        if (empty($existingPassword) || empty($newPassword) || empty($confirmPassword)) {
            return redirect(route('userAccount', ['view' => 'password']))
                ->with('error', __('Please fill in all the fields'));
        } elseif ($newPassword != $confirmPassword) {
            return redirect(route('userAccount', ['view' => 'password']))
                ->with('error', __('validation.confirmed', ['attribute' => 'password']));
        } elseif (!$userManager->validatePassword($newPassword)) {
            return redirect(route('userAccount', ['view' => 'password']))
                ->with('error', __('New password does not meet password complexity requirements'));
        }

        if (!$userManager->updateUserPassword(Auth::id(), $existingPassword, $newPassword, true)) {
            return redirect(route('userAccount', ['view' => 'password']))
                ->with('error', __('Could not update password'));
        }

        // If I don't refresh the user object the logoutOtherDevices won't work with the new password.
        Auth::login($userManager->getUser(Auth::id()));
        Auth::logoutOtherDevices($newPassword);
        return redirect(route('userAccount', ['view' => 'password']))
            ->with('success', __('Password Updated'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function saveNotifications(Request $request)
    {
        $this->validate(
            $request,
            [
                'slack_webhook_url' => ['nullable', 'url', new WebHookSlack],
                'discord_webhook_url' => ['nullable', 'url', new WebHookDiscord],
            ]
        );

        /** @var UserManager $userManager */
        $userManager = app()->make('userManager');

        $slackWebhookUrl = $request->post('slack_webhook_url', '');
        $discordWebhookUrl = $request->post('discord_webhook_url', '');
        $slackEnableByDefault = (int)$request->post('slack_default_enabled', 0) == 1;
        $discordEnableByDefault = (int)$request->post('discord_default_enabled', 0) == 1;

        $user = $userManager->getUser(Auth::id());
        $user->settings()->setMultiple([
            'slack_webhook_url' => $slackWebhookUrl,
            'discord_webhook_url' => $discordWebhookUrl,
            'slack_default_enabled' => $slackEnableByDefault,
            'discord_default_enabled' => $discordEnableByDefault
        ]);

        return redirect(route('userAccount', ['view' => 'notifications']))
            ->with('success', __('Saved'));
    }
}
