<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * If a member (individual/official or district federation) fails to renew
 * before their billing period expires, they are locked out of everything
 * except the renewal payment page — their data and history are preserved
 * and access resumes automatically the moment they pay.
 */
class EnsureMembershipActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isMembershipExpired()) {
            $allowed = [
                'membership.renew',
                'membership.renew.process',
                'membership.checkout',
                'membership.renew.confirm',
                'logout',
            ];

            if (! in_array($request->route()?->getName(), $allowed, true)) {
                return redirect()->route('membership.renew');
            }
        }

        return $next($request);
    }
}
