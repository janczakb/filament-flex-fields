<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Media;

use Bjanczak\FilamentFlexFields\Support\Enterprise\ObservabilityHooks;

final class CaptureRetentionAuditor
{
    /**
     * @param  list<string>  $items
     */
    public static function record(string $category, array $items, bool $dryRun = false): void
    {
        if ($items === []) {
            return;
        }

        ObservabilityHooks::record(ObservabilityHooks::EVENT_RETENTION_PRUNE, [
            'category' => $category,
            'count' => count($items),
            'dry_run' => $dryRun,
            'items' => array_slice($items, 0, 25),
        ]);
    }
}
