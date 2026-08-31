<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\Intelligence\FormulaEngine;
use Bjanczak\FilamentFlexFields\Support\Intelligence\SmartDefaults;

it('evaluates golden formula expressions with field references', function (): void {
    expect(FormulaEngine::evaluate('2 + 3', []))->toBe(5)
        ->and(FormulaEngine::evaluate('10 - 4', []))->toBe(6)
        ->and(FormulaEngine::evaluate('3 * 4', []))->toBe(12)
        ->and(FormulaEngine::evaluate('15 / 3', []))->toBe(5)
        ->and(FormulaEngine::evaluate('(2 + 3) * 4', []))->toBe(20)
        ->and(FormulaEngine::evaluate('{price} * {qty}', [
            'price' => 12.5,
            'qty' => 4,
        ]))->toBe(50)
        ->and(FormulaEngine::evaluate('{subtotal} + {tax} - {discount}', [
            'subtotal' => 100,
            'tax' => 23,
            'discount' => 8,
        ]))->toBe(115);
});

it('evaluates enterprise whitelist functions comparisons and money helpers', function (): void {
    expect(FormulaEngine::evaluate('abs(-12)', []))->toBe(12)
        ->and(FormulaEngine::evaluate('min({a},{b},3)', ['a' => 9, 'b' => 4]))->toBe(3)
        ->and(FormulaEngine::evaluate('max({a},{b})', ['a' => 9, 'b' => 4]))->toBe(9)
        ->and(FormulaEngine::evaluate('round(12.56,1)', []))->toBe(12.6)
        ->and(FormulaEngine::evaluate('floor(12.9)', []))->toBe(12)
        ->and(FormulaEngine::evaluate('ceil(12.1)', []))->toBe(13)
        ->and(FormulaEngine::evaluate('trunc(-12.9)', []))->toBe(-12)
        ->and(FormulaEngine::evaluate('clamp({score},0,100)', ['score' => 140]))->toBe(100)
        ->and(FormulaEngine::evaluate('pct(23)', []))->toBe(0.23)
        ->and(FormulaEngine::evaluate('sum(1,2,3)', []))->toBe(6)
        ->and(FormulaEngine::evaluate('avg(2,4,6)', []))->toBe(4)
        ->and(FormulaEngine::evaluate('pow(2,3)', []))->toBe(8)
        ->and(FormulaEngine::evaluate('sqrt(9)', []))->toBe(3)
        ->and(FormulaEngine::evaluate('sign(-8)', []))->toBe(-1)
        ->and(FormulaEngine::evaluate('between(5,1,10)', []))->toBe(1)
        ->and(FormulaEngine::evaluate('and({a}>0,{b}<10)', ['a' => 9, 'b' => 4]))->toBe(1)
        ->and(FormulaEngine::evaluate('or(0,{b}>0)', ['b' => 4]))->toBe(1)
        ->and(FormulaEngine::evaluate('not(0)', []))->toBe(1)
        ->and(FormulaEngine::evaluate('if({a}>{b},{a},{b})', ['a' => 9, 'b' => 4]))->toBe(9)
        ->and(FormulaEngine::evaluate('nz(0,15)', []))->toBe(15)
        ->and(FormulaEngine::evaluate('10%4', []))->toBe(2)
        ->and(FormulaEngine::evaluate('{subtotal}*pct({tax_rate})', [
            'subtotal' => 100,
            'tax_rate' => 23,
        ]))->toBe(23)
        ->and(FormulaEngine::moneyMajor(85000))->toBe(850.0)
        ->and(FormulaEngine::moneyMinor(850))->toBe(85000)
        ->and(FormulaEngine::toNumber(['amount' => 1250, 'currency' => 'EUR']))->toBe(1250.0)
        ->and(FormulaEngine::tryEvaluate('sin(1)', []))->toBeNull()
        ->and(FormulaEngine::validate('sin(1)'))->not->toBeEmpty()
        ->and(FormulaEngine::validate('1+'))->not->toBeEmpty()
        ->and(FormulaEngine::formulasEnabled())->toBeTrue()
        ->and(FormulaEngine::allowedFunctions())->toContain('clamp', 'pct', 'if', 'and', 'between', 'coalesce');
});

it('short-circuits if and or so unused branches do not throw', function (): void {
    expect(FormulaEngine::evaluate('if({qty}>0,{total}/{qty},0)', [
        'qty' => 0,
        'total' => 100,
    ]))->toBe(0)
        ->and(FormulaEngine::evaluate('if({qty}>0,{total}/{qty},0)', [
            'qty' => 4,
            'total' => 100,
        ]))->toBe(25)
        ->and(FormulaEngine::evaluate('or(1,1/0)', []))->toBe(1)
        ->and(FormulaEngine::evaluate('and(0,1/0)', []))->toBe(0)
        ->and(FormulaEngine::evaluate('nz(12,1/0)', []))->toBe(12);
});

it('evaluates acyclic formula maps and explains expressions', function (): void {
    $results = FormulaEngine::evaluateMap([
        'charter_fee' => '{day_rate}*{nights}',
        'apa' => '{charter_fee}*pct({apa_pct})',
        'subtotal' => '{charter_fee}+{apa}',
    ], [
        'day_rate' => 850,
        'nights' => 7,
        'apa_pct' => 30,
    ]);

    expect($results['charter_fee'])->toBe(5950)
        ->and($results['apa'])->toBe(1785)
        ->and($results['subtotal'])->toBe(7735);

    $explained = FormulaEngine::explain('round({a}*pct({b}),2)', [
        'a' => 199.99,
        'b' => 23,
    ]);

    expect($explained['references'])->toBe(['a', 'b'])
        ->and($explained['functions'])->toContain('round', 'pct')
        ->and($explained['result'])->toBe(46)
        ->and($explained['substituted'])->toContain('199.99');
});

it('rejects unsafe formula constructs', function (): void {
    expect(fn () => FormulaEngine::evaluate('sin(1)', []))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => FormulaEngine::evaluate('{price} + abc', ['price' => 1]))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => FormulaEngine::evaluate('{missing} + 1', []))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => FormulaEngine::evaluate('10 / 0', []))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => FormulaEngine::evaluateMap([
            'net' => '{total}-1',
            'total' => '{net}+1',
        ], []))
        ->toThrow(InvalidArgumentException::class);
});

it('detects formula dependency cycles', function (): void {
    $acyclic = [
        'total' => '{price} * {qty}',
        'margin' => '{total} - {cost}',
    ];

    expect(FormulaEngine::detectCycle($acyclic))->toBe([]);

    $cyclic = [
        'total' => '{net} + {tax}',
        'net' => '{total} - {tax}',
    ];

    expect(FormulaEngine::detectCycle($cyclic))->toBe(['net', 'total']);
});

it('keeps formulas always enabled regardless of legacy config', function (): void {
    config()->set('filament-flex-fields.intelligence.formulas', false);

    expect(FormulaEngine::formulasEnabled())->toBeTrue();

    config()->set('filament-flex-fields.intelligence.formulas', true);

    expect(FormulaEngine::formulasEnabled())->toBeTrue();
});

it('stores and clears smart defaults per user and tenant', function (): void {
    SmartDefaults::reset();

    SmartDefaults::remember('user-1', 'company_name', 'Acme Yachts');
    SmartDefaults::tenantDefaults('tenant-9', ['currency' => 'EUR', 'locale' => 'de']);

    expect(SmartDefaults::recall('user-1', 'company_name'))->toBe('Acme Yachts')
        ->and(SmartDefaults::recall('user-1', 'missing'))->toBeNull()
        ->and(SmartDefaults::getTenantDefaults('tenant-9'))->toBe([
            'currency' => 'EUR',
            'locale' => 'de',
        ]);

    SmartDefaults::clearUser('user-1');

    expect(SmartDefaults::recall('user-1', 'company_name'))->toBeNull()
        ->and(SmartDefaults::getTenantDefaults('tenant-9'))->toBe([
            'currency' => 'EUR',
            'locale' => 'de',
        ]);

    SmartDefaults::reset();
});
