<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\Schedule\ScheduleValidator;

$fixturePath = __DIR__.'/../fixtures/schedule-validation-contract.json';

$contract = json_decode((string) file_get_contents($fixturePath), true, 512, JSON_THROW_ON_ERROR);

it('matches js overlap contract fixtures in php', function (array $case) {
    $validator = new ScheduleValidator;

    expect($validator->slotsOverlap($case['slots']))->toBe($case['expects_overlap']);
})->with(collect($contract['overlap_cases'])->map(fn (array $case): array => [$case])->all());

it('matches js min max contract fixtures in php', function (array $case) {
    $validator = new ScheduleValidator(
        minSlots: $case['min_slots'],
        maxSlots: $case['max_slots'],
    );

    $errors = [];

    $validator->validateDay('mon', [
        'enabled' => true,
        'slots' => $case['slots'],
    ], function (string $message) use (&$errors): void {
        $errors[] = $message;
    });

    if ($case['expects_validation_code'] === null) {
        expect($errors)->toBe([]);

        return;
    }

    expect($errors)->not->toBeEmpty();
})->with(collect($contract['min_max_cases'])->map(fn (array $case): array => [$case])->all());
