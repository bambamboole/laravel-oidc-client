<?php

declare(strict_types=1);

namespace Bambamboole\LaravelOidcClient\Http\Controllers;

use Bambamboole\LaravelOidcClient\Exceptions\OidcClientException;
use Bambamboole\LaravelOidcClient\Token\LogoutTokenValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class BackchannelLogoutController
{
    public function __invoke(Request $request, LogoutTokenValidator $validator): Response
    {
        try {
            ['sid' => $sid] = $validator->validate((string) $request->input('logout_token'));
        } catch (OidcClientException) {
            return response()->json(['error' => 'invalid_request'], 400)->header('Cache-Control', 'no-store, private');
        }

        $retention = (int) config('oidc-client.backchannel_logout.retention_minutes', 120);

        // Immediate teardown for server-side stores (no-op for the cookie driver).
        $sessionId = Cache::pull("oidc-client:bclo:session:{$sid}");
        if (is_string($sessionId) && $sessionId !== '') {
            Session::getHandler()->destroy($sessionId);
        }

        // Universal fallback marker for the enforcement middleware.
        Cache::put("oidc-client:bclo:revoked:{$sid}", true, now()->addMinutes($retention));

        return response('', 200)->header('Cache-Control', 'no-store, private');
    }
}
