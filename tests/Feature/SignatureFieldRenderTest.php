<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SignatureField;
use Bjanczak\FilamentFlexFields\Support\SignatureStorage;
use Bjanczak\FilamentFlexFields\Support\SignatureSvg;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableSignatureForm;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function (): void {
    TestableSignatureForm::$formSchema = [];
    Storage::fake('local');
});

it('renders signature field shell and alpine configuration', function (): void {
    TestableSignatureForm::$formSchema = [
        SignatureField::make('signature')
            ->required()
            ->minStrokes(2),
    ];

    $html = Livewire::test(TestableSignatureForm::class)->html(false);

    expect($html)
        ->toContain('fff-signature-field')
        ->toContain('signatureFieldFormComponent({')
        ->toContain('validationMessages')
        ->toContain('minStrokes')
        ->toContain('fff-signature__validation')
        ->toContain('modulepreload');
});

it('fails server validation when required signature is empty', function (): void {
    TestableSignatureForm::$formSchema = [
        SignatureField::make('signature')->required(),
    ];

    $component = Livewire::test(TestableSignatureForm::class)
        ->set('data.signature', null);

    expect(fn () => $component->instance()->getSchema('form')->validate())
        ->toThrow(ValidationException::class);
});

it('fails server validation when signature has too few strokes', function (): void {
    TestableSignatureForm::$formSchema = [
        SignatureField::make('signature')->minStrokes(2),
    ];

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 320"><path d="M10,20 L30,40" fill="none" stroke="#18181b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';

    $component = Livewire::test(TestableSignatureForm::class)
        ->set('data.signature', $svg);

    expect(fn () => $component->instance()->getSchema('form')->validate())
        ->toThrow(ValidationException::class);
});

it('passes server validation for a valid signature', function (): void {
    TestableSignatureForm::$formSchema = [
        SignatureField::make('signature')->required()->minStrokes(1),
    ];

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 320"><path d="M10,20 L30,40" fill="none" stroke="#18181b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';

    Livewire::test(TestableSignatureForm::class)
        ->set('data.signature', $svg)
        ->call('save')
        ->assertHasNoErrors();
});

it('dehydrates inline svg to ffstage token when storeToDisk is enabled', function (): void {
    TestableSignatureForm::$formSchema = [
        SignatureField::make('signature')
            ->storeToDisk('signatures/test', 'local'),
    ];

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 320"><path d="M10,20 L30,40" fill="none" stroke="#18181b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';

    $component = Livewire::test(TestableSignatureForm::class)
        ->set('data.signature', $svg);

    $state = $component->instance()->getSchema('form')->getState()['signature'] ?? null;

    expect($state)
        ->toStartWith(SignatureStorage::TOKEN_PREFIX)
        ->and(SignatureStorage::resolve($state, 'local'))
        ->toBe(SignatureSvg::normalize($svg));
});

it('exposes translated alpine validation messages', function (): void {
    $field = SignatureField::make('signature')->minStrokes(2)->maxSizeKb(32);

    $config = $field->getAlpineConfiguration(true);

    expect($config['minStrokes'])->toBe(2)
        ->and($config['maxSizeKb'])->toBe(32)
        ->and($config['required'])->toBeTrue()
        ->and($config['validationMessages']['too_few_strokes'])
        ->toBe(__('filament-flex-fields::default.signature.ui.too_few_strokes', ['min' => 2]))
        ->not->toContain('filament-flex-fields::');
});
