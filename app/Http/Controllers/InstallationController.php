<?php

namespace App\Http\Controllers;

use App\Services\UserManager;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class InstallationController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(['no.users']);
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        return view('site.install');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function save(Request $request)
    {
        $this->validate(
            $request,
            [
                'username' => ['required', 'string', 'max:255', 'unique:users', 'alpha_dash'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => [
                    'required',
                    'string',
                    Password::min(14)->letters()->mixedCase()->numbers()->symbols()
                ],
            ]
        );

        $username = $request->post('username');
        $email = $request->post('email');
        $password = $request->post('password');

        /** @var UserManager $userManager */
        $userManager = app()->make('userManager');
        $user = $userManager->createUser($username, $email, $password, false, true);
        if ($user) {
            /*
             * No functionality to automatically create an admin. You first have to create a user and
             * then make them an admin.
             */
            $user = $userManager->updateUser($user, $username, $email, true, false, true, false);
            if ($user) {
                /*
                 * Now they need to self-activate in order to create the OTP.
                 * This function will most likely return false because there are no email settings yet,
                 * but we don't care about that because we will automatically redirect the user to their
                 * activation page.
                 */
                $userManager->sendActivationToken($username, $email);
                return redirect(route('activateAccount', ['token' => $user->getActivationToken(), 'email' => $email]));
            }
        }
        return redirect(route('firstRun'))->with('error', __('Could not create administrator'))->withInput();
    }
}
