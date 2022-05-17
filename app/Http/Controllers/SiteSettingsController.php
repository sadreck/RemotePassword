<?php

namespace App\Http\Controllers;

//use App\Mail\TestEmail;
use App\Notifications\TestEmail;
use App\Services\Core\Settings;
use App\Services\UserManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rules\Password;

class SiteSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'auth.session', 'has.users']);
    }

    /**
     * @param Request $request
     * @param string $view
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function view(Request $request, string $view)
    {
        if (!$this->isValidView($view)) {
            return redirect(route('siteSettings'))->with('error', __('Invalid View'));
        }

        /** @var Settings $settings */
        $settings = app()->make('settings');

        /** @var UserManager $userManager */
        $userManager = app()->make('userManager');

        $users = [];
        switch ($view) {
            case 'users':
                $users = $userManager->all();
                break;
        }

        return view(
            'site.settings.view',
            [
                'view' => $view,
                'viewToInclude' => 'site.settings.includes.view_' . $view,
                'settings' => $settings,
                'users' => $users
            ]
        );
    }

    /**
     * @param Request $request
     * @param string $view
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function save(Request $request, string $view)
    {
        if (!$this->isValidView($view)) {
            return redirect(route('siteSettings'))->with('error', __('Invalid View'));
        }

        return match ($view) {
            'email' => $this->saveEmailSettings($request, $view),
            'general' => $this->saveGeneralSettings($request, $view),
            default => redirect(route('siteSettings', ['view' => $view]))->with('success', 'settings.saved'),
        };
    }

    /**
     * @param Request $request
     * @param string $view
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function saveGeneralSettings(Request $request, string $view)
    {
        /** @var Settings $settings */
        $settings = app()->make('settings');

        $password_email_notifications_enabled = (int)$request->post('password_email_notifications_enabled', 0) == 1;

        $settings->setMultiple(
            [
                'password_email_notifications_enabled' => $password_email_notifications_enabled
            ]
        );

        return redirect(route('siteSettings', ['view' => $view]))->with('success', __('Saved'));
    }

    /**
     * @param Request $request
     * @param string $view
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function saveEmailSettings(Request $request, string $view)
    {
        /** @var Settings $settings */
        $settings = app()->make('settings');

        $enabled = (int)$request->post('enabled', 0) == 1;
        $host = $request->post('host', '');
        $port = (int)$request->post('port', 0);
        $tls = (int)$request->post('tls', 0) == 1;
        $username = $request->post('username', '');
        $password = $request->post('password', '');
        $fromEmail = $request->post('from', '');
        $fromName = $request->post('from_name', '');

        $result = redirect(route('siteSettings', ['view' => $view]));
        if (empty($host)) {
            return $result->with('error', __('No hostname set'));
        } elseif ($port <= 0) {
            return $result->with('error', __('No port set'));
        }

        $settings->setMultiple(
            [
                'email_enabled' => $enabled,
                'email_host' => $host,
                'email_port' => $port,
                'email_tls' => $tls,
                'email_username' => $username,
                'email_password' => $password,
                'email_from' => $fromEmail,
                'email_from_name' => $fromName,
            ]
        );

        return redirect(route('siteSettings', ['view' => $view]))->with('success', __('Saved'));
    }

    /**
     * @param string $view
     * @return bool
     */
    protected function isValidView(string $view) : bool
    {
        return in_array($view, ['general', 'email', 'users']);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Validation\ValidationException
     */
    public function testEmail(Request $request)
    {
        $this->validate(
            $request,
            [
                'recipient' => 'required|email'
            ]
        );

        $to = $request->post('recipient', '');
        if (empty($to)) {
            return redirect(route('siteSettings', ['view' => 'email']))
                ->with('error', __('Recipient cannot be empty'));
        }

        try {
            Notification::route('mail', $to)->notify(new TestEmail());
        } catch (\Exception $e) {
            return redirect(route('siteSettings', ['view' => 'email']))
                ->with('error', __('Could not send email: :message', ['message' => $e->getMessage()]));
        }
        return redirect(route('siteSettings', ['view' => 'email']))
            ->with('success', __('Email sent'));
    }

    /**
     * @param int $userId
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function editUser(int $userId)
    {
        /** @var UserManager $userManager */
        $userManager = app()->make('userManager');

        if ($userId < 0) {
            $userId = 0;
        }

        $user = null;
        if ($userId > 0) {
            $user = $userManager->getUser($userId);
            if (!$user) {
                return redirect(route('siteSettings', ['view' => 'users']))
                    ->with('error', __('User does not exist'));
            }
        }

        return view(
            'site.settings.view',
            [
                'user' => $user,
                'userId' => $userId,
                'view' => 'users',
                'viewToInclude' => 'site.settings.includes.view_users_edit',
            ]
        );
    }

    /**
     * @param Request $request
     * @param int $userId
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function editUserSave(Request $request, int $userId)
    {
        $this->validate(
            $request,
            [
                'username' => ['required', 'string', 'max:255', 'alpha_dash'],
                'email' => ['required', 'string', 'email', 'max:255'],
                'password' => ['nullable', 'string', Password::min(14)->letters()->mixedCase()->numbers()->symbols()]
            ]
        );

        /** @var UserManager $userManager */
        $userManager = app()->make('userManager');

        $username = $request->post('username');
        $email = $request->post('email');
        $password = $request->post('password');
        $enabled = (int)$request->post('enabled', 0) == 1;
        $activated = (int)$request->post('activated', 0) == 1;
        $admin = (int)$request->post('admin', 0) == 1;
        $locked = (int)$request->post('locked', 0) == 1;

        if ($userId > 0) {
            $user = $userManager->getUser($userId);
            if (!$user) {
                return redirect(route('siteSettings', ['view' => 'users]']))
                    ->with('error', __('User does not exist'));
            }

            if (!empty($password)) {
                if (!$userManager->updateUserPassword($user, null, $password, false)) {
                    return redirect(route('siteSettingsUserEdit', ['id' => $user->getId()]))
                        ->with('error', __('Could not update password'))->withInput();
                }
            }
        } else {
            // Re-validate for the password, because we need it here.
            $this->validate(
                $request,
                [
                    'password' => ['string', Password::min(14)->letters()->mixedCase()->numbers()->symbols()]
                ]
            );

            $user = $userManager->createUser($username, $email, $password, $activated, $enabled);
            if (!$user) {
                return redirect(route('siteSettingsUserEdit', ['id' => $userId]))
                    ->with('error', __('Could not create user'))->withInput();
            }
        }

        if (!$userManager->updateUser($user, $username, $email, $enabled, $activated, $admin, $locked)) {
            return redirect(route('siteSettingsUserEdit', ['id' => $user->getId()]))
                ->with('error', __('Could not update user'))->withInput();
        }

        return redirect(route('siteSettings', ['view' => 'users']))
            ->with('success', __('Saved'));
    }
}
