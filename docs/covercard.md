# CoverCard

[← Back to Table of Contents](index.md)


### Summary

SaaS-style **media card** for hero banners, product tiles, and CTA blocks. Supports background image, gradient, or solid color, optional top/footer copy, and a footer action button.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Schemas\Components\CoverCard` |
| **State** | None — display/action component (footer action may use Livewire) |
| **Extends** | `Filament\Schemas\Components\Component` |

### Basic usage

#### Portrait product card

```php
use Bjanczak\FilamentFlexFields\Filament\Actions\Action;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\CoverCard;
use Filament\Notifications\Notification;

CoverCard::make()
    ->backgroundImage('https://cdn.example.com/yacht.jpg')
    ->backgroundColor('#e4e4e7')
    ->ratio('3:4')
    ->topTitle('Azimut 55 Fly')
    ->topDescription('Mediterranean charter')
    ->footerTitle('From €4,900 / week')
    ->footerDescription('Early booking discount')
    ->footerAction(
        Action::make('book')
            ->label('Book now')
            ->action(fn () => Notification::make()->title('Booking started')->success()->send()),
    );
```

#### Full-width low banner

Use a **wide aspect ratio** with `fullWidth()` — height follows width, not a fixed pixel height.

```php
CoverCard::make()
    ->backgroundImage('https://cdn.example.com/harbor.jpg')
    ->backgroundColor('#0f172a')
    ->ratio('21:9')   // cinematic banner; try '3:1' for a thinner strip
    ->tone('light')
    ->fullWidth()
    ->columnSpanFull()
    ->topTitle('Charter season 2026')
    ->footerTitle('Early booking')
    ->footerDescription('Save 15% before March')
    ->footerAction(Action::make('explore')->label('Explore fleet'));
```

#### Gradient strip (no image)

```php
CoverCard::make()
    ->backgroundGradient('linear-gradient(90deg, rgb(15 23 42) 0%, rgb(30 58 138) 50%, rgb(14 116 144) 100%)')
    ->ratio('3:1')
    ->tone('light')
    ->fullWidth()
    ->footerTitle('Launch week')
    ->footerDescription('Limited offer');
```

### Configuration API

#### Background


| Method | Description |
|--------|-------------|
| `backgroundImage(string\|Closure\|null $url)` | Cover image URL. Sanitized via `SafeMediaUrl` (`javascript:` etc. rejected). Takes precedence over gradient for `background-image`. |
| `backgroundGradient(string\|Closure\|null $gradient)` | CSS gradient string when no image is set. |
| `backgroundColor(string\|Closure\|null $color)` | Fallback / underlay color. |
| `backgroundPosition(string\|Closure $position)` | CSS `background-position`. Default: `center`. |

#### Layout & sizing


| Method | Description |
|--------|-------------|
| `ratio(string\|Closure\|null $ratio)` | Aspect ratio via inline `aspect-ratio`. Formats: `3:4`, `16:9`, `21:9`, `3:1`, `auto` (no fixed ratio). Default: `3:4`. |
| `fullWidth(bool\|Closure $condition = true)` | Removes default `max-width: 20rem` cap (`.is-full-width`). |
| `contentMaxWidth(string\|Closure\|null $width)` | Custom `max-width` when **not** full width (e.g. `'18rem'`). Ignored when `fullWidth()` is true. |

#### Copy blocks


| Method | Description |
|--------|-------------|
| `topTitle()` / `topDescription()` | Header block at top of card (optional). |
| `footerTitle()` / `footerDescription()` | Footer copy block (optional). |
| `footerAction(Action\|Closure\|null $action)` | Filament `Action` rendered as pill CTA in footer. Closure shorthand creates a default labeled action. |

#### Appearance


| Method | Values | Default |
|--------|--------|---------|
| `tone(string\|Closure $tone)` | `dark`, `light` | `dark` |
| `radius(string\|Closure $radius)` | `md`, `lg`, `xl`, `2xl` | `xl` |

#### Content overlays


| Method | Description |
|--------|-------------|
| `contentOverlays(bool\|Closure $condition = true)` | Enables separate top/bottom gradient overlay elements instead of the global scrim. Top overlay renders only when top copy exists; bottom overlay only when footer copy/action exists. |
| `topOverlayGradient(string\|Closure\|null $gradient)` | Custom CSS gradient for the top `::before` overlay. |
| `footerOverlayGradient(string\|Closure\|null $gradient)` | Custom CSS gradient for the bottom `::after` overlay. |

```php
CoverCard::make()
    ->backgroundImage('https://images.pexels.com/photos/33866367/pexels-photo-33866367.jpeg')
    ->contentOverlays()
    ->topTitle('Charter season 2026')
    ->footerTitle('Early booking');
```

### Public helper methods

| Method | Returns |
|--------|---------|
| `getAspectRatioStyle()` | CSS ratio string e.g. `3 / 4`, or `null` when `ratio('auto')` |
| `getBackgroundStyles()` | Inline style fragments for background layer |
| `getFooterAction()` | Registered footer `Action` or `null` |
| `hasTopContent()` / `hasFooterContent()` | Whether copy blocks are filled |
| `hasContentOverlays()` / `shouldShowTopOverlay()` / `shouldShowFooterOverlay()` | Content overlay state |
| `isFullWidth()` | Whether full-width mode is active |

### CSS classes

| Class | Meaning |
|-------|---------|
| `fff-cover-card` | Root card |
| `fff-cover-card--tone-{dark\|light}` | Text/scrim variant |
| `fff-cover-card--radius-{md\|lg\|xl\|2xl}` | Corner radius |
| `is-full-width` | No max-width constraint |
| `fff-cover-card__background` | Background layer |
| `fff-cover-card__scrim` | Global gradient overlay (when `contentOverlays()` is off) |
| `fff-cover-card__overlay--top` / `--bottom` | Separate gradient overlay elements |
| `fff-cover-card__content` | Copy + action container |

### Implementation notes

- Invalid `ratio` or unsupported `tone`/`radius` throws `InvalidArgumentException`.
- Image URLs are escaped in inline styles; unsafe schemes return `null` from `getBackgroundImage()`.
- Playground examples live under **Cover card** in Flex Fields Playground.

---
