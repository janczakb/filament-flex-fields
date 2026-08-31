<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SignatureField;
use Bjanczak\FilamentFlexFields\Support\Media\MediaCaptureOs;
use Bjanczak\FilamentFlexFields\Support\Media\SignatureLegalPack;
use Bjanczak\FilamentFlexFields\Support\Media\VoiceNoteTranscriptionInterface;
use Bjanczak\FilamentFlexFields\Support\Playground\FlexFileUploadPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\SignatureFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\VoiceNoteRecorderFieldPlayground;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableSignatureForm;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableTranslatableForm;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('renders the file upload playground schema without structural errors', function () {
    TestableTranslatableForm::$formSchema = app(FlexFileUploadPlayground::class)->components();

    $html = Livewire::test(TestableTranslatableForm::class)->html(false);

    expect($html)
        ->toContain('fff-flex-file-upload')
        ->toContain('fff-flex-file-upload__source-tabs')
        ->toContain('fff-flex-file-upload__source-panel--url')
        ->toContain('fff-flex-file-upload__source-panel--webcam')
        ->toContain('file_upload__sources');

    expect(substr_count($html, '<div'))
        ->toBe(substr_count($html, '</div>'));
});

it('renders the voice note recorder playground schema without structural errors', function () {
    TestableTranslatableForm::$formSchema = app(VoiceNoteRecorderFieldPlayground::class)->components();

    $html = Livewire::test(TestableTranslatableForm::class)->html(false);

    expect($html)
        ->toContain('fff-voice-recorder')
        ->toContain('voice_note__basic')
        ->toContain('voice_note__immediate');

    expect(substr_count($html, '<div'))
        ->toBe(substr_count($html, '</div>'));
});

it('renders the signature field playground schema without boot exceptions', function () {
    TestableSignatureForm::$formSchema = app(SignatureFieldPlayground::class)->components();

    $html = Livewire::test(TestableSignatureForm::class)->html(false);

    expect($html)
        ->toContain('fff-signature-field')
        ->toContain('signature__contract')
        ->toContain('signature__enterprise')
        ->toContain('signature__validation');

    expect(substr_count($html, '<div'))
        ->toBe(substr_count($html, '</div>'));
});

it('boots signature field with legal pack and timestamp seal hooks', function () {
    TestableSignatureForm::$formSchema = [
        SignatureField::make('signature')
            ->legalPack()
            ->timestampSeal()
            ->legalMetadataIn('signature_legal'),
    ];

    expect(fn () => Livewire::test(TestableSignatureForm::class)->html(false))
        ->not->toThrow(Throwable::class);
});

it('writes legal timestamp seal metadata before signature dehydrate when ink is present', function () {
    $this->travelTo('2026-08-31 12:00:00');

    TestableSignatureForm::$formSchema = [
        SignatureField::make('signature')
            ->timestampSeal()
            ->legalMetadataIn('signature_legal'),
    ];

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 320"><path d="M10,20 L30,40" fill="none" stroke="#18181b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';

    $component = Livewire::test(TestableSignatureForm::class)
        ->set('data.signature', $svg)
        ->set('data.signature_legal', null)
        ->call('save');

    expect($component->get('data.signature_legal'))
        ->toMatchArray(['sealed_at' => '2026-08-31T12:00:00+00:00'])
        ->and($component->get('data.signature_legal'))->toHaveKeys([
            'sealed_at',
            'signer_id',
            'ip_address',
            'user_agent',
            'document_hash',
            'signature_path_count',
        ]);
});

it('rejects empty ink when legal pack is enabled on signature field', function () {
    $field = SignatureField::make('signature')->legalPack()->required();

    $rule = collect($field->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);

    $emptySvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 320"></svg>';

    $message = null;
    $rule('signature', $emptySvg, function (string $failMessage) use (&$message): void {
        $message = $failMessage;
    });

    expect($message)->toBe(__('filament-flex-fields::default.validation.signature.too_few_strokes'));
});

it('appends voice note transcript metadata after persist when transcription is registered', function () {
    Storage::fake('public');

    MediaCaptureOs::registerTranscriptionInterface(new class implements VoiceNoteTranscriptionInterface
    {
        public function transcribe(string $disk, string $path, array $context = []): ?string
        {
            return 'hello from voice note';
        }
    });

    TestableTranslatableForm::$formSchema = [
        Bjanczak\FilamentFlexFields\Filament\Forms\Components\VoiceNoteRecorderField::make('voice_note')
            ->disk('public')
            ->directory('voice-notes')
            ->storeMetadataIn('voice_note_meta'),
    ];

    $file = Illuminate\Http\UploadedFile::fake()->create('voice-note.webm', 100, 'audio/webm');
    $fileKey = (string) Illuminate\Support\Str::uuid();

    $livewire = Livewire::test(TestableTranslatableForm::class)
        ->set('data.voice_note_meta', null)
        ->upload("data.voice_note.{$fileKey}", [$file]);

    /** @var Bjanczak\FilamentFlexFields\Filament\Forms\Components\VoiceNoteRecorderField $field */
    $field = $livewire->instance()->getSchema('form')->getComponent('voice_note');
    $field->saveUploadedFiles();

    $meta = $livewire->get('data.voice_note_meta');
    $storedPath = $field->getState();

    expect($storedPath)->toBeString()
        ->and($meta)->toBeArray()
        ->and($meta['transcript'] ?? null)->toBe('hello from voice note');

    MediaCaptureOs::resetRuntimeState();
});

it('seals signature playground persisted state with legal pack helper', function () {
    $playground = app(SignatureFieldPlayground::class);

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 320"><path d="M10,20 L30,40" fill="none" stroke="#18181b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';

    $sealed = $playground->sealPersistedState([
        'signature__enterprise' => $svg,
    ]);

    expect($sealed)->toHaveKey('_meta')
        ->and(SignatureLegalPack::requiresInk($svg))->toBeTrue();
});
