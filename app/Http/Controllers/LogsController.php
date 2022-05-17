<?php

namespace App\Http\Controllers;

use App\Services\PasswordLogManager;
use App\Services\RemotePasswordManager;
use App\Services\UserManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'auth.session', 'has.users']);
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function index(Request $request)
    {
        /** @var PasswordLogManager $logsManager */
        $logsManager = app()->make('passwordLogManager');

        /** @var UserManager $userManager */
        $userManager = app()->make('userManager');

        /** @var RemotePasswordManager $passwordManager */
        $passwordManager = app()->make('passwordManager');

        $searchParameters = $logsManager->getSearchParameters($request, Auth::id());
        $logs = $logsManager->search($searchParameters);

        $passwords = $passwordManager->getUserPasswords(Auth::id());

        return view(
            'logs.index',
            [
                'logs' => $logs,
                'user' => $userManager->getUser(Auth::id()),
                'passwords' => $passwords,
                'search' => $searchParameters,
                'passwordResultsList' => $logsManager->getFriendlyPasswordResults()
            ]
        );
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function invalid(Request $request)
    {
        /** @var PasswordLogManager $logsManager */
        $logsManager = app()->make('passwordLogManager');

        /** @var UserManager $userManager */
        $userManager = app()->make('userManager');

        $searchParameters = $logsManager->getSearchParameters($request, Auth::id());
        $logs = $logsManager->searchInvalid($searchParameters);

        return view(
            'logs.invalid',
            [
                'logs' => $logs,
                'user' => $userManager->getUser(Auth::id()),
                'search' => $searchParameters
            ]
        );
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function errors(Request $request)
    {
        /** @var PasswordLogManager $logsManager */
        $logsManager = app()->make('passwordLogManager');

        /** @var UserManager $userManager */
        $userManager = app()->make('userManager');

        $searchParameters = $logsManager->getSearchParameters($request, Auth::id());
        $logs = $logsManager->searchErrors($searchParameters);

        return view(
            'logs.errors',
            [
                'logs' => $logs,
                'user' => $userManager->getUser(Auth::id()),
                'search' => $searchParameters
            ]
        );
    }
}
