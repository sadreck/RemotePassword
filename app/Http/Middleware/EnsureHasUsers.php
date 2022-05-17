<?php

namespace App\Http\Middleware;

use App\Services\UserManager;
use Closure;
use Illuminate\Http\Request;

class EnsureHasUsers
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        /** @var UserManager $userManager */
        $userManager = app()->make('userManager');
        if ($userManager->getUserCount() == 0) {
            return redirect(route('firstRun'));
        }
        return $next($request);
    }
}
