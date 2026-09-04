<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Support\Compliance\CompliancePack;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;

/**
 * @internal Maintainer-only compliance report preview — not registered in playground nav.
 */
class CompliancePackPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        $report = CompliancePack::exportReport();
        $aaSample = array_slice($report['aa_matrix'], 0, 8, true);

        return [
            Section::make('Compliance pack')
                ->description('CompliancePack::exportReport() — field inventory, locales, WCAG 2.2 AA matrix with product baseline pass components, and audit criteria.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    View::make('filament-flex-fields::partials.playground.compliance-pack-demo')
                        ->viewData([
                            'report' => $report,
                            'aaSample' => $aaSample,
                            'aaTotal' => count($report['aa_matrix']),
                        ]),
                ]),
        ];
    }
}
