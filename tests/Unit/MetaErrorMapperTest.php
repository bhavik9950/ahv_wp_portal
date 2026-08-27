<?php

declare(strict_types=1);

use App\Enums\ErrorCategory;
use App\Services\WhatsApp\MetaErrorMapper;

beforeEach(fn () => $this->mapper = new MetaErrorMapper);

it('classifies HTTP 429 as rate limited and honours Retry-After', function () {
    $error = $this->mapper->fromHttp(429, ['error' => ['code' => 130429, 'message' => 'rate limit']], ['Retry-After' => ['17']]);

    expect($error->category)->toBe(ErrorCategory::RateLimited)
        ->and($error->retryAfterSeconds)->toBe(17)
        ->and($error->isRetryable())->toBeTrue();
});

it('classifies 5xx as temporary and retryable', function () {
    $error = $this->mapper->fromHttp(503, ['error' => ['message' => 'server']]);

    expect($error->category)->toBe(ErrorCategory::Temporary)
        ->and($error->isRetryable())->toBeTrue();
});

it('classifies auth failures as non-retryable', function () {
    $error = $this->mapper->fromHttp(401, ['error' => ['code' => 190, 'message' => 'bad token']]);

    expect($error->category)->toBe(ErrorCategory::Auth)
        ->and($error->isRetryable())->toBeFalse();
});

it('classifies template errors and never leaks the raw meta message to users', function () {
    $error = $this->mapper->fromHttp(400, ['error' => ['code' => 132001, 'message' => 'template x not found for namespace y']]);

    expect($error->category)->toBe(ErrorCategory::Template)
        ->and($error->isRetryable())->toBeFalse()
        ->and($error->userMessage)->not->toContain('namespace')
        ->and($error->adminMessage)->toContain('template x not found');
});

it('falls back to unknown for unrecognised codes', function () {
    $error = $this->mapper->fromHttp(400, ['error' => ['code' => 999999, 'message' => '???']]);

    expect($error->category)->toBe(ErrorCategory::Unknown);
});
