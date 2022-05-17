<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SecureHeaders
{
    /** @var array|string[] */
    protected array $secureHeaders = [
        'Cache-Control' => 'no-store, max-age=0',
        'Pragma' => 'no-cache',
        'Referrer-Policy' => 'no-referrer',
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'deny',
        'X-Permitted-Cross-Domain-Policies' => 'none',
        'X-XSS-Protection' => '1; mode=block',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        /** @var RedirectResponse $response */
        $response = $next($request);

        foreach ($this->secureHeaders as $name => $value) {
            $response->headers->set($name, $value);
        }

        return $response;
    }
}
