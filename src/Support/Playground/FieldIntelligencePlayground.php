<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CurrencyField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextareaField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\NumberStepper;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Bjanczak\FilamentFlexFields\Support\Intelligence\FormulaEngine;
use Closure;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Throwable;

class FieldIntelligencePlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'formula__day_rate' => 85000,
            'formula__nights' => 7,
            'formula__apa_pct' => 30,
            'formula__broker_pct' => 10,
            'formula__deal_vat_rate' => 23,
            'formula__deal_discount' => 25000,
            'formula__charter_fee' => 595000,
            'formula__apa' => 178500,
            'formula__deal_subtotal' => 773500,
            'formula__commission' => 77350,
            'formula__deal_vat' => 177905,
            'formula__deal_grand' => 926405,

            'formula__subtotal' => 595000,
            'formula__tax_rate' => 23,
            'formula__discount' => 20000,
            'formula__vat' => 136850,
            'formula__grand_total' => 711850,

            'formula__done' => 18,
            'formula__goal' => 24,
            'formula__progress_pct' => '75',

            'formula__revenue' => 1200000,
            'formula__cost' => 780000,
            'formula__margin' => 420000,
            'formula__margin_pct' => '35',

            'formula__lab_a' => 120,
            'formula__lab_b' => 4,
            'formula__lab_c' => 15,
            'formula__lab_expr' => 'round(clamp(({a}*{b})-({a}*pct({c})), 0, 9999), 2)',
            'formula__lab_result' => '462',
            'formula__lab_refs' => 'a, b, c',
            'formula__lab_status' => 'OK',

            'formula__cycle_mode' => 'safe',
            'formula__cycle_report' => 'No cycles — graph is acyclic.',

            'formula__reject_expr' => 'sin({x})',
            'formula__reject_status' => 'Rejected: Formula contains disallowed characters or constructs.',
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            Section::make('Calculated formulas')
                ->description('Enterprise FormulaEngine — always-on safe `{field}` arithmetic for Filament forms and CurrencyField minor-unit money.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    View::make('filament-flex-fields::partials.playground.field-intelligence-intro'),
                    PlaygroundCodeSnippet::make(<<<'PHP'
use Bjanczak\FilamentFlexFields\Support\Intelligence\FormulaEngine;

// Always on — whitelist helpers: abs min max round floor ceil trunc clamp
// sum avg pct pow sqrt sign between and or not coalesce if nz
// Short-circuit: if / and / or / coalesce / nz (unused branches never run)
FormulaEngine::evaluate('clamp(round({score},0),0,100)', ['score' => 103.7]);
FormulaEngine::evaluate('if({qty}>0,{total}/{qty},0)', ['qty' => 0, 'total' => 100]); // 0, no div/0
FormulaEngine::evaluate('{subtotal}*pct({tax_rate})', [
    'subtotal' => 100,
    'tax_rate' => 23,
]);

FormulaEngine::evaluateMap([
    'charter_fee' => '{day_rate}*{nights}',
    'apa' => '{charter_fee}*pct({apa_pct})',
    'subtotal' => '{charter_fee}+{apa}',
], [
    'day_rate' => 850,
    'nights' => 7,
    'apa_pct' => 30,
]);

FormulaEngine::explain('{day_rate}*{nights}', [
    'day_rate' => 850,
    'nights' => 7,
]);
// ['expression' => '...', 'references' => [...], 'result' => 5950]

// CurrencyField stores minor units (€850.00 => 85000)
FormulaEngine::moneyMajor(85000); // 850.0
FormulaEngine::moneyMinor(850);   // 85000
PHP, filename: 'formula-engine.php'),
                ]),

            Section::make('Enterprise deal desk')
                ->description('Charter → APA → subtotal → broker commission + VAT − discount. CurrencyField minor units + live FormulaEngine chain.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    Grid::make(['default' => 1, 'sm' => 2, 'lg' => 4])
                        ->schema([
                            CurrencyField::make('formula__day_rate')
                                ->label('Day rate')
                                ->currency('EUR')
                                ->live()
                                ->afterStateUpdated($this->syncDealDeskClosure()),
                            NumberStepper::make('formula__nights')
                                ->label('Nights')
                                ->minValue(1)
                                ->maxValue(30)
                                ->live(debounce: 400)
                                ->afterStateUpdated($this->syncDealDeskClosure()),
                            NumberStepper::make('formula__apa_pct')
                                ->label('APA %')
                                ->suffix('%')
                                ->minValue(0)
                                ->maxValue(50)
                                ->live(debounce: 400)
                                ->afterStateUpdated($this->syncDealDeskClosure()),
                            NumberStepper::make('formula__broker_pct')
                                ->label('Broker %')
                                ->suffix('%')
                                ->minValue(0)
                                ->maxValue(30)
                                ->live(debounce: 400)
                                ->afterStateUpdated($this->syncDealDeskClosure()),
                            NumberStepper::make('formula__deal_vat_rate')
                                ->label('VAT rate')
                                ->suffix('%')
                                ->minValue(0)
                                ->maxValue(40)
                                ->live(debounce: 400)
                                ->afterStateUpdated($this->syncDealDeskClosure()),
                            CurrencyField::make('formula__deal_discount')
                                ->label('Discount')
                                ->currency('EUR')
                                ->live()
                                ->afterStateUpdated($this->syncDealDeskClosure()),
                            CurrencyField::make('formula__charter_fee')
                                ->label('Charter fee')
                                ->currency('EUR')
                                ->disabled()
                                ->dehydrated(false)
                                ->helperText('{day_rate}*{nights}'),
                            CurrencyField::make('formula__apa')
                                ->label('APA')
                                ->currency('EUR')
                                ->disabled()
                                ->dehydrated(false)
                                ->helperText('{charter_fee}*pct({apa_pct})'),
                            CurrencyField::make('formula__deal_subtotal')
                                ->label('Subtotal')
                                ->currency('EUR')
                                ->disabled()
                                ->dehydrated(false)
                                ->helperText('{charter_fee}+{apa}'),
                            CurrencyField::make('formula__commission')
                                ->label('Broker commission')
                                ->currency('EUR')
                                ->disabled()
                                ->dehydrated(false)
                                ->helperText('{deal_subtotal}*pct({broker_pct})'),
                            CurrencyField::make('formula__deal_vat')
                                ->label('VAT')
                                ->currency('EUR')
                                ->disabled()
                                ->dehydrated(false)
                                ->helperText('round({deal_subtotal}*pct({deal_vat_rate}), 2)'),
                            CurrencyField::make('formula__deal_grand')
                                ->label('Grand total')
                                ->currency('EUR')
                                ->disabled()
                                ->dehydrated(false)
                                ->helperText('{deal_subtotal}+{deal_vat}-{deal_discount}')
                                ->columnSpan(['default' => 1, 'sm' => 2, 'lg' => 2]),
                        ]),
                    PlaygroundCodeSnippet::make(<<<'PHP'
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CurrencyField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\NumberStepper;
use Bjanczak\FilamentFlexFields\Support\Intelligence\FormulaEngine;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

$recalculate = function (mixed $state, Set $set, Get $get): void {
    $computed = FormulaEngine::evaluateMap([
        'charter_fee' => '{day_rate}*{nights}',
        'apa' => '{charter_fee}*pct({apa_pct})',
        'deal_subtotal' => '{charter_fee}+{apa}',
        'commission' => '{deal_subtotal}*pct({broker_pct})',
        'deal_vat' => 'round({deal_subtotal}*pct({deal_vat_rate}), 2)',
        'deal_grand' => '{deal_subtotal}+{deal_vat}-{deal_discount}',
    ], [
        'day_rate' => FormulaEngine::moneyMajor($get('day_rate')),
        'nights' => $get('nights'),
        'apa_pct' => $get('apa_pct'),
        'broker_pct' => $get('broker_pct'),
        'deal_vat_rate' => $get('deal_vat_rate'),
        'deal_discount' => FormulaEngine::moneyMajor($get('deal_discount')),
    ]);

    foreach ($computed as $slug => $value) {
        $set($slug, FormulaEngine::moneyMinor($value));
    }
};

CurrencyField::make('day_rate')->currency('EUR')->live()->afterStateUpdated($recalculate),
NumberStepper::make('nights')->minValue(1)->maxValue(30)->live(debounce: 400)->afterStateUpdated($recalculate),
NumberStepper::make('apa_pct')->suffix('%')->live(debounce: 400)->afterStateUpdated($recalculate),
NumberStepper::make('broker_pct')->suffix('%')->live(debounce: 400)->afterStateUpdated($recalculate),
NumberStepper::make('deal_vat_rate')->suffix('%')->live(debounce: 400)->afterStateUpdated($recalculate),
CurrencyField::make('deal_discount')->currency('EUR')->live()->afterStateUpdated($recalculate),

CurrencyField::make('charter_fee')->currency('EUR')->disabled()->dehydrated(false),
CurrencyField::make('apa')->currency('EUR')->disabled()->dehydrated(false),
CurrencyField::make('deal_subtotal')->currency('EUR')->disabled()->dehydrated(false),
CurrencyField::make('commission')->currency('EUR')->disabled()->dehydrated(false),
CurrencyField::make('deal_vat')->currency('EUR')->disabled()->dehydrated(false),
CurrencyField::make('deal_grand')->currency('EUR')->disabled()->dehydrated(false),
PHP, filename: 'deal-desk.php'),
                ]),

            Section::make('Invoice with VAT')
                ->description('Shorter chain: VAT = subtotal × pct(tax_rate), then grand total = subtotal + VAT − discount.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])
                        ->schema([
                            CurrencyField::make('formula__subtotal')
                                ->label('Subtotal (net)')
                                ->currency('EUR')
                                ->live()
                                ->afterStateUpdated($this->syncInvoiceClosure()),
                            NumberStepper::make('formula__tax_rate')
                                ->label('VAT rate')
                                ->suffix('%')
                                ->minValue(0)
                                ->maxValue(40)
                                ->live(debounce: 400)
                                ->afterStateUpdated($this->syncInvoiceClosure()),
                            CurrencyField::make('formula__discount')
                                ->label('Discount')
                                ->currency('EUR')
                                ->live()
                                ->afterStateUpdated($this->syncInvoiceClosure()),
                            CurrencyField::make('formula__vat')
                                ->label('VAT amount')
                                ->currency('EUR')
                                ->disabled()
                                ->dehydrated(false)
                                ->helperText('round({subtotal}*pct({tax_rate}), 2)'),
                            CurrencyField::make('formula__grand_total')
                                ->label('Grand total')
                                ->currency('EUR')
                                ->disabled()
                                ->dehydrated(false)
                                ->helperText('{subtotal}+{vat}-{discount}')
                                ->columnSpan(['default' => 1, 'sm' => 2, 'lg' => 2]),
                        ]),
                    PlaygroundCodeSnippet::make(<<<'PHP'
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CurrencyField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\NumberStepper;
use Bjanczak\FilamentFlexFields\Support\Intelligence\FormulaEngine;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

$recalculate = function (mixed $state, Set $set, Get $get): void {
    $computed = FormulaEngine::evaluateMap([
        'vat' => 'round({subtotal}*pct({tax_rate}), 2)',
        'grand_total' => '{subtotal}+{vat}-{discount}',
    ], [
        'subtotal' => FormulaEngine::moneyMajor($get('subtotal')),
        'tax_rate' => $get('tax_rate'),
        'discount' => FormulaEngine::moneyMajor($get('discount')),
    ]);

    $set('vat', FormulaEngine::moneyMinor($computed['vat']));
    $set('grand_total', FormulaEngine::moneyMinor($computed['grand_total']));
};

CurrencyField::make('subtotal')->currency('EUR')->live()->afterStateUpdated($recalculate),
NumberStepper::make('tax_rate')->suffix('%')->live(debounce: 400)->afterStateUpdated($recalculate),
CurrencyField::make('discount')->currency('EUR')->live()->afterStateUpdated($recalculate),
CurrencyField::make('vat')->currency('EUR')->disabled()->dehydrated(false),
CurrencyField::make('grand_total')->currency('EUR')->disabled()->dehydrated(false),
PHP, filename: 'invoice-vat.php'),
                ]),

            Section::make('Completion progress')
                ->description('{done} ÷ {goal} × 100 → percent complete, clamped to 0–100.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    Grid::make(['default' => 1, 'sm' => 3])
                        ->schema([
                            NumberStepper::make('formula__done')
                                ->label('Tasks done')
                                ->minValue(0)
                                ->maxValue(100)
                                ->live(debounce: 400)
                                ->afterStateUpdated($this->syncProgressClosure()),
                            NumberStepper::make('formula__goal')
                                ->label('Goal')
                                ->minValue(1)
                                ->maxValue(100)
                                ->live(debounce: 400)
                                ->afterStateUpdated($this->syncProgressClosure()),
                            FlexTextInput::make('formula__progress_pct')
                                ->label('Progress')
                                ->suffix('%')
                                ->disabled()
                                ->dehydrated(false)
                                ->helperText('clamp(round({done}/{goal}*100, 2), 0, 100)'),
                        ]),
                    PlaygroundCodeSnippet::make(<<<'PHP'
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\NumberStepper;
use Bjanczak\FilamentFlexFields\Support\Intelligence\FormulaEngine;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

$recalculate = function (mixed $state, Set $set, Get $get): void {
    $set('progress_pct', (string) FormulaEngine::evaluate(
        'clamp(round({done}/{goal}*100, 2), 0, 100)',
        [
            'done' => $get('done'),
            'goal' => $get('goal'),
        ],
    ));
};

NumberStepper::make('done')->minValue(0)->maxValue(100)->live(debounce: 400)->afterStateUpdated($recalculate),
NumberStepper::make('goal')->minValue(1)->maxValue(100)->live(debounce: 400)->afterStateUpdated($recalculate),
FlexTextInput::make('progress_pct')->suffix('%')->disabled()->dehydrated(false),
PHP, filename: 'progress.php'),
                ]),

            Section::make('Margin')
                ->description('Chained: margin = revenue − cost, then margin % = margin ÷ revenue × 100. Money via CurrencyField.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    Grid::make(['default' => 1, 'sm' => 2, 'lg' => 4])
                        ->schema([
                            CurrencyField::make('formula__revenue')
                                ->label('Revenue')
                                ->currency('EUR')
                                ->live()
                                ->afterStateUpdated($this->syncMarginClosure()),
                            CurrencyField::make('formula__cost')
                                ->label('Cost')
                                ->currency('EUR')
                                ->live()
                                ->afterStateUpdated($this->syncMarginClosure()),
                            CurrencyField::make('formula__margin')
                                ->label('Margin €')
                                ->currency('EUR')
                                ->disabled()
                                ->dehydrated(false)
                                ->helperText('{revenue}-{cost}'),
                            FlexTextInput::make('formula__margin_pct')
                                ->label('Margin %')
                                ->suffix('%')
                                ->disabled()
                                ->dehydrated(false)
                                ->helperText('round({margin}/{revenue}*100, 2)'),
                        ]),
                    PlaygroundCodeSnippet::make(<<<'PHP'
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CurrencyField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Support\Intelligence\FormulaEngine;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

$recalculate = function (mixed $state, Set $set, Get $get): void {
    $computed = FormulaEngine::evaluateMap([
        'margin' => '{revenue}-{cost}',
        'margin_pct' => 'round({margin}/{revenue}*100, 2)',
    ], [
        'revenue' => FormulaEngine::moneyMajor($get('revenue')),
        'cost' => FormulaEngine::moneyMajor($get('cost')),
    ]);

    $set('margin', FormulaEngine::moneyMinor($computed['margin']));
    $set('margin_pct', (string) $computed['margin_pct']);
};

CurrencyField::make('revenue')->currency('EUR')->live()->afterStateUpdated($recalculate),
CurrencyField::make('cost')->currency('EUR')->live()->afterStateUpdated($recalculate),
CurrencyField::make('margin')->currency('EUR')->disabled()->dehydrated(false),
FlexTextInput::make('margin_pct')->suffix('%')->disabled()->dehydrated(false),
PHP, filename: 'margin.php'),
                ]),

            Section::make('Formula lab')
                ->description('Scratchpad: edit the expression and inputs — result, dependency refs, and status update live.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    FlexTextareaField::make('formula__lab_expr')
                        ->label('Expression')
                        ->rows(2)
                        ->live(debounce: 300)
                        ->afterStateUpdated($this->syncLabClosure())
                        ->helperText('Use {a}, {b}, {c}. Allowed: + - * / ( ) and abs/min/max/round/floor/ceil/clamp/sum/avg/pct/pow/sqrt/coalesce/if/nz.'),
                    Grid::make(['default' => 1, 'sm' => 3])
                        ->schema([
                            FlexTextInput::make('formula__lab_a')
                                ->label('{a}')
                                ->numeric()
                                ->live()
                                ->afterStateUpdated($this->syncLabClosure()),
                            FlexTextInput::make('formula__lab_b')
                                ->label('{b}')
                                ->numeric()
                                ->live()
                                ->afterStateUpdated($this->syncLabClosure()),
                            FlexTextInput::make('formula__lab_c')
                                ->label('{c}')
                                ->numeric()
                                ->live()
                                ->afterStateUpdated($this->syncLabClosure()),
                        ]),
                    Grid::make(['default' => 1, 'sm' => 3])
                        ->schema([
                            FlexTextInput::make('formula__lab_result')
                                ->label('Result')
                                ->disabled()
                                ->dehydrated(false),
                            FlexTextInput::make('formula__lab_refs')
                                ->label('fieldReferences()')
                                ->disabled()
                                ->dehydrated(false),
                            FlexTextInput::make('formula__lab_status')
                                ->label('Status')
                                ->disabled()
                                ->dehydrated(false),
                        ]),
                    PlaygroundCodeSnippet::make(<<<'PHP'
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextareaField;
use Bjanczak\FilamentFlexFields\Support\Intelligence\FormulaEngine;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

$recalculate = function (mixed $state, Set $set, Get $get): void {
    $expression = (string) $get('expression');
    $values = [
        'a' => $get('a'),
        'b' => $get('b'),
        'c' => $get('c'),
    ];

    $explained = FormulaEngine::explain($expression, $values);

    $set('refs', implode(', ', $explained['references']));
    $set('result', (string) $explained['result']);
};

FlexTextareaField::make('expression')
    ->rows(2)
    ->live(debounce: 300)
    ->afterStateUpdated($recalculate),
FlexTextInput::make('a')->numeric()->live()->afterStateUpdated($recalculate),
FlexTextInput::make('b')->numeric()->live()->afterStateUpdated($recalculate),
FlexTextInput::make('c')->numeric()->live()->afterStateUpdated($recalculate),
FlexTextInput::make('result')->disabled()->dehydrated(false),
FlexTextInput::make('refs')->disabled()->dehydrated(false),
PHP, filename: 'formula-lab.php'),
                ]),

            Section::make('Cycle guard')
                ->description('FormBuilder refuses cyclic formula graphs. Toggle a safe chain vs a deliberate cycle.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    SelectField::make('formula__cycle_mode')
                        ->label('Graph')
                        ->options([
                            'safe' => 'Safe — vat → total (acyclic)',
                            'cyclic' => 'Cyclic — net ⇄ total (blocked)',
                        ])
                        ->live()
                        ->afterStateUpdated($this->syncCycleClosure()),
                    FlexTextInput::make('formula__cycle_report')
                        ->label('detectCycle()')
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpanFull(),
                    PlaygroundCodeSnippet::make(<<<'PHP'
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Bjanczak\FilamentFlexFields\Support\Intelligence\FormulaEngine;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

SelectField::make('cycle_mode')
    ->options([
        'safe' => 'Safe — vat → total',
        'cyclic' => 'Cyclic — net ⇄ total',
    ])
    ->live()
    ->afterStateUpdated(function (mixed $state, Set $set, Get $get): void {
        $map = $get('cycle_mode') === 'cyclic'
            ? ['net' => '{total}-100', 'total' => '{net}+100']
            : ['vat' => '{subtotal}*pct({tax_rate})', 'total' => '{subtotal}+{vat}'];

        $cycle = FormulaEngine::detectCycle($map);

        $set(
            'cycle_report',
            $cycle === []
                ? 'No cycles — graph is acyclic.'
                : 'Cycle detected: '.implode(' ⇄ ', $cycle),
        );
    }),

FlexTextInput::make('cycle_report')->disabled()->dehydrated(false),
PHP, filename: 'cycle-guard.php'),
                ]),

            Section::make('Security wall')
                ->description('Anything outside whitelist arithmetic is rejected — no JS, no PHP functions, no LLM.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    SelectField::make('formula__reject_expr')
                        ->label('Attempt')
                        ->options([
                            'sin({x})' => 'sin({x}) — blocked function',
                            'eval(1)' => 'eval(1) — blocked',
                            'pct(23)' => 'pct(23) — allowed',
                            'sum(1,2,3)' => 'sum(1,2,3) — allowed',
                            'if(1,10,0)' => 'if(1,10,0) — allowed',
                            'coalesce(0,5)' => 'coalesce(0,5) — allowed',
                            'abs(-12)' => 'abs(-12) — allowed function',
                            '{price} + abc' => '{price} + abc — bare identifier',
                            '{missing}+1' => '{missing}+1 — unknown field',
                            '10/0' => '10/0 — division by zero',
                            'clamp(140,0,100)' => 'clamp(140,0,100) — allowed',
                        ])
                        ->live()
                        ->afterStateUpdated($this->syncRejectClosure()),
                    FlexTextInput::make('formula__reject_status')
                        ->label('Engine response')
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpanFull(),
                    PlaygroundCodeSnippet::make(<<<'PHP'
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Bjanczak\FilamentFlexFields\Support\Intelligence\FormulaEngine;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use InvalidArgumentException;

SelectField::make('attempt')
    ->options([
        'sin({x})' => 'sin({x}) — blocked',
        'pct(23)' => 'pct(23) — allowed',
        'sum(1,2,3)' => 'sum(1,2,3) — allowed',
        'if(1,10,0)' => 'if(1,10,0) — allowed',
        'coalesce(0,5)' => 'coalesce(0,5) — allowed',
        'clamp(140,0,100)' => 'clamp(140,0,100) — allowed',
    ])
    ->live()
    ->afterStateUpdated(function (mixed $state, Set $set, Get $get): void {
        try {
            $result = FormulaEngine::evaluate((string) $get('attempt'), ['x' => 1]);
            $set('status', 'Allowed → '.(string) $result);
        } catch (InvalidArgumentException $exception) {
            $set('status', 'Rejected: '.$exception->getMessage());
        }
    }),

FlexTextInput::make('status')->disabled()->dehydrated(false),
PHP, filename: 'security-wall.php'),
                ]),
        ];
    }

    private function syncDealDeskClosure(): Closure
    {
        return function (mixed $state, Set $set, Get $get): void {
            try {
                $computed = FormulaEngine::evaluateMap([
                    'charter_fee' => '{day_rate}*{nights}',
                    'apa' => '{charter_fee}*pct({apa_pct})',
                    'deal_subtotal' => '{charter_fee}+{apa}',
                    'commission' => '{deal_subtotal}*pct({broker_pct})',
                    'deal_vat' => 'round({deal_subtotal}*pct({deal_vat_rate}), 2)',
                    'deal_grand' => '{deal_subtotal}+{deal_vat}-{deal_discount}',
                ], [
                    'day_rate' => FormulaEngine::moneyMajor($get('formula__day_rate')),
                    'nights' => $get('formula__nights'),
                    'apa_pct' => $get('formula__apa_pct'),
                    'broker_pct' => $get('formula__broker_pct'),
                    'deal_vat_rate' => $get('formula__deal_vat_rate'),
                    'deal_discount' => FormulaEngine::moneyMajor($get('formula__deal_discount')),
                ]);
            } catch (Throwable) {
                foreach ([
                    'formula__charter_fee',
                    'formula__apa',
                    'formula__deal_subtotal',
                    'formula__commission',
                    'formula__deal_vat',
                    'formula__deal_grand',
                ] as $path) {
                    $set($path, null);
                }

                return;
            }

            foreach ($computed as $slug => $value) {
                $set('formula__'.$slug, FormulaEngine::moneyMinor($value));
            }
        };
    }

    private function syncInvoiceClosure(): Closure
    {
        return function (mixed $state, Set $set, Get $get): void {
            try {
                $computed = FormulaEngine::evaluateMap([
                    'vat' => 'round({subtotal}*pct({tax_rate}), 2)',
                    'grand_total' => '{subtotal}+{vat}-{discount}',
                ], [
                    'subtotal' => FormulaEngine::moneyMajor($get('formula__subtotal')),
                    'tax_rate' => $get('formula__tax_rate'),
                    'discount' => FormulaEngine::moneyMajor($get('formula__discount')),
                ]);
            } catch (Throwable) {
                $set('formula__vat', null);
                $set('formula__grand_total', null);

                return;
            }

            $set('formula__vat', FormulaEngine::moneyMinor($computed['vat']));
            $set('formula__grand_total', FormulaEngine::moneyMinor($computed['grand_total']));
        };
    }

    private function syncProgressClosure(): Closure
    {
        return function (mixed $state, Set $set, Get $get): void {
            $goal = (float) ($get('formula__goal') ?? 0);

            if ($goal <= 0) {
                $set('formula__progress_pct', '');

                return;
            }

            $this->setFormulaResult(
                $set,
                'formula__progress_pct',
                'clamp(round({done}/{goal}*100, 2), 0, 100)',
                [
                    'done' => $get('formula__done'),
                    'goal' => $get('formula__goal'),
                ],
            );
        };
    }

    private function syncMarginClosure(): Closure
    {
        return function (mixed $state, Set $set, Get $get): void {
            try {
                $computed = FormulaEngine::evaluateMap([
                    'margin' => '{revenue}-{cost}',
                    'margin_pct' => 'round({margin}/{revenue}*100, 2)',
                ], [
                    'revenue' => FormulaEngine::moneyMajor($get('formula__revenue')),
                    'cost' => FormulaEngine::moneyMajor($get('formula__cost')),
                ]);
            } catch (Throwable) {
                $set('formula__margin', null);
                $set('formula__margin_pct', '');

                return;
            }

            $set('formula__margin', FormulaEngine::moneyMinor($computed['margin']));
            $set('formula__margin_pct', (string) $computed['margin_pct']);
        };
    }

    private function syncLabClosure(): Closure
    {
        return function (mixed $state, Set $set, Get $get): void {
            $expression = trim((string) ($get('formula__lab_expr') ?? ''));
            $values = [
                'a' => $get('formula__lab_a'),
                'b' => $get('formula__lab_b'),
                'c' => $get('formula__lab_c'),
            ];

            if ($expression === '') {
                $set('formula__lab_result', '');
                $set('formula__lab_refs', '');
                $set('formula__lab_status', 'Empty expression');

                return;
            }

            try {
                $refs = FormulaEngine::fieldReferences($expression);
                $set('formula__lab_refs', $refs === [] ? '—' : implode(', ', $refs));
            } catch (Throwable $exception) {
                $set('formula__lab_refs', '');
                $set('formula__lab_result', '');
                $set('formula__lab_status', 'Rejected: '.$exception->getMessage());

                return;
            }

            $result = $this->evaluateFormula($expression, $values);

            if ($result === null) {
                $set('formula__lab_result', '');
                $set('formula__lab_status', $this->describeFormulaFailure($expression, $values));

                return;
            }

            $set('formula__lab_result', (string) $result);
            $set('formula__lab_status', 'OK');
        };
    }

    private function syncCycleClosure(): Closure
    {
        return function (mixed $state, Set $set, Get $get): void {
            $mode = (string) ($get('formula__cycle_mode') ?? 'safe');

            $map = $mode === 'cyclic'
                ? [
                    'net' => '{total}-100',
                    'total' => '{net}+100',
                ]
                : [
                    'vat' => '{subtotal}*pct({tax_rate})',
                    'total' => '{subtotal}+{vat}',
                ];

            $cycle = FormulaEngine::detectCycle($map);

            $set(
                'formula__cycle_report',
                $cycle === []
                    ? 'No cycles — graph is acyclic.'
                    : 'Cycle detected: '.implode(' ⇄ ', $cycle),
            );
        };
    }

    private function syncRejectClosure(): Closure
    {
        return function (mixed $state, Set $set, Get $get): void {
            $expression = (string) ($get('formula__reject_expr') ?? '');

            try {
                $result = FormulaEngine::evaluate($expression, ['x' => 1, 'price' => 10]);
                $set('formula__reject_status', 'Allowed → '.(string) $result);
            } catch (Throwable $exception) {
                $set('formula__reject_status', 'Rejected: '.$exception->getMessage());
            }
        };
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function setFormulaResult(Set $set, string $statePath, string $expression, array $values): void
    {
        $result = $this->evaluateFormula($expression, $values);

        $set($statePath, $result === null ? '' : (string) $result);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function evaluateFormula(string $expression, array $values): float|int|null
    {
        try {
            $normalized = [];

            foreach ($values as $key => $value) {
                $normalized[$key] = is_numeric($value) ? $value + 0 : 0;
            }

            $result = FormulaEngine::evaluate($expression, $normalized);

            if (is_float($result)) {
                return round($result, 2);
            }

            return $result;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function describeFormulaFailure(string $expression, array $values): string
    {
        try {
            $normalized = [];

            foreach ($values as $key => $value) {
                $normalized[$key] = is_numeric($value) ? $value + 0 : 0;
            }

            FormulaEngine::evaluate($expression, $normalized);

            return 'Rejected';
        } catch (Throwable $exception) {
            return 'Rejected: '.$exception->getMessage();
        }
    }
}
