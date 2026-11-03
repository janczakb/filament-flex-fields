<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground\TextInput;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\IconPickerField;
use Bjanczak\FilamentFlexFields\Support\FlexIcon;
use Bjanczak\FilamentFlexFields\Support\TextInput\TextInputOptions;
use Filament\Actions\Action;
use Filament\Support\RawJs;

/** The demo consumes the option contract, so new choices cannot silently miss its gallery. */
final class TextInputDemo
{
    /** @return array<string, list<array<string, mixed>>> */
    public static function groups(): array
    {
        $groups = array_fill_keys(['overview', 'native', 'counters', 'editing', 'actions', 'password', 'tools', 'assistance', 'appearance'], []);
        $add = function (string $group, string $key, string $label, array $calls, mixed $value = 'Hello FLEX') use (&$groups): void {
            $groups[$group][] = ['key' => 'flex_text_input__'.$key, 'label' => $label, 'calls' => $calls, 'value' => $value];
        };
        $add('overview', 'basic', 'Text · emoji · dictation', ['placeholder' => 'Type something…', 'maxLength' => 80, 'characterCounter' => true, 'emojiPicker' => true, 'speechDictation' => true], 'Styled single-line input');
        $add('overview', 'email', 'Email · verification', ['email' => true, 'autofocus' => false, 'caretOnFocus' => 'end', 'autocomplete' => 'off', 'prefixIcon' => FlexIcon::Envelope, 'verificationStatus' => 'Verified', 'verificationStatusIcon' => FlexIcon::SealCheck, 'verificationStatusColor' => 'success'], 'hello@example.com');
        $add('overview', 'password', 'Password · native reveal', ['password' => true, 'autocomplete' => 'new-password', 'revealable' => true, 'passwordStrength' => true], 'Fern!82Violet#47');
        $add('overview', 'domain', 'Inline affixes', ['prefix' => 'https://', 'suffix' => '.com'], 'acme');
        $add('overview', 'slug', 'Clear · native live validation', ['placeholder' => 'my-product-name', 'maxLength' => 60, 'characterCounter' => true, 'clearable' => true, 'live' => [false, 500], 'validating' => true], 'my-product-name');
        $add('overview', 'unicode', 'Graphemes · family emoji = 1', ['counterMode' => 'graphemes', 'characterCounter' => true, 'counterDisplay' => 'both', 'softLimit' => 24, 'emojiPicker' => true, 'clearable' => true], '👨‍👩‍👧‍👦 Hello');
        $add('native', 'numeric', 'Native integer · 0–100', ['integer' => true, 'minValue' => 0, 'maxValue' => 100, 'required' => true, 'enterKeyHint' => 'done'], 42);
        $add('native', 'decimal', 'Numeric · step .01', ['numeric' => true, 'step' => .01, 'minValue' => 0, 'maxValue' => 100], '12.50');
        $add('native', 'telephone', 'Native telephone', ['tel' => true, 'autocomplete' => 'tel'], '+48 123 456 789');
        $add('native', 'url', 'Native URL', ['url' => true, 'autocomplete' => 'url'], 'https://example.com');
        $add('native', 'type', 'Explicit native type', ['type' => 'search', 'placeholder' => 'Search…']);
        $add('native', 'masked', 'Native mask · postal code', ['mask' => '99-999', 'stripCharacters' => '-'], '00-001');
        $add('native', 'money_mask', 'Native dynamic mask', ['mask' => RawJs::make('$money($input)'), 'numeric' => true, 'stripCharacters' => ','], '1,234.50');
        $add('native', 'datalist', 'Native datalist', ['datalist' => ['Laravel', 'Filament', 'Livewire']], 'Filament');
        $add('native', 'disabled', 'Disabled · tools revoked', ['disabled' => true, 'emojiPicker' => true, 'speechDictation' => true, 'clearable' => true], 'Disabled value');
        $add('native', 'readonly', 'Read only · copy remains available', ['readOnly' => true, 'copyable' => true], 'Read-only value');
        $add('native', 'native_affixes', 'Native non-inline affixes', ['prefix' => ['https://', false], 'suffix' => ['.com', false], 'inlinePrefix' => false, 'inlineSuffix' => false], 'acme');
        $add('native', 'native_icons', 'Native icons & colors', ['prefixIcon' => [FlexIcon::Envelope, false], 'suffixIcon' => [FlexIcon::Check, false], 'prefixIconColor' => 'primary', 'suffixIconColor' => 'success']);
        $add('native', 'attributes', 'Native attributes · lazy state', ['extraInputAttributes' => ['data-demo-native' => 'true', 'aria-describedby' => 'native-demo-help'], 'extraAlpineAttributes' => ['data-demo-alpine' => 'true'], 'lazy' => true, 'autocapitalize' => 'sentences', 'autocomplete' => 'off', 'inputMode' => 'text', 'minLength' => 2, 'maxLength' => 80, 'spellcheck' => true, 'helperText' => 'Native state, validation and accessibility attributes.']);
        $add('native', 'rules', 'Native rules · transformation', ['rules' => ['alpha_dash'], 'trim' => true, 'dehydrateStateUsing' => fn (mixed $state): string => strtolower(trim((string) $state))], 'FLEX_value');
        $add('password', 'hold_password', 'Hold to reveal · conceals on blur', ['password' => true, 'autocomplete' => 'new-password', 'revealable' => true, 'revealMode' => 'hold', 'concealOnBlur' => true, 'capsLockIndicator' => true, 'passwordStrength' => true], 'Fern!82Violet#47');
        $localAction = fn (string $name, string $label, string $icon): Action => Action::make($name)->label($label)->tooltip($label)->icon($icon)
            ->livewireClickHandlerEnabled(false)->alpineClickHandler('showStatus('.e(json_encode($label, JSON_THROW_ON_ERROR)).')');
        $add('actions', 'custom_actions', 'Native prefix & suffix actions', [
            'prefixAction' => $localAction('prefixHelp', 'Prefix action activated', FlexIcon::Envelope),
            'suffixAction' => $localAction('suffixHelp', 'Suffix action activated', FlexIcon::Copy), 'clearable' => true,
            'helperText' => 'Both native actions show local feedback; they do not submit or refresh this demo.',
        ]);
        $add('actions', 'action_collections', 'Native action collections', [
            'prefixActions' => [$localAction('collectionPrefix', 'Prefix collection action activated', FlexIcon::Envelope)],
            'suffixActions' => [$localAction('collectionSuffix', 'Suffix collection action activated', FlexIcon::Copy)],
        ]);
        $add('tools', 'custom_tool_icons', 'Custom tool icons & labels', ['emojiPicker' => true, 'speechDictation' => true, 'clearable' => true, 'emojiIcon' => FlexIcon::LookTop, 'microphoneIcon' => FlexIcon::Mic01, 'clearIcon' => FlexIcon::MultiplicationSign, 'emojiPickerLabel' => __('filament-flex-fields::text-input.emoji'), 'speechDictationLabel' => __('filament-flex-fields::text-input.speak'), 'emojiPickerLocale' => 'en', 'speechDictationLanguage' => 'en-US']);
        $add('actions', 'custom_copy', 'Custom copy feedback', ['copyable' => [true, 'Copied!', 1200], 'copyIcon' => FlexIcon::Copy, 'copyFeedback' => 'both']);
        $add('password', 'custom_password', 'Custom strength labels & reveal icons', ['password' => true, 'autocomplete' => 'new-password', 'revealable' => true, 'passwordStrength' => true, 'passwordStrengthLabels' => ['very_weak' => '0/4', 'weak' => '1/4', 'fair' => '2/4', 'good' => '3/4', 'strong' => '4/4'], 'showPasswordIcon' => FlexIcon::View, 'hidePasswordIcon' => FlexIcon::ViewOff], 'Fern!82Violet#47');
        $add('actions', 'loading', 'Inline loading · no empty toolbar', ['loading' => true, 'live' => [false, 500], 'clearable' => true]);
        $frameworks = [
            ['value' => 'Laravel', 'label' => 'Laravel', 'description' => 'PHP framework'],
            ['value' => 'Livewire', 'label' => 'Livewire', 'description' => 'Reactive UI'],
            ['value' => 'Filament', 'label' => 'Filament', 'description' => 'Admin panel'],
            ['value' => 'Symfony', 'label' => 'Symfony · disabled example', 'description' => 'Disabled options cannot be selected.', 'disabled' => true],
        ];
        $cities = [
            ['value' => 'Amsterdam', 'label' => 'Amsterdam', 'description' => 'Netherlands'],
            ['value' => 'Athens', 'label' => 'Athens', 'description' => 'Greece'],
            ['value' => 'Berlin', 'label' => 'Berlin', 'description' => 'Germany'],
            ['value' => 'Lisbon', 'label' => 'Lisbon', 'description' => 'Portugal'],
            ['value' => 'London', 'label' => 'London', 'description' => 'United Kingdom'],
            ['value' => 'Paris', 'label' => 'Paris', 'description' => 'France'],
            ['value' => 'Warsaw', 'label' => 'Warsaw', 'description' => 'Poland'],
        ];
        $searchCities = static fn (string $search): array => array_values(array_filter($cities,
            static fn (array $city): bool => str_contains(strtolower($city['label']), strtolower($search))));
        $add('assistance', 'suggestions', 'Local suggestions · descriptions below', [
            'suggestions' => $frameworks, 'suggestionMinLength' => 0, 'placeholder' => 'Click to browse frameworks…',
        ], '');
        $add('assistance', 'suggestions_trailing', 'Local suggestions · trailing descriptions', [
            'suggestions' => $frameworks, 'suggestionDescriptionPosition' => 'trailing', 'suggestionMinLength' => 0,
            'placeholder' => 'Click to browse frameworks…',
        ], '');
        $add('assistance', 'async_suggestions', 'Async suggestions · descriptions below', [
            'suggestionsUsing' => $searchCities, 'suggestionMinLength' => 1, 'placeholder' => 'Type a city, e.g. a…',
        ], '');
        $add('assistance', 'async_suggestions_trailing', 'Async suggestions · trailing descriptions', [
            'suggestionsUsing' => $searchCities, 'suggestionDescriptionPosition' => 'trailing', 'suggestionMinLength' => 1,
            'placeholder' => 'Type a city, e.g. a…',
        ], '');
        $add('assistance', 'verification', 'Async verification · latest result wins', ['verifyUsing' => fn (string $value): array => ['state' => filter_var($value, FILTER_VALIDATE_EMAIL) ? 'success' : 'error', 'message' => filter_var($value, FILTER_VALIDATE_EMAIL) ? 'Valid email format' : 'Enter a valid email address'], 'verificationDebounce' => 300, 'verificationMinLength' => 3], '');
        $add('tools', 'associated_icon', 'Text & icon · Hugeicons', ['iconPicker' => ['associated_icon', fn (IconPickerField $picker) => $picker->label('Choose associated icon')->sets(['hugeicons'])->perPage(48)->clearable()]], 'Contact us');
        $add('tools', 'curated_icon', 'Text & icon · curated libraries', ['iconPicker' => ['curated_icon', fn (IconPickerField $picker) => $picker->label('Choose curated icon')->sets(['heroicons'])->icons(['heroicon-o-star', 'heroicon-o-heart', 'heroicon-o-envelope'])->gridColumns(3)->perPage(12)]], 'Featured');
        $add('tools', 'dynamic_tools', 'Dynamic capability revocation · type off/on', ['emojiPicker' => fn (mixed $state): bool => $state !== 'off', 'speechDictation' => fn (mixed $state): bool => $state !== 'off', 'live' => [false, 300], 'helperText' => 'Type off to disable both tools; on restores them.'], 'on');
        foreach (TextInputOptions::DEFAULTS as $method => $default) {
            $group = self::optionGroup($method);
            $choices = TextInputOptions::CHOICES[$method] ?? (is_bool($default) ? [false, true] : [self::exampleValue($method, $default)]);
            foreach ($choices as $index => $choice) {
                $key = $method.'_'.$index;
                $calls = self::dependencies($group, $key);
                $calls[$method] = $choice;
                if ($method === 'suggestionDescriptionPosition') {
                    $calls['suggestions'] = $frameworks;
                    $calls['suggestionMinLength'] = 0;
                }
                if ($method === 'toolsOrder' || $method === 'toolsVisibility') {
                    $calls['emojiPicker'] = true;
                    $calls['speechDictation'] = true;
                }
                if (str_starts_with($method, 'verification')) {
                    $calls['verifyUsing'] = fn (string $value): array => ['state' => 'success', 'message' => 'Checked: '.$value];
                }
                // Nullable label defaults resolve to translated text; setters receive their public types.
                if (in_array($method, ['clearLabel', 'clearConfirmationLabel', 'copyErrorLabel'], true) && $choice === null) {
                    $calls[$method] = __('filament-flex-fields::text-input.'.match ($method) {
                        'clearLabel' => 'clear', 'clearConfirmationLabel' => 'clear_confirm', default => 'copy_error'
                    });
                }
                $label = $method.'('.(is_array($choice) ? '…' : (is_bool($choice) ? ($choice ? 'true' : 'false') : ($choice ?? 'null'))).')';
                $value = match ($group) {
                    'password' => 'Fern!82Violet#47', 'counters' => '👨‍👩‍👧‍👦 Hello', 'assistance' => '', default => 'Hello FLEX'
                };
                $add($group, $key, $label, $calls, $value);
            }
        }
        foreach (['primary', 'secondary', 'soft', 'flat'] as $variant) {
            $add('appearance', $variant, 'variant('.$variant.')', ['variant' => $variant, 'emojiPicker' => true], $variant.' input');
        }
        foreach (['sm', 'md', 'lg'] as $size) {
            $add('appearance', $size, 'size('.$size.')', ['size' => $size, 'speechDictation' => true], $size.' input');
        }
        foreach (['native', 'none', 'sm', 'md', 'lg', 'xl', 'full'] as $radius) {
            $add('appearance', 'rounding_'.$radius, 'rounding('.$radius.')', ['rounding' => $radius, 'clearable' => true], $radius.' rounding');
        }
        $add('appearance', 'rtl', 'RTL · Arabic', ['textDirection' => 'rtl', 'characterCounter' => true, 'emojiPicker' => true, 'clearable' => true], 'مرحبا بالعالم');
        $add('appearance', 'focus_off', 'focusOutline(false)', ['focusOutline' => false]);
        $add('editing', 'autofocus', 'Native autofocus · explicit opt-in', ['autofocus' => true]);

        return $groups;
    }

    /** @return array<string, mixed> */
    private static function dependencies(string $group, string $key): array
    {
        if (str_starts_with($key, 'draft')) {
            return ['draftKey' => 'demo-'.$key];
        }

        return match ($group) {
            'counters' => ['characterCounter' => true, 'counterLimit' => 24],
            'password' => ['password' => true, 'autocomplete' => 'new-password', 'revealable' => true, 'passwordStrength' => true, 'passwordRequirements' => ['min' => 12, 'uppercase' => 1, 'lowercase' => 1, 'numbers' => 1, 'symbols' => 1]],
            'actions' => ['clearable' => true, 'copyable' => true],
            'tools' => ['emojiPicker' => true, 'speechDictation' => true],
            'assistance' => ['suggestions' => ['Laravel', 'Livewire', 'Filament']],
            default => [],
        };
    }

    public static function optionGroup(string $method): string
    {
        return match (true) {
            str_starts_with($method, 'counter'), $method === 'softLimit', $method === 'enforceCounterLimit' => 'counters',
            str_starts_with($method, 'clear'), str_starts_with($method, 'copy'), str_starts_with($method, 'tools') => 'actions',
            str_starts_with($method, 'password'), str_starts_with($method, 'reveal'), $method === 'concealOnBlur', $method === 'capsLockIndicator' => 'password',
            str_starts_with($method, 'emoji'), str_starts_with($method, 'speech') => 'tools',
            str_starts_with($method, 'suggestion'), str_starts_with($method, 'draft'), str_starts_with($method, 'verification') => 'assistance',
            $method === 'textDirection' => 'appearance', default => 'editing',
        };
    }

    private static function exampleValue(string $method, mixed $default): mixed
    {
        return match ($method) {
            'softLimit', 'counterLimit' => 12, 'revealTimeout' => 3000,
            'counterTemplate' => '{count} / {limit} · {remaining}', 'counterLocale' => 'pl-PL',
            'clearLabel' => __('filament-flex-fields::text-input.clear'),
            'clearConfirmationLabel' => __('filament-flex-fields::text-input.clear_confirm'),
            'copyErrorLabel' => __('filament-flex-fields::text-input.copy_error'),
            'passwordRequirements' => ['min' => 12, 'uppercase' => 1, 'lowercase' => 1, 'numbers' => 1, 'symbols' => 1],
            'toolsOrder' => ['actions', 'clear', 'emoji', 'dictation'], 'draftKey' => 'demo-text-input',
            'speechDictationMessages' => ['not-allowed' => __('filament-flex-fields::text-input.not-allowed')],
            default => $default,
        };
    }

    /** @param array<string, mixed> $scenario */
    public static function field(array $scenario): FlexTextInput
    {
        // Demo instances are independent: queued wire:model updates must not
        // turn a deferred-tab request into a whole catalog-page render.
        $field = FlexTextInput::make($scenario['key'])->label($scenario['label'])
            ->partiallyRenderAfterStateUpdated()
            ->extraInputAttributes(['data-fff-demo-manual-focus' => 'true']);
        // Advisory requests bundle deferred input updates. Independent remote
        // examples update state without replacing the focused native control.
        if (isset($scenario['calls']['suggestionsUsing']) || isset($scenario['calls']['verifyUsing'])) {
            $field->partiallyRenderAfterStateUpdated(false)->skipRenderAfterStateUpdated();
        }
        foreach ($scenario['calls'] as $method => $value) {
            // Arguments with multiple native positions are explicit, never interpreted as callbacks.
            $multi = in_array($method, ['copyable', 'live', 'prefix', 'suffix', 'prefixIcon', 'suffixIcon', 'iconPicker'], true) && is_array($value);
            $field->{$method}(...($multi ? $value : [$value]));
        }

        return $field;
    }

    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        $values = [];
        foreach (self::groups() as $scenarios) {
            foreach ($scenarios as $scenario) {
                $values[$scenario['key']] = $scenario['value'];
            }
        }

        $values['associated_icon'] = null;
        $values['curated_icon'] = 'heroicon-o-star';

        return $values;
    }
}
