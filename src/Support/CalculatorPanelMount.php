<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

use Illuminate\Support\Facades\View;

class CalculatorPanelMount
{
    protected bool $queued = false;

    protected bool $rendered = false;

    public function requestMount(): void
    {
        $this->queued = true;
    }

    public function renderMarkup(): string
    {
        if (! $this->queued || $this->rendered) {
            return '';
        }

        $this->rendered = true;

        return View::make('filament-flex-fields::partials.calculator-panel-mount')->render();
    }

    public function clear(): void
    {
        $this->queued = false;
        $this->rendered = false;
    }

    public static function queue(): void
    {
        app(self::class)->requestMount();
    }

    public static function renderOnce(): string
    {
        return app(self::class)->renderMarkup();
    }

    public static function reset(): void
    {
        app(self::class)->clear();
    }
}
