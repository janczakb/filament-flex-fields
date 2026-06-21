<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\RichEditor\RichEditorAttachmentManifest;
use Bjanczak\FilamentFlexFields\Support\RichEditor\RichEditorAttachmentManifestRepository;
use Illuminate\Support\Facades\Storage;

it('caches attachment manifest reads within the same request', function () {
    Storage::fake('public');

    $disk = Storage::disk('public');
    $disk->put('uploads/photo.jpg', 'image');
    $disk->put('uploads/photo.flex-variants.json', json_encode([
        'master' => 'uploads/photo.jpg',
        'variants' => [],
    ]));

    $repository = new RichEditorAttachmentManifestRepository;

    $first = $repository->read($disk, 'uploads/photo.jpg');
    $disk->delete('uploads/photo.flex-variants.json');

    $second = $repository->read($disk, 'uploads/photo.jpg');

    expect($first)->toBeInstanceOf(RichEditorAttachmentManifest::class)
        ->and($second)->toBe($first);
});
