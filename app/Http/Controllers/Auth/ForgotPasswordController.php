<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\UserManager;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;

class ForgotPasswordController extends Controller
{
//    use SendsPasswordResetEmails;
    public function index()
    {
        return view('auth.forgot');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function forgotAction(Request $request)
    {
        /** @var UserManager $userManager */
        $userManager = app()->make('userManager');

        $username = $request->post('username', '');
        $email = $request->post('email', '');
        if (empty($username) || empty($email)) {
            return redirect(route('resendAccountActivation'))->withErrors(__('Email or token are empty'));
        }

        $userManager->sendPasswordResetToken($username, $email);
        return redirect(route('login'))->with('success', __('If your account exists an email with a reset password link has been dispatched.'));
    }
}
