<?php

declare(strict_types=1);

namespace Bambamboole\LaravelOidcClient\Http\Controllers;

use Bambamboole\LaravelOidcClient\Exceptions\OidcClientException;
use Bambamboole\LaravelOidcClient\RelyingParty;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OidcCallbackController
{
    public function __invoke(Request $request, RelyingParty $relyingParty): RedirectResponse
    {
        try {
            return $relyingParty->handleCallback($request);
        } catch (OidcClientException) {
            $loginRoute = (string) config('oidc-client.routes.login.name', 'login');

            return redirect()->route($loginRoute)->withErrors([
                'oidc' => 'Sign-in failed. Please try again.',
            ]);
        }
    }
}
