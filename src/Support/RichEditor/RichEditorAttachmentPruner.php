<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\RichEditor;

use Illuminate\Contracts\Filesystem\Filesystem;

class RichEditorAttachmentPruner
{
    /**
     * @param  list<string>  $masterPaths
     */
    public function deleteMastersWithVariants(Filesystem $disk, array $masterPaths): void
    {
        foreach ($masterPaths as $masterPath) {
            if (! is_string($masterPath) || $masterPath === '') {
                continue;
            }

            $manifest = $this->resolveManifest($disk, $masterPath);

            foreach ($manifest->allPaths() as $path) {
                rescue(fn () => $disk->delete($path), report: false);
            }

            rescue(fn () => $disk->delete($manifest->manifestPath()), report: false);
        }
    }

    protected function resolveManifest(Filesystem $disk, string $masterPath): RichEditorAttachmentManifest
    {
        $manifestPath = RichEditorAttachmentPaths::manifestPath($masterPath);

        if (! rescue(fn (): bool => $disk->exists($manifestPath), false, report: false)) {
            return new RichEditorAttachmentManifest(master: $masterPath);
        }

        $payload = json_decode((string) rescue(fn (): string => $disk->get($manifestPath), '', report: false), true);

        if (! is_array($payload)) {
            return new RichEditorAttachmentManifest(master: $masterPath);
        }

        return RichEditorAttachmentManifest::fromArray($payload)
            ?? new RichEditorAttachmentManifest(master: $masterPath);
    }
}
