<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Schemas\Components;

use Bjanczak\FilamentFlexFields\Support\Security\SafeMediaUrl;
use Closure;
use Filament\Actions\Action;
use Filament\Schemas\Components\Component;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CoverCard extends Component
{
    protected string $view = 'filament-flex-fields::schemas.components.cover-card';

    protected string|Closure|null $backgroundColor = null;

    protected string|Closure|null $backgroundGradient = null;

    protected string|Closure|null $backgroundImage = null;

    protected string|Closure $backgroundPosition = 'center';

    protected string|Closure|null $ratio = '3:4';

    protected string|Closure|null $topTitle = null;

    protected string|Closure|null $topDescription = null;

    protected string|Closure|null $footerTitle = null;

    protected string|Closure|null $footerDescription = null;

    protected string|Closure $tone = 'dark';

    protected string|Closure $radius = 'xl';

    protected bool|Closure $coverFullWidth = false;

    protected string|Closure|null $contentMaxWidth = null;

    protected bool|Closure $contentOverlays = false;

    protected string|Closure|null $topOverlayGradient = null;

    protected string|Closure|null $footerOverlayGradient = null;

    public static function make(): static
    {
        $static = app(static::class);
        $static->configure();

        return $static;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->gap(false);
        $this->columns(1);
    }

    public function backgroundColor(string|Closure|null $color): static
    {
        $this->backgroundColor = $color;

        return $this;
    }

    public function backgroundGradient(string|Closure|null $gradient): static
    {
        $this->backgroundGradient = $gradient;

        return $this;
    }

    public function backgroundImage(string|Closure|null $url): static
    {
        $this->backgroundImage = $url;

        return $this;
    }

    public function backgroundPosition(string|Closure $position): static
    {
        $this->backgroundPosition = $position;

        return $this;
    }

    public function ratio(string|Closure|null $ratio): static
    {
        $this->ratio = $ratio;

        return $this;
    }

    public function topTitle(string|Closure|null $title): static
    {
        $this->topTitle = $title;

        return $this;
    }

    public function topDescription(string|Closure|null $description): static
    {
        $this->topDescription = $description;

        return $this;
    }

    public function footerTitle(string|Closure|null $title): static
    {
        $this->footerTitle = $title;

        return $this;
    }

    public function footerDescription(string|Closure|null $description): static
    {
        $this->footerDescription = $description;

        return $this;
    }

    public function tone(string|Closure $tone): static
    {
        $this->tone = $tone;

        return $this;
    }

    public function radius(string|Closure $radius): static
    {
        $this->radius = $radius;

        return $this;
    }

    public function fullWidth(bool|Closure $condition = true): static
    {
        $this->coverFullWidth = $condition;

        return $this;
    }

    public function contentMaxWidth(string|Closure|null $width): static
    {
        $this->contentMaxWidth = $width;

        return $this;
    }

    public function contentOverlays(bool|Closure $condition = true): static
    {
        $this->contentOverlays = $condition;

        return $this;
    }

    public function topOverlayGradient(string|Closure|null $gradient): static
    {
        $this->topOverlayGradient = $gradient;

        return $this;
    }

    public function footerOverlayGradient(string|Closure|null $gradient): static
    {
        $this->footerOverlayGradient = $gradient;

        return $this;
    }

    public function footerAction(Action|Closure|null $action): static
    {
        if ($action instanceof Closure) {
            $action = Action::make('footer')
                ->label(__('filament-flex-fields::default.cover_card.action'))
                ->action($action);
        }

        $this->action($action);

        if ($action !== null) {
            $this->assignActionKey($action);
        }

        return $this;
    }

    protected function assignActionKey(Action $action): void
    {
        if ($this->hasStatePath() || filled($this->evaluate($this->key))) {
            return;
        }

        $this->key(Str::kebab($action->getName()));
    }

    public function getFooterAction(): ?Action
    {
        return $this->getAction();
    }

    public function getBackgroundColor(): ?string
    {
        $color = $this->evaluate($this->backgroundColor);

        return filled($color) ? (string) $color : null;
    }

    public function getBackgroundGradient(): ?string
    {
        $gradient = $this->evaluate($this->backgroundGradient);

        return filled($gradient) ? (string) $gradient : null;
    }

    public function getBackgroundImage(): ?string
    {
        $image = $this->evaluate($this->backgroundImage);

        if (! filled($image)) {
            return null;
        }

        return SafeMediaUrl::sanitize((string) $image);
    }

    public function getBackgroundPosition(): string
    {
        return (string) $this->evaluate($this->backgroundPosition);
    }

    public function getRatio(): ?string
    {
        $ratio = $this->evaluate($this->ratio);

        if ($ratio === null || $ratio === '' || $ratio === 'auto') {
            return null;
        }

        return (string) $ratio;
    }

    public function getAspectRatioStyle(): ?string
    {
        $ratio = $this->getRatio();

        if ($ratio === null) {
            return null;
        }

        if (str_contains($ratio, ':')) {
            [$width, $height] = array_pad(explode(':', $ratio, 2), 2, null);

            if (! is_numeric($width) || ! is_numeric($height) || (float) $height <= 0) {
                throw new InvalidArgumentException("Invalid cover card ratio [{$ratio}]. Use formats like 3:4 or 16:9.");
            }

            return "{$width} / {$height}";
        }

        if (str_contains($ratio, '/')) {
            return $ratio;
        }

        if (is_numeric($ratio) && (float) $ratio > 0) {
            return (string) $ratio;
        }

        throw new InvalidArgumentException("Invalid cover card ratio [{$ratio}]. Use formats like 3:4 or 16:9.");
    }

    /**
     * @return array<int, string>
     */
    public function getBackgroundStyles(): array
    {
        $styles = [];

        $image = $this->getBackgroundImage();
        $gradient = $this->getBackgroundGradient();
        $color = $this->getBackgroundColor();

        if ($image !== null) {
            $styles[] = "background-image: url('".str_replace("'", "\\'", $image)."')";
            $styles[] = 'background-size: cover';
            $styles[] = 'background-position: '.$this->getBackgroundPosition();
            $styles[] = 'background-repeat: no-repeat';
        } elseif ($gradient !== null) {
            $styles[] = 'background-image: '.$gradient;
        }

        if ($color !== null) {
            $styles[] = 'background-color: '.$color;
        }

        return $styles;
    }

    public function getTopTitle(): ?string
    {
        $title = $this->evaluate($this->topTitle);

        return filled($title) ? (string) $title : null;
    }

    public function getTopDescription(): ?string
    {
        $description = $this->evaluate($this->topDescription);

        return filled($description) ? (string) $description : null;
    }

    public function getFooterTitle(): ?string
    {
        $title = $this->evaluate($this->footerTitle);

        return filled($title) ? (string) $title : null;
    }

    public function getFooterDescription(): ?string
    {
        $description = $this->evaluate($this->footerDescription);

        return filled($description) ? (string) $description : null;
    }

    public function getTone(): string
    {
        $tone = (string) $this->evaluate($this->tone);

        if (! in_array($tone, ['dark', 'light'], true)) {
            throw new InvalidArgumentException("Cover card tone [{$tone}] is not supported.");
        }

        return $tone;
    }

    public function getRadius(): string
    {
        $radius = (string) $this->evaluate($this->radius);

        if (! in_array($radius, ['md', 'lg', 'xl', '2xl'], true)) {
            throw new InvalidArgumentException("Cover card radius [{$radius}] is not supported.");
        }

        return $radius;
    }

    public function isFullWidth(): bool
    {
        return (bool) $this->evaluate($this->coverFullWidth);
    }

    public function getContentMaxWidth(): ?string
    {
        $width = $this->evaluate($this->contentMaxWidth);

        return filled($width) ? (string) $width : null;
    }

    public function hasTopContent(): bool
    {
        return filled($this->getTopTitle()) || filled($this->getTopDescription());
    }

    public function hasFooterCopy(): bool
    {
        return filled($this->getFooterTitle()) || filled($this->getFooterDescription());
    }

    public function hasFooterAction(): bool
    {
        return $this->getFooterAction() !== null;
    }

    public function hasFooterContent(): bool
    {
        return $this->hasFooterCopy() || $this->hasFooterAction();
    }

    public function hasContentOverlays(): bool
    {
        return (bool) $this->evaluate($this->contentOverlays);
    }

    public function shouldShowTopOverlay(): bool
    {
        return $this->hasContentOverlays() && $this->hasTopContent();
    }

    public function shouldShowFooterOverlay(): bool
    {
        return $this->hasContentOverlays() && $this->hasFooterContent();
    }

    public function getTopOverlayGradient(): string
    {
        $gradient = $this->evaluate($this->topOverlayGradient);

        if (filled($gradient)) {
            return (string) $gradient;
        }

        return 'linear-gradient(180deg, #00000036 0%, #00000021 42%, #00000000 100%)';
    }

    public function getFooterOverlayGradient(): string
    {
        $gradient = $this->evaluate($this->footerOverlayGradient);

        if (filled($gradient)) {
            return (string) $gradient;
        }

        return 'linear-gradient(0deg, #00000036 0%, #00000021 42%, #00000000 100%)';
    }

    public function hasCustomTopOverlayGradient(): bool
    {
        return filled($this->evaluate($this->topOverlayGradient));
    }

    public function hasCustomFooterOverlayGradient(): bool
    {
        return filled($this->evaluate($this->footerOverlayGradient));
    }
}
