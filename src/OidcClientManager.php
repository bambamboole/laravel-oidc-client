<?php

declare(strict_types=1);

namespace Bambamboole\LaravelOidcClient;

use Bambamboole\LaravelOidcClient\Routing\Handler;
use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class OidcClientManager
{
    /**
     * @var (Closure(string, array<string, mixed>): (Authenticatable|null))|null
     */
    private ?Closure $resolveUsersUsing = null;

    /**
     * Register every enabled {@see Handler} as a route.
     *
     * The list of endpoints, their intrinsic HTTP verb, and their default
     * path/controller/middleware come from the {@see Handler} enum; sparse
     * per-endpoint overrides and the global `routes` settings (or whether an
     * endpoint is disabled) come from config via {@see Handler::config()}.
     */
    public function routes(): void
    {
        foreach (Handler::cases() as $handler) {
            $config = $handler->config();

            if ($config === false) {
                continue;
            }

            Route::{$handler->method()}($config->route, $config->controller)
                ->name($handler->value)
                ->middleware($config->middleware);
        }
    }

    /**
     * The guard resolved users are logged into (`oidc-client.login_guard`).
     */
    public function guard(): StatefulGuard
    {
        /** @var StatefulGuard $guard The login guard must be session-based. */
        $guard = Auth::guard($this->guardName());

        return $guard;
    }

    /**
     * Redirect to the post-login destination (`oidc-client.redirect_after_login`).
     */
    public function redirectAfterLogin(): RedirectResponse
    {
        return redirect()->intended((string) config('oidc-client.redirect_after_login', '/dashboard'));
    }

    /**
     * Log out of the login guard and fully invalidate the local session.
     */
    public function terminateLocalSession(Request $request): void
    {
        $this->guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    /**
     * @param  Closure(string, array<string, mixed>): (Authenticatable|null)  $callback
     */
    public function resolveUsersUsing(Closure $callback): void
    {
        $this->resolveUsersUsing = $callback;
    }

    /**
     * @param  array<string, mixed>  $claims
     */
    public function resolveUser(string $sub, array $claims): ?Authenticatable
    {
        if ($this->resolveUsersUsing !== null) {
            return ($this->resolveUsersUsing)($sub, $claims);
        }

        $provider = Auth::createUserProvider(config('auth.guards.'.$this->guardName().'.provider'));

        return $provider?->retrieveById($sub);
    }

    private function guardName(): string
    {
        return (string) config('oidc-client.login_guard', 'web');
    }
}
