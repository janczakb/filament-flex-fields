<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\RichEditor;

class RichEditorAttachmentIdResolver
{
    /**
     * @return list<string>
     */
    public static function fromContent(mixed $content): array
    {
        if (blank($content)) {
            return [];
        }

        if (is_string($content)) {
            return self::fromHtml($content);
        }

        if (! is_array($content)) {
            return [];
        }

        $ids = [];

        self::walkNodes($content, $ids);

        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  list<string>  $ids
     */
    protected static function walkNodes(array $node, array &$ids): void
    {
        if (($node['type'] ?? null) === 'image') {
            $id = $node['attrs']['id'] ?? $node['attrs']['data-id'] ?? null;

            if (is_string($id) && $id !== '') {
                $ids[] = $id;
            }
        }

        foreach ($node['content'] ?? [] as $child) {
            if (is_array($child)) {
                self::walkNodes($child, $ids);
            }
        }
    }

    /**
     * @return list<string>
     */
    protected static function fromHtml(string $content): array
    {
        $ids = [];

        if (! preg_match_all('/\bdata-id="([^"]+)"/', $content, $matches)) {
            return [];
        }

        foreach ($matches[1] as $id) {
            if (is_string($id) && $id !== '') {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }
}
