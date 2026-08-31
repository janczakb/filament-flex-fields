<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\DateTime\DateTimeCalendar;

it('resolves indian calendar from locale extension', function () {
    expect(DateTimeCalendar::resolveIdentifier('hi-IN-u-ca-indian'))->toBe('indian');
});

it('normalizes gregorian aliases', function () {
    expect(DateTimeCalendar::normalizeIdentifier('gregorian'))->toBe('gregory');
});

it('prefers explicit calendar system override', function () {
    expect(DateTimeCalendar::resolveIdentifier('hi-IN-u-ca-indian', 'persian'))->toBe('persian');
});
