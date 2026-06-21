<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexRichEditor;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\RichEditor\FlexRichContentRenderer;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\RichEditor\Plugins\FlexRichEditorBlockImagePlugin;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\RichEditor\Plugins\FlexRichEditorPasteCleanupPlugin;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\RichEditor\Plugins\FlexRichEditorYoutubePlugin;
use Closure;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\ToolbarButtonGroup;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * @mixin FlexRichEditor
 */
trait InteractsWithFlexRichEditorEnhancements
{
    protected int|Closure|null $flexRichEditorMinCharacters = null;

    protected int|Closure|null $flexRichEditorMaxCharacters = null;

    protected int|Closure|null $flexRichEditorMaxWords = null;

    protected string|Closure $flexRichEditorLimitBehavior = 'soft';

    protected bool|Closure $flexRichEditorReadingTime = false;

    protected int|Closure $flexRichEditorReadingTimeWordsPerMinute = 200;

    protected bool|Closure|null $flexRichEditorResponsiveImages = null;

    protected bool|Closure $flexRichEditorLazyImages = true;

    protected bool|Closure $flexRichEditorAltTextRequired = false;

    protected string|Closure $flexRichEditorImageSizes = '100vw';

    protected bool|Closure $flexRichEditorFullscreen = false;

    protected bool|Closure $flexRichEditorDistractionFree = false;

    protected int|Closure|null $flexRichEditorAutosaveInterval = null;

    protected string|Closure|null $flexRichEditorAutosaveKey = null;

    protected string|Closure|null $flexRichEditorToolbarRole = null;

    /**
     * @var array<string|Closure>|null
     */
    protected array|Closure|null $flexRichEditorDisabledTools = null;

    protected bool|Closure|string $flexRichEditorPasteCleanup = false;

    public function minCharacters(int|Closure $characters): static
    {
        $this->flexRichEditorMinCharacters = $characters;

        return $this;
    }

    public function maxCharacters(int|Closure $characters): static
    {
        $this->flexRichEditorMaxCharacters = $characters;

        return $this;
    }

    public function maxWords(int|Closure $words): static
    {
        $this->flexRichEditorMaxWords = $words;

        return $this;
    }

    public function limitBehavior(string|Closure $behavior): static
    {
        $this->flexRichEditorLimitBehavior = $behavior;

        return $this;
    }

    public function readingTime(bool|Closure $condition = true, int|Closure $wordsPerMinute = 200): static
    {
        $this->flexRichEditorReadingTime = $condition;
        $this->flexRichEditorReadingTimeWordsPerMinute = $wordsPerMinute;

        return $this;
    }

    public function responsiveImages(bool|Closure $condition = true): static
    {
        $this->flexRichEditorResponsiveImages = $condition;

        return $this;
    }

    public function lazyImages(bool|Closure $condition = true): static
    {
        $this->flexRichEditorLazyImages = $condition;

        return $this;
    }

    public function altTextRequired(bool|Closure $condition = true): static
    {
        $this->flexRichEditorAltTextRequired = $condition;

        return $this;
    }

    public function imageSizes(string|Closure $sizes = '100vw'): static
    {
        $this->flexRichEditorImageSizes = $sizes;

        return $this;
    }

    public function fullscreen(bool|Closure $condition = true): static
    {
        $this->flexRichEditorFullscreen = $condition;

        return $this;
    }

    public function distractionFree(bool|Closure $condition = true): static
    {
        $this->flexRichEditorDistractionFree = $condition;

        return $this;
    }

    public function autosave(int|Closure $intervalSeconds = 30, string|Closure|null $key = null): static
    {
        $this->flexRichEditorAutosaveInterval = $intervalSeconds;
        $this->flexRichEditorAutosaveKey = $key;

        return $this;
    }

    public function toolbarForRole(string|Closure $role): static
    {
        $this->flexRichEditorToolbarRole = $role;

        $this->toolbarButtons(function (): array {
            $resolvedRole = $this->getRichEditorToolbarRole();

            if ($resolvedRole === null) {
                return $this->getFlexDefaultToolbarButtons();
            }

            return $this->getToolbarButtonsForRole($resolvedRole);
        });

        return $this;
    }

    public function toolbarForAuthor(): static
    {
        return $this->toolbarForRole('author');
    }

    public function toolbarForEditor(): static
    {
        return $this->toolbarForRole('editor');
    }

    public function toolbarForAdmin(): static
    {
        return $this->toolbarForRole('admin');
    }

    /**
     * @param  array<string>|Closure  $tools
     */
    public function disabledTools(array|Closure $tools): static
    {
        $this->flexRichEditorDisabledTools = $tools;

        return $this;
    }

    public function pasteCleanup(bool|Closure|string $mode = true): static
    {
        $this->flexRichEditorPasteCleanup = $mode;

        return $this;
    }

    public function getMinCharacters(): ?int
    {
        $value = $this->evaluate($this->flexRichEditorMinCharacters);

        return is_int($value) ? $value : null;
    }

    public function getMaxCharacters(): ?int
    {
        $value = $this->evaluate($this->flexRichEditorMaxCharacters);

        return is_int($value) ? $value : null;
    }

    public function getMaxWords(): ?int
    {
        $value = $this->evaluate($this->flexRichEditorMaxWords);

        return is_int($value) ? $value : null;
    }

    public function getLimitBehavior(): string
    {
        $behavior = (string) $this->evaluate($this->flexRichEditorLimitBehavior);

        if (! in_array($behavior, ['soft', 'hard'], true)) {
            throw new InvalidArgumentException("Invalid rich editor limit behavior [{$behavior}].");
        }

        return $behavior;
    }

    public function shouldShowReadingTime(): bool
    {
        return (bool) $this->evaluate($this->flexRichEditorReadingTime);
    }

    public function getReadingTimeWordsPerMinute(): int
    {
        $value = $this->evaluate($this->flexRichEditorReadingTimeWordsPerMinute);

        return is_int($value) && $value > 0 ? $value : 200;
    }

    public function shouldUseResponsiveRichEditorImages(): bool
    {
        if ($this->flexRichEditorResponsiveImages !== null) {
            return (bool) $this->evaluate($this->flexRichEditorResponsiveImages);
        }

        return $this->getFlexRichEditorImageVariants() !== [];
    }

    public function shouldLazyLoadRichEditorImages(): bool
    {
        return (bool) $this->evaluate($this->flexRichEditorLazyImages);
    }

    public function shouldRequireRichEditorAltText(): bool
    {
        return (bool) $this->evaluate($this->flexRichEditorAltTextRequired);
    }

    public function getRichEditorImageSizes(): string
    {
        return (string) $this->evaluate($this->flexRichEditorImageSizes);
    }

    public function shouldEnableRichEditorFullscreen(): bool
    {
        return (bool) $this->evaluate($this->flexRichEditorFullscreen);
    }

    public function shouldEnableRichEditorDistractionFree(): bool
    {
        return (bool) $this->evaluate($this->flexRichEditorDistractionFree);
    }

    public function shouldAutosaveRichEditor(): bool
    {
        return $this->flexRichEditorAutosaveInterval !== null;
    }

    public function getRichEditorAutosaveIntervalSeconds(): int
    {
        $interval = $this->evaluate($this->flexRichEditorAutosaveInterval);

        return is_int($interval) && $interval > 0 ? $interval : 30;
    }

    public function getRichEditorAutosaveKey(): string
    {
        $key = $this->evaluate($this->flexRichEditorAutosaveKey);

        if (is_string($key) && $key !== '') {
            return $key;
        }

        $segments = [
            $this->getStatePath(),
        ];

        $record = $this->getRecord();

        if ($record !== null && $record->getKey() !== null) {
            $segments[] = $record->getMorphClass().':'.$record->getKey();
        } else {
            $segments[] = 'create';
        }

        $userId = auth()->id();

        if ($userId !== null) {
            $segments[] = 'user:'.$userId;
        }

        return implode('::', $segments);
    }

    public function getRichEditorToolbarRole(): ?string
    {
        $role = $this->evaluate($this->flexRichEditorToolbarRole);

        return is_string($role) && $role !== '' ? $role : null;
    }

    /**
     * @return list<string>
     */
    public function getDisabledRichEditorTools(): array
    {
        $tools = $this->evaluate($this->flexRichEditorDisabledTools);

        if (! is_array($tools)) {
            return [];
        }

        return array_values(array_filter(
            $tools,
            fn (mixed $tool): bool => is_string($tool) && $tool !== '',
        ));
    }

    public function getPasteCleanupMode(): ?string
    {
        $mode = $this->evaluate($this->flexRichEditorPasteCleanup);

        if ($mode === false || $mode === null) {
            return null;
        }

        if ($mode === true) {
            return 'standard';
        }

        if ($mode === 'aggressive') {
            return 'aggressive';
        }

        return 'standard';
    }

    public function shouldEnablePasteCleanup(): bool
    {
        return $this->getPasteCleanupMode() !== null;
    }

    public function hasContentLimits(): bool
    {
        return $this->getMinCharacters() !== null
            || $this->getMaxCharacters() !== null
            || $this->getMaxWords() !== null;
    }

    public function shouldShowRichEditorFooter(): bool
    {
        return $this->shouldShowWordCount()
            || $this->shouldShowJsonBadge()
            || $this->hasContentLimits()
            || $this->shouldShowReadingTime()
            || $this->shouldAutosaveRichEditor()
            || $this->shouldRequireRichEditorAltText();
    }

    public function shouldEnableRichEditorChromeSync(): bool
    {
        return $this->shouldAutosaveRichEditor()
            || $this->shouldShowWordCount()
            || $this->shouldShowReadingTime()
            || $this->hasContentLimits()
            || $this->shouldRequireRichEditorAltText()
            || $this->getLimitBehavior() === 'hard';
    }

    /**
     * @return array<string, mixed>
     */
    public function getRichEditorFooterConfigForJs(): array
    {
        return [
            'minCharacters' => $this->getMinCharacters(),
            'maxCharacters' => $this->getMaxCharacters(),
            'maxWords' => $this->getMaxWords(),
            'limitBehavior' => $this->getLimitBehavior(),
            'readingTime' => $this->shouldShowReadingTime(),
            'wordsPerMinute' => $this->getReadingTimeWordsPerMinute(),
            'showWordCount' => $this->shouldShowWordCount(),
            'autosave' => $this->shouldAutosaveRichEditor(),
            'autosaveInterval' => $this->getRichEditorAutosaveIntervalSeconds(),
            'autosaveKey' => $this->getRichEditorAutosaveKey(),
            'altTextRequired' => $this->shouldRequireRichEditorAltText(),
            'pasteCleanupMode' => $this->getPasteCleanupMode(),
            'fullscreen' => $this->shouldEnableRichEditorFullscreen(),
            'distractionFree' => $this->shouldEnableRichEditorDistractionFree(),
            'labels' => [
                'empty' => $this->getWordCountEmptyLabel(),
                'line' => $this->getWordCountLineTemplate(),
                'readingTime' => $this->getReadingTimeLabelTemplate(),
                'limitWarning' => __('filament-flex-fields::default.rich_editor.limits.warning'),
                'limitDanger' => __('filament-flex-fields::default.rich_editor.limits.danger'),
                'autosaveSaving' => __('filament-flex-fields::default.rich_editor.autosave.saving'),
                'autosaveSaved' => __('filament-flex-fields::default.rich_editor.autosave.saved'),
                'autosaveRestorePrompt' => __('filament-flex-fields::default.rich_editor.autosave.restore_prompt'),
                'autosaveRestoreConfirm' => __('filament-flex-fields::default.rich_editor.autosave.restore_confirm'),
                'autosaveRestoreDismiss' => __('filament-flex-fields::default.rich_editor.autosave.restore_dismiss'),
                'altTextMissing' => __('filament-flex-fields::default.rich_editor.alt_text.missing'),
                'fullscreenEnter' => __('filament-flex-fields::default.rich_editor.fullscreen.enter'),
                'fullscreenExit' => __('filament-flex-fields::default.rich_editor.fullscreen.exit'),
            ],
        ];
    }

    public function getReadingTimeLabelTemplate(): string
    {
        $line = __('filament-flex-fields::default.rich_editor.reading_time.line');

        return str_replace(':minutes', '__MINUTES__', $line);
    }

    public function makeFlexRichContentRenderer(mixed $content): FlexRichContentRenderer
    {
        $disk = isset($this->container)
            ? $this->getFileAttachmentsDiskName()
            : (string) config('filament.default_filesystem_disk', 'public');

        $visibility = isset($this->container)
            ? ($this->getFileAttachmentsVisibility() ?? ($disk === 'public' ? 'public' : 'private'))
            : ($disk === 'public' ? 'public' : 'private');

        $renderer = FlexRichContentRenderer::make($content)
            ->fileAttachmentsDisk($disk)
            ->fileAttachmentsVisibility($visibility)
            ->fileAttachmentProvider($this->getFileAttachmentProvider())
            ->imageVariants($this->getFlexRichEditorImageVariants())
            ->responsiveImages($this->shouldUseResponsiveRichEditorImages())
            ->lazyImages($this->shouldLazyLoadRichEditorImages())
            ->imageSizes($this->getRichEditorImageSizes());

        if (isset($this->container)) {
            $renderer->plugins($this->getPlugins());
        }

        return $renderer;
    }

    /**
     * @return array<int, string|array<int, string|ToolbarButtonGroup>>
     */
    public function getToolbarButtonsForRole(string $role): array
    {
        $presets = config('filament-flex-fields.rich_editor.toolbar_roles', []);

        if (! is_array($presets) || ! isset($presets[$role]) || ! is_array($presets[$role])) {
            throw new InvalidArgumentException("Unknown FlexRichEditor toolbar role [{$role}].");
        }

        return $presets[$role];
    }

    /**
     * @param  array<int, string|array<int, string|ToolbarButtonGroup>>  $groups
     * @return array<int, string|array<int, string|ToolbarButtonGroup>>
     */
    protected function filterDisabledRichEditorToolbarButtons(array $groups): array
    {
        $disabled = $this->getDisabledRichEditorTools();

        if ($disabled === []) {
            return $groups;
        }

        return array_values(array_filter(array_map(
            function (array|string|ToolbarButtonGroup $group) use ($disabled): array|string|\Filament\Forms\Components\RichEditor\ToolbarButtonGroup|null {
                if (is_string($group)) {
                    return in_array($group, $disabled, true) ? null : $group;
                }

                if ($group instanceof ToolbarButtonGroup) {
                    // Let the group handle its own filtering internally if needed
                    return $group;
                }

                if (! is_array($group)) {
                    return $group;
                }

                $filtered = array_values(array_filter(
                    $group,
                    fn (mixed $button): bool => ($button instanceof ToolbarButtonGroup)
                        || (is_string($button) && ! in_array($button, $disabled, true)),
                ));

                return $filtered === [] ? null : $filtered;
            },
            $groups,
        )));
    }

    /**
     * @return list<string>
     */
    public function getDistractionFreeHiddenTools(): array
    {
        return [
            'clearFormatting',
            'clearContent',
        ];
    }

    public function registerFlexRichEditorEnhancementHooks(): void
    {
        $this->plugins(function (): array {
            $plugins = [
                FlexRichEditorBlockImagePlugin::make(),
            ];

            if ($this->shouldEnableRichEditorYoutube()) {
                $plugins[] = FlexRichEditorYoutubePlugin::make($this);
            }

            if (! $this->shouldEnablePasteCleanup()) {
                return $plugins;
            }

            $plugins[] = FlexRichEditorPasteCleanupPlugin::make((string) $this->getPasteCleanupMode());

            return $plugins;
        });
    }

    protected function countRichEditorImagesMissingAltText(mixed $content): int
    {
        if (! is_array($content)) {
            return 0;
        }

        $count = 0;

        $walk = function (mixed $node) use (&$walk, &$count): void {
            if (! is_array($node)) {
                return;
            }

            if (($node['type'] ?? null) === 'image') {
                $alt = trim((string) ($node['attrs']['alt'] ?? ''));

                if ($alt === '') {
                    $count++;
                }
            }

            foreach ($node['content'] ?? [] as $child) {
                $walk($child);
            }
        };

        $walk($content);

        return $count;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getLengthValidationRules(): array
    {
        /** @var RichEditor $this */
        $rules = parent::getLengthValidationRules();

        if (filled($maxCharacters = $this->getMaxCharacters())) {
            $rules[] = function (string $attribute, mixed $value, Closure $fail) use ($maxCharacters): void {
                if (blank($value)) {
                    return;
                }

                $textLength = Str::length($this->getTipTapEditor()
                    ->setContent($value)
                    ->getText());

                if ($textLength > $maxCharacters) {
                    $fail(__('filament-flex-fields::default.rich_editor.limits.max_characters', [
                        'max' => $maxCharacters,
                    ]));
                }
            };
        }

        if (filled($minCharacters = $this->getMinCharacters())) {
            $rules[] = function (string $attribute, mixed $value, Closure $fail) use ($minCharacters): void {
                if (blank($value)) {
                    return;
                }

                $textLength = Str::length($this->getTipTapEditor()
                    ->setContent($value)
                    ->getText());

                if ($textLength < $minCharacters) {
                    $fail(__('filament-flex-fields::default.rich_editor.limits.min_characters', [
                        'min' => $minCharacters,
                    ]));
                }
            };
        }

        if (filled($maxWords = $this->getMaxWords())) {
            $rules[] = function (string $attribute, mixed $value, Closure $fail) use ($maxWords): void {
                if (blank($value)) {
                    return;
                }

                $wordCount = $this->countRichEditorWords($value);

                if ($wordCount > $maxWords) {
                    $fail(__('filament-flex-fields::default.rich_editor.limits.max_words', [
                        'max' => $maxWords,
                    ]));
                }
            };
        }

        if ($this->shouldRequireRichEditorAltText()) {
            $rules[] = function (string $attribute, mixed $value, Closure $fail): void {
                if (blank($value)) {
                    return;
                }

                $missing = $this->countRichEditorImagesMissingAltText($value);

                if ($missing > 0) {
                    $fail(__('filament-flex-fields::default.rich_editor.alt_text.validation', [
                        'count' => $missing,
                    ]));
                }
            };
        }

        return $rules;
    }

    protected function countRichEditorWords(mixed $content): int
    {
        $text = trim($this->getTipTapEditor()
            ->setContent($content)
            ->getText());

        if ($text === '') {
            return 0;
        }

        return count(preg_split('/\s+/u', $text, flags: PREG_SPLIT_NO_EMPTY) ?: []);
    }
}
