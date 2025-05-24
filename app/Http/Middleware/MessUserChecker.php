<?php

namespace App\Http\Middleware;

use App\Exceptions\CustomException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MessUserChecker
{
    /**
     * Handle an incoming request.
     * Verifies that the authenticated user has a valid MessUser record,
     * belongs to a valid Mess, and is initiated if required.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  bool  $checkInitiated  Whether to check if the user is initiated
     */
    public function handle(Request $request, Closure $next, $checkInitiated = false): Response
    {
        $checkInitiated = filter_var($checkInitiated, FILTER_VALIDATE_BOOLEAN);
        $user = auth()->user();

        if (!$user) {
            throw new CustomException(message: "User is not authenticated");
        }

        // Check if user has a valid MessUser record
        $messUser = $user->messUser;

        if (!$messUser) {
            throw new CustomException(message: "You are not a member of any mess");
        }

        // Check if the MessUser belongs to a valid Mess
        $mess = $messUser->mess;

        if (!$mess) {
            throw new CustomException(message: "Mess not found for this user");
        }

        // Check if the user is initiated if required
        if ($checkInitiated) {
            $initiatedUser = app()->getMonth()->initiatedUser()->where("mess_user_id", $messUser->id)->exists();

            if (!$initiatedUser) {
                throw new CustomException(message: "User has not been initiated yet in this month");
            }
        }

        // Store the MessUser and Mess in the app container for later use
        // Store the MessUser in the app container and set the current mess
        app()->setMessUser($messUser);
        app()->setMess($mess);


        return $next($request);
    }
}
