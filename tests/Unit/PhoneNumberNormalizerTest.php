<?php

declare(strict_types=1);

use App\Services\WhatsApp\PhoneNumberNormalizer;

beforeEach(fn () => $this->n = new PhoneNumberNormalizer('91'));

it('normalizes a national number with the default country code', function () {
    expect($this->n->normalize('9876543210'))->toBe('919876543210');
});

it('keeps an already international number', function () {
    expect($this->n->normalize('+91 98765 43210'))->toBe('919876543210')
        ->and($this->n->normalize('0091-98765-43210'))->toBe('919876543210');
});

it('applies an explicit country code', function () {
    expect($this->n->normalize('7911 123456', '44'))->toBe('447911123456');
});

it('rejects numbers that are too short or empty', function () {
    expect($this->n->normalize('123'))->toBeNull()
        ->and($this->n->normalize(''))->toBeNull()
        ->and($this->n->normalize('not a phone'))->toBeNull();
});

it('guesses the country code from an E.164 number', function () {
    expect($this->n->parse('+14155550100')['country_code'])->toBe('1');
});
