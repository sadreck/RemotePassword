<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Services\ReturnTypes\UserTokenType;
use App\Services\UserManager;
use Illuminate\Foundation\Auth\VerifiesEmails;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:10,1');
    }

    /**
     * @param Request $request
     * @param string $token
     * @param string $email
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function index(Request $request, string $token, string $email)
    {
        /** @var UserManager $userManager */
        $userManager = app()->make('userManager');

        $activationData = $userManager->getUserTokenData($token, $email, UserTokenType::ACCOUNT_ACTIVATION);
        if (is_bool($activationData)) {
            return redirect(route('login'));
        }
        [$user, $activation] = $activationData;

        return view(
            'auth.register_2fa',
            [
                'action' => 'activate',
                'token' => $token,
                'email' => $email,
                'qrCodeImage' => $user->get2FAQRImage(),
                'otpSecret' => $user->getOTPSecret()
            ]
        );
    }

    /**
     * @param Request $request
     * @param string $token
     * @param string $email
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function activate(Request $request, string $token, string $email)
    {
        /** @var UserManager $userManager */
        $userManager = app()->make('userManager');

        $activationData = $userManager->getUserTokenData($token, $email, UserTokenType::ACCOUNT_ACTIVATION);
        if (is_bool($activationData)) {
            return redirect(route('login'));
        }
        [$user, $activation] = $activationData;

        $code = $request->post('otp', '');
        if (!$user->isValid2FACode($code)) {
            return redirect(route('activateAccount', ['token' => $token, 'email' => $email]))
                ->with('error', __('Invalid OTP'));
        }

        if (!$userManager->activate($token, $email)) {
            return redirect(route('activateAccount', ['token' => $token, 'email' => $email]))
                ->with('error', __('Could not activate your account'));
        }

        return view(
            'auth.register_2fa',
            [
                'action' => 'backupcodes',
                'backupCodes' => $user->get2FABackupCodes()
            ]
        );
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function resendIndex(Request $request)
    {
        return view('auth.resend_activation');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function resendAction(Request $request)
    {
        /** @var UserManager $userManager */
        $userManager = app()->make('userManager');

        $username = $request->post('username', '');
        $email = $request->post('email', '');
        if (empty($username) || empty($email)) {
            return redirect(route('resendAccountActivation'))->withErrors(__('Please fill in both username and email'));
        }

        $userManager->sendActivationToken($username, $email);
        return redirect(route('login'))->with('success', __('If your account exists and has not been activated already, an email with an activation link has been dispatched.'));
    }
}
