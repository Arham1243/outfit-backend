<?php

namespace App\Http\Middleware;

use App\Support\AccountStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckIfUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $status = $user->status;

            if (! AccountStatus::isActive($status)) {
                optional($request->user()->currentAccessToken())->delete();

                $message = 'Your access has been expired. Please contact your System Administrator to restore it.';

                return response()->json([
                    'message' => $message,
                    'errors' => ['email' => [$message]],
                ], 403);
            }
        }

        return $next($request);
    }
}
