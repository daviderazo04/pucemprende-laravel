<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;    
use Illuminate\Http\JsonResponse;       //Temporal mientras no haya login en el front

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): JsonResponse //RedirectResponse        cambiar esto cuando haya login en el front
    {
        if ($request->user()->hasVerifiedEmail()) {
            //Esto para cuando se tenga el front porque redirige a login
            return redirect()->intended(
                config('app.frontend_url').'/dashboard?verified=1'
            );

            //Era pa probar en postman pero no funciona
            //return response()->json(['message' => 'Email verificado correctamente.'], 200);
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }


        return redirect()->intended(
            config('app.frontend_url').'/dashboard?verified=1'
        );
        

        //Era pa probar en postman pero no funciona
        //return response()->json(['message' => 'Email verificado correctamente.'], 200);
    }
}
