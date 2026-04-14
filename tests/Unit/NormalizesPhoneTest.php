<?php

use App\Models\User;

test('normalizes 10-digit SA number with leading zero', function () {
    expect(User::normalizePhone('0821234567'))->toBe('0821234567');
});

test('normalizes 9-digit number by prepending zero', function () {
    expect(User::normalizePhone('821234567'))->toBe('0821234567');
});

test('normalizes +27 international format', function () {
    expect(User::normalizePhone('+27821234567'))->toBe('0821234567');
});

test('normalizes 27 prefix without plus', function () {
    expect(User::normalizePhone('27821234567'))->toBe('0821234567');
});

test('strips spaces from phone number', function () {
    expect(User::normalizePhone('082 123 4567'))->toBe('0821234567');
});

test('strips dashes from phone number', function () {
    expect(User::normalizePhone('082-123-4567'))->toBe('0821234567');
});

test('strips parentheses from phone number', function () {
    expect(User::normalizePhone('(082) 123 4567'))->toBe('0821234567');
});

test('returns null for empty input', function () {
    expect(User::normalizePhone(''))->toBeNull();
    expect(User::normalizePhone(null))->toBeNull();
});

test('returns null for invalid short number', function () {
    expect(User::normalizePhone('12345'))->toBeNull();
});

test('returns null for non-numeric garbage', function () {
    expect(User::normalizePhone('not a phone'))->toBeNull();
});

test('handles Excel-truncated number with +27 and spaces', function () {
    expect(User::normalizePhone('+27 82 123 4567'))->toBe('0821234567');
});
