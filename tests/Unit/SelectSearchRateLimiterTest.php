<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\Select\SelectSearchRateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

it('builds a rate-limit key from authenticated user and field name', function (): void {
    $user = new class
    {
        public function getAuthIdentifier(): int
        {
            return 42;
        }
    };

    $request = Request::create('/admin', 'GET');
    $request->setUserResolver(fn () => $user);

    $limiter = new SelectSearchRateLimiter($request);

    expect($limiter->key('assignee'))->toBe('fff-select-search:42:assignee')
        ->and($limiter->maxAttempts())->toBeGreaterThanOrEqual(1);
});

it('falls back to request ip when guest', function (): void {
    $request = Request::create('/admin', 'GET', server: ['REMOTE_ADDR' => '203.0.113.10']);
    $request->setUserResolver(fn () => null);

    $limiter = new SelectSearchRateLimiter($request);

    expect($limiter->key('status'))->toBe('fff-select-search:203.0.113.10:status');
});

it('blocks after max attempts within the window', function (): void {
    config()->set('filament-flex-fields.select.search_rate_limit_per_minute', 2);

    $request = Request::create('/admin', 'GET', server: ['REMOTE_ADDR' => '198.51.100.20']);
    $request->setUserResolver(fn () => null);
    $limiter = new SelectSearchRateLimiter($request);
    $field = 'rate-limit-test-'.uniqid();

    RateLimiter::clear($limiter->key($field));

    expect($limiter->attempt($field))->toBeTrue()
        ->and($limiter->attempt($field))->toBeTrue()
        ->and($limiter->attempt($field))->toBeFalse()
        ->and($limiter->tooManyAttempts($field))->toBeTrue();

    RateLimiter::clear($limiter->key($field));
});

it('keeps separate keys for two authenticated users behind the same load-balancer IP', function (): void {
    $sharedIp = '203.0.113.50';

    $userA = new class
    {
        public function getAuthIdentifier(): int
        {
            return 101;
        }
    };

    $userB = new class
    {
        public function getAuthIdentifier(): int
        {
            return 202;
        }
    };

    $requestA = Request::create('/admin', 'GET', server: [
        'REMOTE_ADDR' => '10.0.0.1',
        'HTTP_X_FORWARDED_FOR' => $sharedIp,
    ]);
    $requestA->setTrustedProxies(['10.0.0.1'], Request::HEADER_X_FORWARDED_FOR);
    $requestA->setUserResolver(fn () => $userA);

    $requestB = Request::create('/admin', 'GET', server: [
        'REMOTE_ADDR' => '10.0.0.1',
        'HTTP_X_FORWARDED_FOR' => $sharedIp,
    ]);
    $requestB->setTrustedProxies(['10.0.0.1'], Request::HEADER_X_FORWARDED_FOR);
    $requestB->setUserResolver(fn () => $userB);

    $limiterA = new SelectSearchRateLimiter($requestA);
    $limiterB = new SelectSearchRateLimiter($requestB);
    $field = 'assignee';

    expect($limiterA->key($field))->toBe('fff-select-search:101:assignee')
        ->and($limiterB->key($field))->toBe('fff-select-search:202:assignee')
        ->and($limiterA->key($field))->not->toBe($limiterB->key($field));
});

it('uses Request::ip under TrustedProxies for guests and never keys on raw X-Forwarded-For alone', function (): void {
    $request = Request::create('/admin', 'GET', server: [
        'REMOTE_ADDR' => '10.0.0.1',
        'HTTP_X_FORWARDED_FOR' => '198.51.100.77',
    ]);
    $request->setTrustedProxies(['10.0.0.1'], Request::HEADER_X_FORWARDED_FOR);
    $request->setUserResolver(fn () => null);

    $limiter = new SelectSearchRateLimiter($request);

    expect($request->ip())->toBe('198.51.100.77')
        ->and($limiter->key('status'))->toBe('fff-select-search:198.51.100.77:status')
        ->and($limiter->key('status'))->not->toContain('10.0.0.1');
});
