<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Services\UserManager;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class ResetPasswordController extends Controller
{
    /** @var string */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * @param string $token
     * @param string $email
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index(string $token, string $email)
    {
        return view(
            'auth.reset',
            [
                'token' => $token,
                'email' => $email
            ]
        );
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function actionReset(Request $request)
    {
        /** @var UserManager $userManager */
        $userManager = app()->make('userManager');

        $email = $request->post('email', '');
        $token = $request->post('token', '');

        if (empty($email) || empty($token)) {
            return redirect(route('login'))->withErrors(__('Email or token are empty'));
        }

        $this->validate(
            $request,
            [
                'password' => [
                    'required',
                    'string',
                    'confirmed',
                    Password::min(14)->letters()->mixedCase()->numbers()->symbols()
                ],
            ]
        );

        if (!$userManager->resetUserPassword($email, $token, $request->post('password', ''))) {
            return redirect(route('login'))->withErrors(__('Could not reset password'));
        }
        return redirect(route('login'))->with('success', __('Password Updated'));
    }
}
