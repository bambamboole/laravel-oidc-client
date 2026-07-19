<?php

declare(strict_types=1);

use Bambamboole\LaravelOidcClient\Http\Controllers\BackchannelLogoutController;
use Bambamboole\LaravelOidcClient\Http\Controllers\OidcCallbackController;
use Bambamboole\LaravelOidcClient\Http\Controllers\OidcLoginController;
use Bambamboole\LaravelOidcClient\Http\Controllers\OidcLogoutController;
use Bambamboole\LaravelOidcClient\Routing\Handler;
use Bambamboole\LaravelOidcClient\Routing\HandlerConfig;

function handlerConfig(Handler $handler): HandlerConfig
{
    $config = $handler->config();

    if (! $config instanceof HandlerConfig) {
        throw new RuntimeException("Handler [{$handler->value}] should be configured.");
    }

    return $config;
}

it('resolves every handler from code defaults when config carries no overrides', function () {
    config()->set('oidc-client.handlers', []);
    config()->set('oidc-client.backchannel_logout.enabled', true);

    $login = handlerConfig(Handler::Login);
    $callback = handlerConfig(Handler::Callback);
    $logout = handlerConfig(Handler::Logout);
    $backchannel = handlerConfig(Handler::BackchannelLogout);

    expect($login->route)->toBe('login')
        ->and($login->controller)->toBe(OidcLoginController::class)
        ->and($login->middleware)->toBe(['web'])
        ->and($callback->route)->toBe('login/callback')
        ->and($callback->controller)->toBe(OidcCallbackController::class)
        ->and($callback->middleware)->toBe(['web'])
        ->and($logout->route)->toBe('logout')
        ->and($logout->controller)->toBe(OidcLogoutController::class)
        ->and($logout->middleware)->toBe(['web'])
        ->and($backchannel->route)->toBe('oidc/backchannel-logout')
        ->and($backchannel->controller)->toBe(BackchannelLogoutController::class)
        ->and($backchannel->middleware)->toBe(['throttle:60,1']);
});

it('exposes the intrinsic http verb per handler', function () {
    expect(Handler::Login->method())->toBe('get')
        ->and(Handler::Callback->method())->toBe('get')
        ->and(Handler::Logout->method())->toBe('post')
        ->and(Handler::BackchannelLogout->method())->toBe('post');
});

it('applies a sparse route override and keeps the other keys from defaults', function () {
    config()->set('oidc-client.handlers', [
        Handler::Login->value => ['route' => 'sign-in'],
    ]);

    $config = handlerConfig(Handler::Login);

    expect($config->route)->toBe('sign-in')
        ->and($config->controller)->toBe(OidcLoginController::class)
        ->and($config->middleware)->toBe(['web']);
});

it('still registers the other handlers from defaults when one is overridden', function () {
    config()->set('oidc-client.handlers', [
        Handler::Login->value => ['route' => 'sign-in'],
    ]);

    expect(handlerConfig(Handler::Callback)->route)->toBe('login/callback')
        ->and(handlerConfig(Handler::Logout)->route)->toBe('logout');
});

it('replaces the default middleware with a per-handler override rather than merging', function () {
    config()->set('oidc-client.handlers', [
        Handler::Login->value => ['middleware' => ['web', 'auth']],
    ]);

    expect(handlerConfig(Handler::Login)->middleware)->toBe(['web', 'auth']);
});

it('supports a controller override as an escape hatch', function () {
    config()->set('oidc-client.handlers', [
        Handler::Login->value => ['controller' => OidcLogoutController::class],
    ]);

    expect(handlerConfig(Handler::Login)->controller)->toBe(OidcLogoutController::class);
});

it('treats a disabled handler as false', function () {
    config()->set('oidc-client.handlers', [
        Handler::Logout->value => false,
    ]);

    expect(Handler::Logout->config())->toBeFalse();
});

it('appends routes.middleware to every handler', function () {
    config()->set('oidc-client.handlers', []);
    config()->set('oidc-client.routes.middleware', ['throttle:api']);

    expect(handlerConfig(Handler::Login)->middleware)->toBe(['web', 'throttle:api'])
        ->and(handlerConfig(Handler::Callback)->middleware)->toBe(['web', 'throttle:api'])
        ->and(handlerConfig(Handler::Logout)->middleware)->toBe(['web', 'throttle:api']);
});

it('appends routes.middleware after a per-handler middleware override', function () {
    config()->set('oidc-client.handlers', [
        Handler::Login->value => ['middleware' => ['auth']],
    ]);
    config()->set('oidc-client.routes.middleware', ['throttle:api']);

    expect(handlerConfig(Handler::Login)->middleware)->toBe(['auth', 'throttle:api']);
});

it('prefixes every handler path with routes.prefix', function () {
    config()->set('oidc-client.handlers', []);
    config()->set('oidc-client.routes.prefix', 'sso');
    config()->set('oidc-client.backchannel_logout.enabled', true);

    expect(handlerConfig(Handler::Login)->route)->toBe('sso/login')
        ->and(handlerConfig(Handler::Callback)->route)->toBe('sso/login/callback')
        ->and(handlerConfig(Handler::Logout)->route)->toBe('sso/logout')
        ->and(handlerConfig(Handler::BackchannelLogout)->route)->toBe('sso/oidc/backchannel-logout');
});

it('trims surrounding slashes from routes.prefix', function () {
    config()->set('oidc-client.handlers', []);
    config()->set('oidc-client.routes.prefix', '/sso/');

    expect(handlerConfig(Handler::Login)->route)->toBe('sso/login');
});

it('applies no prefix when routes.prefix is empty', function () {
    config()->set('oidc-client.handlers', []);
    config()->set('oidc-client.routes.prefix', '');

    expect(handlerConfig(Handler::Login)->route)->toBe('login');
});

it('gates the backchannel logout handler behind its feature flag', function () {
    config()->set('oidc-client.handlers', []);

    config()->set('oidc-client.backchannel_logout.enabled', false);
    expect(Handler::BackchannelLogout->config())->toBeFalse();

    config()->set('oidc-client.backchannel_logout.enabled', true);
    expect(Handler::BackchannelLogout->config())->toBeInstanceOf(HandlerConfig::class);
});
