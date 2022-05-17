<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Services\ReturnTypes\UserTokenType;
use App\Services\UserManager;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    use AuthenticatesUsers {
        attemptLogin as protected parentAttemptLogin;
        login as public parentLogin;
    }

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function username()
    {
        return 'username';
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function showLoginForm()
    {
        return view(
            'auth.login',
            [
                'locked' => request()->has('locked')
            ]
        );
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        /** @var UserManager $userManager */
        $userManager = app()->make('userManager');

        $username = $request->post($this->username(), '');
        if (!empty($username) && $userManager->isUserLockedOut($username, true, true)) {
            return redirect(route('login', ['locked']))->with('error', __('Your account is locked due to a high number of invalid login attempts.'));
        }

        return $this->parentLogin($request);
    }

    /**
     * Get the needed authorization credentials from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function credentials(Request $request)
    {
        $data = $request->only($this->username(), 'password');
        $data['activated'] = true;
        $data['enabled'] = true;
        return $data;
    }

    /**
     * @param Request $request
     * @return bool
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function attemptLogin(Request $request) : bool
    {
        /** @var UserManager $userManager */
        $userManager = app()->make('userManager');

        if ($this->guard()->validate($this->credentials($request))) {
            $username = $request->post($this->username(), '');

            // Credentials are correct, we need to validate the OTP now.
            $validOTP = $userManager->validate2FA(
                $username,
                $request->post('otp', '')
            );
            if (!$validOTP) {
                return false;
            }

            // If we reach this, everything is correct, clear login count.
            $userManager->unlockUserAccount($username, '');
        }
        return $this->parentAttemptLogin($request);
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function unlock()
    {
        return view('auth.unlock');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function sendUnlockEmail(Request $request)
    {
        /** @var UserManager $userManager */
        $userManager = app()->make('userManager');

        $username = $request->post('username', '');
        $email = $request->post('email', '');
        if (empty($username) || empty($email)) {
            return redirect(route('actionSendUnlockEmail'))->withErrors(__('Please fill in both username and email'));
        }

        $userManager->sendUnlockEmail($username, $email);
        return redirect(route('login'))->with('success', __('If your account exists and is currently locked, an email with an unlock link has been dispatched.'));
    }

    /**
     * @param Request $request
     * @param string $token
     * @param string $email
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function unlockAccount(Request $request, string $token, string $email)
    {
        /** @var UserManager $userManager */
        $userManager = app()->make('userManager');

        $activationData = $userManager->getUserTokenData($token, $email, UserTokenType::UNLOCK_ACCOUNT);
        if (is_bool($activationData)) {
            return redirect(route('login'));
        }
        [$user, $activation] = $activationData;

        if (!$userManager->isUserLockedOut($user->username, false, false)) {
            return redirect(route('login'))->with('success', __('Your account has been unlocked.'));
        }

        if (!$userManager->unlockUserAccount($user->username, $user->email)) {
            return redirect(route('login'))->with('error', __('Could not unlock account'));
        }

        return redirect(route('login'))->with('success', __('Your account has been unlocked.'));
    }
}
