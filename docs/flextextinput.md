---
title: "FlexTextInput"
description: Native Filament state and validation with modular FLEX editing, Unicode limits and optional tools.
icon: "text-cursor-input"
"canonical": "https://flex-fields.bjanczak.com/docs/flextextinput"
"og:type": "article"
"og:image": "https://flex-fields.bjanczak.com/art/og/flex-text-input-emoji.webp"
"og:image:type": "image/webp"
"og:image:width": "1200"
"og:image:height": "630"
"og:image:alt": "FlexTextInput in the FLEX Fields playground"
"og:video": "https://flex-fields.bjanczak.com/art/previews/flex-text-input-emoji-motion.mp4"
"og:video:secure_url": "https://flex-fields.bjanczak.com/art/previews/flex-text-input-emoji-motion.mp4"
"og:video:type": "video/mp4"
"og:video:width": "1920"
"og:video:height": "1080"
"twitter:image": "https://flex-fields.bjanczak.com/art/og/flex-text-input-emoji.webp"
"twitter:image:alt": "FlexTextInput in the FLEX Fields playground"
---

{/* flex-component-media:start */}
<script type="application/ld+json">
{JSON.stringify({
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "Organization",
      "@id": "https://flex-fields.bjanczak.com/#organization",
      "name": "FLEX Fields",
      "url": "https://flex-fields.bjanczak.com/",
      "logo": "https://flex-fields.bjanczak.com/art/docs-logo-light.svg",
      "sameAs": [
        "https://github.com/janczakb/filament-flex-fields"
      ]
    },
    {
      "@type": "WebSite",
      "@id": "https://flex-fields.bjanczak.com/#website",
      "name": "FLEX Fields",
      "url": "https://flex-fields.bjanczak.com/",
      "publisher": {
        "@id": "https://flex-fields.bjanczak.com/#organization"
      },
      "inLanguage": "en"
    },
    {
      "@type": "WebPage",
      "@id": "https://flex-fields.bjanczak.com/docs/flextextinput#webpage",
      "url": "https://flex-fields.bjanczak.com/docs/flextextinput",
      "name": "FlexTextInput",
      "description": "Native Filament state and validation with modular FLEX editing, Unicode limits and optional tools.",
      "inLanguage": "en",
      "isPartOf": {
        "@id": "https://flex-fields.bjanczak.com/#website"
      },
      "mainEntity": {
        "@id": "https://flex-fields.bjanczak.com/docs/flextextinput#article"
      }
    },
    {
      "@type": "TechArticle",
      "@id": "https://flex-fields.bjanczak.com/docs/flextextinput#article",
      "url": "https://flex-fields.bjanczak.com/docs/flextextinput",
      "headline": "FlexTextInput",
      "description": "Native Filament state and validation with modular FLEX editing, Unicode limits and optional tools.",
      "inLanguage": "en",
      "mainEntityOfPage": {
        "@id": "https://flex-fields.bjanczak.com/docs/flextextinput#webpage"
      },
      "publisher": {
        "@id": "https://flex-fields.bjanczak.com/#organization"
      },
      "video": {
        "@id": "https://flex-fields.bjanczak.com/art/previews/flex-text-input-emoji-motion.mp4#video"
      }
    },
    {
      "@type": "VideoObject",
      "@id": "https://flex-fields.bjanczak.com/art/previews/flex-text-input-emoji-motion.mp4#video",
      "name": "FlexTextInput — FLEX Fields demonstration",
      "description": "A recorded demonstration of FlexTextInput in the FLEX Fields playground for Filament. Native Filament state and validation with modular FLEX editing, Unicode limits and optional tools.",
      "thumbnailUrl": [
        "https://flex-fields.bjanczak.com/art/previews/flex-text-input-emoji.webp"
      ],
      "uploadDate": "2026-10-16T03:10:31+02:00",
      "duration": "PT7.2S",
      "contentUrl": "https://flex-fields.bjanczak.com/art/previews/flex-text-input-emoji-motion.mp4",
      "width": 1920,
      "height": 1080,
      "inLanguage": "en",
      "isPartOf": {
        "@id": "https://flex-fields.bjanczak.com/docs/flextextinput#article"
      }
    }
  ]
}).replace(/</g, '\u003c')}
</script>

<video
  autoPlay
  muted
  loop
  playsInline
  controls
  preload="metadata"
  className="w-full aspect-video rounded-xl"
  src="/art/previews/flex-text-input-emoji-motion.mp4"
  poster="/art/previews/flex-text-input-emoji.webp"
  width="1920"
  height="1080"
  aria-label="FlexTextInput — FLEX Fields demonstration"
>
  <a href="/art/previews/flex-text-input-emoji-motion.mp4">Watch the FlexTextInput demonstration (MP4).</a>
</video>
{/* flex-component-media:end */}

# FlexTextInput

`Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput` extends
Filament `TextInput`. Filament owns persisted state, casts, validation, actions,
relationships and binding modifiers. FLEX owns its presentation and optional
browser tools. The browser tools use the native input binding, with no separate persisted-state engine.

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;

FlexTextInput::make('title')
    ->required()->maxLength(120)
    ->characterCounter()->counterMode('graphemes')
    ->counterLimit(60)->enforceCounterLimit()
    ->counterDisplay('both')->clearable()->emojiPicker();
```

## Native compatibility

Use native `email()`, `numeric()`, `integer()`, `password()`, `tel()`, `url()`,
`type()`, `inputMode()`, `minValue()`, `maxValue()`, `step()`, `minLength()`,
`maxLength()`, `length()`, masks (including `RawJs`), `stripCharacters()`,
`datalist()`, autocomplete, autofocus, capitalization and spellcheck as usual.
Native validation, hydration/dehydration callbacks, disabled/read-only behavior,
`live()`/`lazy()`/blur bindings, affixes, icons, colors, prefix/suffix actions,
`extraInputAttributes()` and `extraAlpineAttributes()` remain available through
inheritance. Native `CopyAction`, `ShowPasswordAction` and `HidePasswordAction`
retain their visibility, disabled state, attributes and accessible labels.

A mask uses native text-input rendering. Explicit `type()` is respected;
password reveal only changes the DOM type when the native type is `password`
and no mask is configured. Read-only fields may still offer copying. Clearing,
suggestions, drafts, emoji and dictation cannot edit a disabled/read-only field.

Native `maxLength()` has the browser's UTF-16 maxlength semantics while native
server string validation uses Laravel's semantics. FLEX `counterLimit()` with
`enforceCounterLimit()` provides a separate, explicitly selected unit on both
client and server. Neither counter mode nor visual strength silently replaces
native validation. Domain-specific validation still belongs to application rules.

## Options

Options accept closures where their fluent signature allows them. Defaults and
budgets are centralized in `Support/TextInput/TextInputOptions.php`; unsupported
choices fail explicitly. Builder configuration uses the same contract with
snake_case names (for example `counter_mode`, `draft_lifetime`). Callback-backed
suggestions and verification belong to application PHP, not serialized config.

### Counters

| Method | Default | Contract |
|---|---|---|
| `counterMode()` | `characters` | `characters` (code points), `graphemes`, `words`, `bytes` (UTF-8) |
| `counterDisplay()` | `used` | `used`, `remaining`, `both` |
| `counterWarningAt()` | `0.85` | Fraction 0–1 |
| `softLimit()` | `null` | Advisory positive limit |
| `counterLimit()` | `null` | Explicit positive unit limit; takes precedence over soft/native limit |
| `enforceCounterLimit()` | `false` | Requires counterLimit; bounds edits and adds a server validation rule |
| `counterTemplate()` | `null` | `{count}`, `{limit}`, `{remaining}`; at most 512 characters |
| `counterLocale()` | `null` | Language tag for number formatting, e.g. `pl-PL` |

Word counting splits trimmed text on Unicode whitespace. Grapheme counting uses
PHP Unicode clusters and browser `Intl.Segmenter`; an unsupported browser reports
unavailable counting and rejects an enforced grapheme edit instead of changing
the unit silently. Older ICU/Unicode versions can differ on newly introduced
clusters. Server validation is authoritative. Large text is not segmented unless
a counter, hard unit limit or reading-time feature needs it.

### Editing and reading

| Method | Default | Contract |
|---|---|---|
| `clearValue()` | empty string | Replacement string or null |
| `clearLabel()` | translation | Accessible tool label |
| `clearConfirmation()` | `false` | Explicit confirmation before clearing |
| `clearConfirmationLabel()` | translation | Confirmation text |
| `selectOnFocus()` | `false` | Explicit select-all behavior |
| `caretOnFocus()` | `preserve` | `preserve`, `start`, `end`; no unsolicited selection |
| `enterKeyHint()` | `null` | Native enter/done/go/next/previous/search/send hint |
| `textDirection()` | `null` | Native inheritance, auto, ltr, rtl |
| `normalizeOnBlur()` | `none` | none, trim, lowercase, uppercase, collapse-whitespace |
| `pasteMode()` | `preserve` | preserve, plain-line, trim |
| `unicodeNormalization()` | `none` | none, NFC, NFKC |
| `readingTime()` | `false` | Optional estimate, minimum one minute |
| `readingWordsPerMinute()` | `200` | 50–1000 |

Typing uses native DOM selection and IME composition. Tool edits dispatch one
input event and preserve the relevant caret. Ctrl/Cmd+Z can undo the most recent
FLEX tool edit while the input still matches its result; unrelated native edits
invalidate that tool history. This is not a replacement for browser undo history.
Normalization runs on blur and is opt-in; apply equivalent server normalization
when the stored representation must satisfy an application rule.

### Copying and toolbar

| Method | Default | Contract |
|---|---|---|
| `copyFeedback()` | `icon` | icon, message, both |
| `copyMode()` | `raw` | raw, selection, trimmed |
| `copyTransform()` | `none` | none, uppercase, lowercase; never changes field state |
| `copyErrorLabel()` | translation | Failure feedback |
| `toolsOrder()` | emoji, dictation, icon, clear, actions | Unique registered group names; omitted groups append in default order |
| `toolsVisibility()` | `always` | always, focus, hover; keyboard focus reveals hover tools too |

Native `copyable(true, 'Copied!', 1200)` configures the message and duration.
Clipboard success is shown only after copying succeeds. The legacy fallback
preserves source type, focus and selection. Loading stays in the input control,
independent of toolbar visibility. A toolbar with no available actions leaves
no separator or empty position.

### Passwords

| Method | Default | Contract |
|---|---|---|
| `revealMode()` | `toggle` | toggle, hold |
| `concealOnBlur()` | `false` | Conceal when focus leaves the input |
| `capsLockIndicator()` | `false` | Optional Caps Lock feedback |
| `revealTimeout()` | `null` | Auto-conceal after 500–120000 ms |
| `passwordRequirements()` | empty | min, uppercase, lowercase, numbers, symbols; each integer 1–1024 |
| `passwordRequirementsVisible()` | `true` | Show configured requirement checks |
| `passwordStrengthAlgorithm()` | `balanced` | balanced or requirements |

```php
FlexTextInput::make('password')
    ->password()->required()->revealable()->revealMode('hold')
    ->concealOnBlur()->revealTimeout(3000)->capsLockIndicator()
    ->passwordStrength()->passwordRequirements([
        'min' => 12, 'uppercase' => 1, 'lowercase' => 1,
        'numbers' => 1, 'symbols' => 1,
    ]);
```

Configured requirements are checked by a server validation rule as well as the
visual list. Strength is an advisory estimate, not entropy measurement or a
breached-password service. Balanced mode penalizes common patterns and repeated
characters. Requirements mode reports completion of the configured rules.
Password fields reject drafts, suggestions, remote verification and text/paste
normalization to prevent unintended persistence, disclosure or transformation.

### Emoji and dictation

| Method | Default | Contract |
|---|---|---|
| `emojiCloseOnSelect()` | `true` | Close or continue inserting |
| `emojiPickerPlacement()` | `auto` | auto, top, bottom |
| `emojiPickerWidth()` | `304` | 240–640 px, viewport clamped |
| `speechDictationMode()` | `append` | append, replace-selection |
| `speechDictationContinuous()` | `true` | Browser recognition mode |
| `speechDictationInterim()` | `false` | Preview interim results without persisting them |
| `speechDictationTimeout()` | `60000` | Owner-local deadline 1000–120000 ms |
| `speechDictationMessages()` | empty | Up to 20 bounded message overrides |

Enable with `emojiPicker()` and/or `speechDictation()`. Additional methods are
`emojiPickerLocale()`, `emojiPickerLabel()`, `speechDictationLanguage()` and
`speechDictationLabel()`. Browser support and permission still govern dictation.

A browser `network` recognition error means communication with its speech service
failed, even when the device remains online. The field displays that distinction.
Only native `SpeechRecognition` / `webkitSpeechRecognition` is used; FLEX does
not download or install speech models. Service access is provided by the browser.
Click the microphone again to retry after an error.

Stopping keeps the session busy until final results arrive. Final results are
committed once through the native input event, updating Livewire state and the
counter together. Finalized text survives a subsequent service error; interim
text is never saved as a final transcript. Only one field in a document uses the
recognition microphone at a time. Switching fields, revoking editability,
hiding the document or disposing the owner retires its session and callbacks.

Closing/removing/disabling its owner aborts a session and discards pending text.
Emoji content loads only when opened. Input and textarea reuse one picker,
Select's positioning, portals, exclusivity and enter/exit transitions. Shared CSS
has one owner; it is not copied into each consuming field.

Default action icons use Hugeicons: Mic01 (the shape without a horizontal foot),
LookTop and MultiplicationSign. Override with `microphoneIcon()`, `emojiIcon()`,
`clearIcon()`, `copyIcon()`, `copySuccessIcon()`, `showPasswordIcon()` and
`hidePasswordIcon()`. Password reveal uses `ViewIcon` / `ViewOffIcon`. All default FLEX icons are from the package's Hugeicons set.

### Suggestions, verification and drafts

| Method | Default | Contract |
|---|---|---|
| `suggestionLimit()` | `8` | 1–20 results |
| `suggestionMinLength()` | `1` | 0–100 code points |
| `suggestionPlacement()` | `auto` | auto, top, bottom |
| `suggestionDescriptionPosition()` | `below` | below or trailing; applies to local and async descriptions |
| `suggestionDebounce()` | `200` | 0–2000 ms |
| `verificationDebounce()` | `400` | 0–2000 ms |
| `verificationMinLength()` | `1` | 0–100 code points |
| `draftKey()` | `null` | Opt-in nonempty key, maximum 120 bytes |
| `draftStorage()` | `session` | session, local |
| `draftLifetime()` | `86400` | 60–2592000 seconds |

```php
FlexTextInput::make('city')->suggestions([
    ['value' => 'Warsaw', 'label' => 'Warsaw', 'description' => 'Poland'],
    ['value' => 'London', 'label' => 'London', 'description' => 'United Kingdom'],
])->suggestionLimit(8)->suggestionDescriptionPosition('trailing');

FlexTextInput::make('city')
    ->suggestionsUsing(fn (string $search): array => City::query()
        ->where('name', 'like', $search.'%')->limit(8)->pluck('name')->all())
    ->skipRenderAfterStateUpdated();

FlexTextInput::make('handle')
    ->verifyUsing(function (string $value): array {
        $taken = User::where('handle', $value)->exists();

        return [
            'state' => $taken ? 'error' : 'success',
            'message' => $taken ? 'Taken' : 'Available',
        ];
    })
    ->verificationDebounce(400)
    ->skipRenderAfterStateUpdated();
```

`City` and `User` are example application models. Authorize tenant/user access
inside callbacks. These independent examples use native
`skipRenderAfterStateUpdated()` so a deferred text update bundled with a lookup
does not replace the focused input. Keep normal rendering when your server-side
state hooks must update other fields or the input configuration.

`suggestionDescriptionPosition('trailing')` places the description at the logical end of each suggestion row, including RTL layouts. Long descriptions truncate within the menu. The default `below` keeps the existing stacked layout. This applies to both `suggestions()` and `suggestionsUsing()` records containing a `description`; labels and descriptions remain plain text, and selecting a row still commits only its `value`.

Static suggestions accept at most 200 normalized records;
value ≤512, label ≤120 and description ≤240 characters. Disabled records remain visible but cannot
be selected; this is an option state, not a loading error. Free text remains permitted. Keyboard arrows, Enter and Escape,
ARIA listbox/combobox semantics and retry feedback are built in. Suggestions use
the same menu surface as Select, not a second overlay engine.

Remote methods are exposed through Filament with renderless callbacks, bounded
input and shared actor/field rate limiting. Single-flight work retains only the
latest pending query; a small bounded cache avoids repeated suggestion requests.
Late results cannot restore a disposed/disabled field. Callback verification
returns success/error/warning and a message ≤512 characters; the field stays idle
when verification is not eligible. This is
advisory and does not replace submit-time server validation. Avoid putting
sensitive values into remote verification callbacks.

Drafts use pathname + explicit key, TTL and a maximum 8192 UTF-16 code units.
Restoration only occurs into an empty field; it never overwrites filled server
state. An empty edit removes the stored draft. Storage failures produce feedback.
Use unique keys when several instances exist. Keep sensitive data out of browser
drafts. No draft module is initialized without opting in.

### Additional presentation API

`variant(primary|secondary|flat|soft)`, `size(sm|md|lg)`,
`rounding(native|none|sm|md|lg|xl|full)` and `focusOutline()` preserve FLEX's visual
language. `characterCounter()`, `clearable()`, `loading()`, `passwordStrength()`,
`passwordStrengthLabels()` and `validating()` enable their respective features.
The password indicator has four neutral segments before editing. Weak and very
weak values fill one red segment; fair values fill two amber segments; good and
strong values fill three and four green segments. Fill transitions run in both
directions, mirror in RTL, and respect reduced-motion preferences. Existing
five-level `passwordStrengthLabels()` remain configurable. The indicator is an
estimate; `passwordRequirements()` and native validation enforce your actual
submission policy. When `passwordRequirementsVisible()` is enabled, each rule
has its own row and circular check indicator. Green check indicators animate when rules are met,
and their spoken met/not-met state is localized. The list follows RTL and stays
in one column on small screens.

Static badges use `verificationStatus()`, `verificationStatusIcon()` and
`verificationStatusColor()` independently of async verification.

## Demo and examples

The live demo groups examples by task: native input features, Unicode limits,
editing, actions, passwords, emoji and dictation, suggestions and drafts, and
appearance including RTL. Open **Code Example** for copyable PHP configurations.
Only the active demo group loads its optional tools.

## Loading and behavior

Plain inputs do not load icon or emoji catalogs. Enable only the tools your field
needs: suggestions, verification, drafts and dictation load on demand, and the
emoji picker loads its content when opened. Shared resources are reused across
instances.

Emoji and icon menus align to the **whole input**, rather than their toolbar
button: right edge in LTR and left edge in RTL. Suggestions match the input's
width and align with its start edge. Menus follow resize and scroll, open above
when space below is limited, and stay inside the viewport. Small screens use the
shared mobile sheet.

Removing a field, changing its capabilities or making it disabled/read-only
releases its active tools and prevents late responses from changing its state.
Normal Livewire value updates preserve the active controller.

## Associated icon picker

Add an icon action inside the text input without a separate form row. The text remains a normal string, while the icon is a separate, server-validated value. The dropdown shares the existing [Icon Picker](/docs/icon-picker-field): search, library filters, pagination, SVG caching, keyboard support and virtualized results.

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\IconPickerField;

FlexTextInput::make('title')
    ->label('Title')
    ->iconPicker('title_icon', fn (IconPickerField $picker) => $picker
        ->label('Choose title icon')
        ->sets(['hugeicons', 'heroicons'])
        ->perPage(48)
        ->gridColumns(8)
        ->clearable());
```

The first argument is a relative sibling state path. A form with `statePath('data')` saves `data.title` and `data.title_icon`. In a repeater, each row keeps its own text and icon. Do not add a second Hidden or Icon Picker field for the same icon path: the embedded native field already owns hydration, validation and dehydration. Add a nullable string column for the icon when using ordinary Eloquent columns, or keep both keys in your JSON form data.

The configuration callback accepts the ordinary Icon Picker API. Installed Blade icon sets are available through `sets()`; a library must be registered in your application before you select it. `icons()` restricts choices to explicit identities, and `excludeIcons()` removes identities. Both search results and save validation enforce the same policy.

```php
FlexTextInput::make('cta_label')
    ->iconPicker('cta_icon', fn (IconPickerField $picker) => $picker
        ->sets(['heroicons'])
        ->icons(['heroicon-o-star', 'heroicon-o-heart', 'heroicon-o-envelope'])
        ->excludeIcons(['heroicon-o-heart'])
        ->iconsOnly()
        ->gridColumns(3)
        ->closeOnSelect()
        ->required());
```

Icon clearing is enabled by default: open the picker and choose **Clear icon** to save the associated icon as `null`. The text value is preserved, and a cleared icon stays empty after reload; the trigger displays the default picker symbol. Use `clearable(false)` in the callback to hide the clear action. Use `required()` if an empty icon must fail server validation.

Available callback settings include `sets()`, `icons()`, `excludeIcons()`, `searchResultsLayout('icons'|'grid'|'list')`, `gridColumns()`, `perPage()`, `limitPerSet()`, `preload()`, `closeOnSelect()`, `clearable()`, `label()`, `required()` and native field validation hooks. The owner fixes the embedded state path, inline trigger and parent disabled/read-only boundary.

```php
FlexTextInput::make('name')
    ->iconPicker('name_icon', condition: fn ($get) => $get('allow_custom_icon'))
    ->toolsOrder(['icon', 'emoji', 'dictation', 'clear', 'actions']);
```

This composition API is configured in trusted PHP. It does not accept executable callbacks from stored JSON. Optional assets are declared for the rendered input; a plain text field does not acquire the Icon Picker. SVG pages are fetched when the dropdown opens unless `preload()` is explicitly enabled. Choosing an icon does not transmit or replace the text input's value.

For standalone compact triggers, `IconPickerField::make('icon')->inlineTrigger()` uses the same control without its usual select label/name presentation. Use the associated-input API above when it belongs to a text input.


### Server-side value protection

FLEX rejects structured array/object values submitted to a scalar text input,
including empty arrays. Optional empty and null values retain native Filament
behavior. Required, email, URL, numeric, unique, length and application-specific
rules still belong to the native form. Enforced Unicode limits and password
requirements are checked on the server as well as shown in the browser.

The animated strength meter and `verifyUsing()` feedback are advisory; use
`passwordRequirements()`, native validation rules and your form's save action to
enforce the policy. Selecting an icon stores its name separately and validates
it against the configured libraries and allowed list.

Emoji and icon menus fit the viewport: at widths up to 768 px they use the shared
mobile sheet; above that boundary they use a clamped desktop panel with automatic
above/below placement. Their results scroll internally when height is limited.
An empty search placeholder follows RTL, while entered Latin/Arabic text keeps
native automatic text direction.
