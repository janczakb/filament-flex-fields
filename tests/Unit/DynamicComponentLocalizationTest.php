<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CalculatorField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Currency\CurrencySelect;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CurrencyField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexFileUpload;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexRichEditor;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexVerificationCode;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Phone\PhoneCountrySelect;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\PhoneField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\RichEditor\Actions\AttachFilesAction;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\RichEditor\Actions\YoutubeAction;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\RichEditor\FlexRichEditorTool;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\RichEditor\Plugins\FlexRichEditorYoutubePlugin;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\ScheduleField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TodoListField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\VoiceNoteRecorderField;
use Bjanczak\FilamentFlexFields\Filament\Resources\FlexFieldGroupResource;
use Bjanczak\FilamentFlexFields\Filament\Schema\Admin\FieldTypeAutoAdminSchema;
use Bjanczak\FilamentFlexFields\Filament\Schema\FieldTypeSettingsBuilder;
use Bjanczak\FilamentFlexFields\Filament\Schema\MatrixChoiceBuilder;
use Bjanczak\FilamentFlexFields\Filament\Schema\VisibilityRuleBuilder;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\CoverCard;
use Bjanczak\FilamentFlexFields\Support\CurrencyCountries;
use Bjanczak\FilamentFlexFields\Support\PhoneCountries;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableTranslatableForm;
use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;

it('updates retained rich editor tools including inherited and plugin tools after locale changes', function (): void {
    app()->setLocale('en');
    $editor = FlexRichEditor::make('body')->youtube();
    Schema::make(new TestableTranslatableForm)->components([$editor])->getComponents();
    $tools = $editor->getTools(); // Populate Filament's tool cache in the original locale.
    $youtube = FlexRichEditorYoutubePlugin::make($editor)->getEditorTools()[0];
    $english = array_map(fn ($tool) => $tool->getLabel(), $tools);

    foreach (['pl', 'de', 'en'] as $locale) {
        app()->setLocale($locale);
        foreach ($tools as $name => $tool) {
            $key = match ($name) {
                'clearContent' => 'filament-flex-fields::default.rich_editor.clear_content',
                'youtube' => 'filament-flex-fields::default.rich_editor.youtube.tool',
                'gridAddColumnBefore' => 'filament-forms::components.rich_editor.tools.table_add_column_before',
                'gridAddColumnAfter' => 'filament-forms::components.rich_editor.tools.table_add_column_after',
                'gridDeleteColumn' => 'filament-forms::components.rich_editor.tools.table_delete_column',
                default => 'filament-forms::components.rich_editor.tools.'.Str::snake($name),
            };
            expect($tool->getLabel())->toBe(__($key));
        }
        expect($youtube->getLabel())->toBe(__('filament-flex-fields::default.rich_editor.youtube.tool'));
    }
    app()->setLocale('pl');
    expect($tools['bold']->getLabel())->not->toBe($english['bold']);
});

it('preserves custom tool labels and their lazy evaluation while adapting native tools', function (): void {
    app()->setLocale('en');
    $calls = 0;
    $source = RichEditorTool::make('custom')->label(function () use (&$calls): string {
        $calls++;

        return app()->getLocale();
    });
    $adapted = FlexRichEditorTool::fromParentTool($source);
    expect($calls)->toBe(0);
    app()->setLocale('pl');
    expect($adapted->getLabel())->toBe('pl')->and($calls)->toBe(1);

    foreach ([null, '', 'Custom label', new HtmlString('<b>Custom label</b>')] as $label) {
        $source = RichEditorTool::make('bold')->label($label);
        $adapted = FlexRichEditorTool::fromParentTool($source);
        expect((new ReflectionProperty(RichEditorTool::class, 'label'))->getValue($adapted))->toBe($label);
    }
    $custom = FlexRichEditor::make('body')->tools([RichEditorTool::make('bold')->label('My bold')]);
    Schema::make(new TestableTranslatableForm)->components([$custom])->getComponents();
    app()->setLocale('de');
    expect($custom->getTools()['bold']->getLabel())->toBe('My bold');
});

it('localizes retained toolbar groups and honors an explicit group name override', function (): void {
    app()->setLocale('en');
    $editor = FlexRichEditor::make('body');
    $groups = [$editor->getFlexFullToolbarButtons(), $editor->getNativeComparisonToolbarButtons()];
    app()->setLocale('pl');
    foreach ($groups as $set) {
        foreach ([2 => 'heading', 3 => 'alignment', 5 => 'lists'] as $index => $key) {
            expect($set[$index][0]->getName())->toBe(__('filament-flex-fields::default.rich_editor.toolbar_groups.'.$key));
        }
    }
    $group = $groups[0][2][0]->name('My headings');
    app()->setLocale('de');
    expect($group->getName())->toBe('My headings');
});

it('localizes retained rich editor actions and their existing schemas', function (): void {
    app()->setLocale('en');
    $editor = FlexRichEditor::make('body');
    Schema::make(new TestableTranslatableForm)->components([$editor])->getComponents();
    $actions = collect($editor->getDefaultActions())->keyBy(fn (Action $action): string => $action->getName());
    $actions['attachFiles'] = AttachFilesAction::make();
    $actions['youtube'] = YoutubeAction::make();
    $schemas = [];
    foreach ($actions as $name => $action) {
        if (! in_array($name, ['link', 'grid', 'youtube'], true)) {
            continue;
        }
        $schemas[$name] = $action->getSchema(Schema::make(new TestableTranslatableForm));
    }
    app()->setLocale('pl');
    foreach (['attachFiles', 'link', 'grid', 'textColor', 'youtube'] as $name) {
        $prefix = $name === 'youtube'
            ? 'filament-flex-fields::default.rich_editor.youtube'
            : 'filament-forms::components.rich_editor.actions.'.Str::snake($name);
        expect($actions[$name]->getLabel())->toBe(__($prefix.($name === 'youtube' ? '.tool' : '.label')))
            ->and($actions[$name]->getModalHeading())->toBe(__($prefix.'.modal.heading'));
    }
    foreach ($schemas as $name => $schema) {
        foreach ($schema->getFlatComponents() as $field) {
            if (! $field instanceof Field) {
                continue;
            }
            $key = $name === 'youtube'
                ? 'filament-flex-fields::default.rich_editor.youtube.modal.url'
                : 'filament-forms::components.rich_editor.actions.'.$name.'.modal.form.'.Str::snake($field->getName()).'.label';
            expect($field->getLabel())->toBe(__($key));
            if ($field->getName() === 'preset') {
                expect($field->getOptions()['two'])->toBe(__('filament-forms::components.rich_editor.actions.grid.modal.form.preset.options.two'));
            }
        }
    }
});

it('updates closure generated footer actions without replacing custom actions', function (): void {
    app()->setLocale('en');
    $verification = FlexVerificationCode::make('code')->footerAction(fn () => null);
    $cover = CoverCard::make()->footerAction(fn () => null);
    $actions = [$verification->getFooterAction(), $cover->getFooterAction()];
    app()->setLocale('pl');
    expect($actions[0]->getLabel())->toBe(__('filament-flex-fields::default.verification_code.resend'))
        ->and($actions[1]->getLabel())->toBe(__('filament-flex-fields::default.cover_card.action'));
    $custom = Action::make('myAction')->label('My action');
    expect($verification->footerAction($custom)->getFooterAction()->getLabel())->toBe('My action');
});

it('refreshes retained phone and currency select catalogs by locale without changing stored values', function (): void {
    app()->setLocale('en');
    $phone = PhoneField::make('phone')->countries(['PL', 'DE']);
    $currency = CurrencyField::make('price')->currencies(['PLN', 'EUR']);
    Schema::make(new TestableTranslatableForm)->components([$phone, $currency])->statePath('data')->getComponents();
    $phoneSelect = PhoneCountrySelect::forPhone($phone);
    $currencySelect = CurrencySelect::forCurrency($currency);
    $original = [$phoneSelect->getOptions(), $currencySelect->getOptions()];
    foreach (['pl', 'de', 'en'] as $locale) {
        app()->setLocale($locale);
        expect($phoneSelect->getOptions()['PL']['label'])->toBe(PhoneCountries::name('PL'))
            ->and($currencySelect->getOptions()['PLN']['label'])->toBe(CurrencyCountries::name('PLN'))
            ->and(array_keys($phoneSelect->getOptions()))->toBe(array_keys($original[0]))
            ->and(array_keys($currencySelect->getOptions()))->toBe(array_keys($original[1]))
            ->and($phoneSelect->getLabel())->toBe(__('filament-flex-fields::default.phone.country'))
            ->and($currencySelect->getSearchPrompt())->toBe(__('filament-flex-fields::default.currency.search_currencies'));
    }
    app()->setLocale('pl');
    expect($phoneSelect->getOptions()['PL']['label'])->not->toBe($original[0]['PL']['label'])
        ->and($currencySelect->getOptions()['PLN']['label'])->not->toBe($original[1]['PLN']['label']);
    $phone->locale('fr');
    expect($phoneSelect->getOptions()['PL']['label'])->toBe(PhoneCountries::name('PL', 'fr'));
});

it('localizes retained schedule switches upload metadata and validation presets', function (): void {
    app()->setLocale('en');
    $schedule = ScheduleField::make('schedule');
    Schema::make(new TestableTranslatableForm)->components([$schedule])->statePath('data')->getComponents();
    $switch = $schedule->makeDaySwitch('monday');
    $upload = FlexFileUpload::make('files')->documentsOnly();
    $metadata = $upload->getFileMetadataTemplates();
    $calculator = CalculatorField::make('amount');
    app()->setLocale('pl');
    expect($switch->getLabel())->toBe(__('filament-flex-fields::default.schedule.toggle_day', ['day' => __('filament-flex-fields::default.schedule.days.monday')]))
        ->and($upload->getValidationMessages()['mimetypes'])->toBe(__('filament-flex-fields::default.file_upload.validation.documents_only'))
        ->and($calculator->getPlaceholder())->toBe(__('filament-flex-fields::default.calculator.placeholder'));
    foreach ($metadata as $field) {
        expect($field->getLabel())->toBe(__('filament-flex-fields::default.file_upload.metadata.'.$field->getName()));
    }
    $upload->validationMessages(['mimetypes' => 'My validation message']);
    app()->setLocale('de');
    expect($upload->getValidationMessages()['mimetypes'])->toBe('My validation message');
});

it('localizes retained schema settings headings and operator options', function (): void {
    app()->setLocale('en');
    $settings = FieldTypeSettingsBuilder::section();
    $visibility = VisibilityRuleBuilder::make('rules');
    Schema::make(new TestableTranslatableForm)->components([$settings, $visibility])->getComponents();
    $operator = collect($visibility->getDefaultChildComponents())->first(fn ($field) => $field->getName() === 'operator');
    app()->setLocale('pl');
    expect($settings->getHeading())->toBe(__('filament-flex-fields::default.schema.field_type_settings'))
        ->and($settings->getDescription())->toBe(__('filament-flex-fields::default.schema.field_type_settings_help'))
        ->and($operator->getOptions()['filled'])->toBe(__('filament-flex-fields::default.schema.visibility_operator_filled'));
});

it('does not freeze translated presentation defaults anywhere in production component builders', function (): void {
    $parser = (new ParserFactory)->createForNewestSupportedVersion();
    $finder = new NodeFinder;
    $sinks = ['label', 'placeholder', 'tooltip', 'helperText', 'hint', 'modalHeading', 'modalDescription', 'searchPrompt', 'scanButtonLabel', 'visitLabel', 'validationMessages', 'extraModalWindowAttributes', 'options', 'emptyStateHeading', 'emptyStateDescription', 'modalSubmitActionLabel', 'successNotificationTitle', 'failureNotificationTitle', 'heading', 'description', 'addActionLabel', 'keyLabel', 'valueLabel', 'content', 'modalCancelActionLabel', 'title'];
    $isTranslation = fn (Node $node): bool => ($node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Name && in_array($node->name->toString(), ['__', 'trans', 'trans_choice'], true))
        || ($node instanceof Node\Expr\StaticCall && $node->class instanceof Node\Name && $node->class->getLast() === 'Translations')
        || ($node instanceof Node\Expr\MethodCall && $node->name instanceof Node\Identifier && $node->name->name === 'todoListString');
    $eager = function (Node $node) use (&$eager, $isTranslation): bool {
        if ($node instanceof Node\Expr\Closure || $node instanceof Node\Expr\ArrowFunction) {
            return false;
        }
        if ($isTranslation($node)) {
            return true;
        }
        foreach ($node->getSubNodeNames() as $key) {
            foreach (is_array($node->$key) ? $node->$key : [$node->$key] as $child) {
                if ($child instanceof Node && $eager($child)) {
                    return true;
                }
            }
        }

        return false;
    };
    $violations = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/../../src', FilesystemIterator::SKIP_DOTS)) as $file) {
        if ($file->getExtension() !== 'php' || preg_match('~Playground~', $file->getPathname())) {
            continue; // Playground examples supply application-owned sample text.
        }
        foreach ($finder->find($parser->parse(file_get_contents($file->getPathname())), fn ($node): bool => $node instanceof Node\Expr\MethodCall || $node instanceof Node\Expr\StaticCall) as $call) {
            if (! $call->name instanceof Node\Identifier || ! in_array($call->name->name, [...$sinks, 'make'], true)) {
                continue;
            }
            $arg = $call->args[0]->value ?? null;
            if (! $arg || $arg instanceof Node\Expr\Closure || $arg instanceof Node\Expr\ArrowFunction) {
                continue;
            }
            // A collection's map callback runs eagerly when its result is passed to options().
            if ($eager($arg) || ($call->name->name === 'options' && $finder->findFirst($arg, $isTranslation))) {
                $violations[] = $file->getPathname().':'.$call->getStartLine();
            }
        }
    }
    expect($violations)->toBe([]);
});

it('keeps package-owned resource labels and field type names translated after schema construction', function (): void {
    app()->setLocale('en');
    $resource = FlexFieldGroupResource::class;
    $schema = $resource::form(Schema::make(new TestableTranslatableForm));
    $sections = $schema->getComponents();
    $fields = $sections[0]->getChildComponents();
    app()->setLocale('pl');
    expect($resource::getNavigationLabel())->toBe(__('filament-flex-fields::schema-management.navigation_plural'))
        ->and($resource::getModelLabel())->toBe(__('filament-flex-fields::schema-management.model_singular'))
        ->and($sections[0]->getHeading())->toBe(__('filament-flex-fields::schema-management.group'))
        ->and($fields[0]->getLabel())->toBe(__('filament-flex-fields::schema-management.name'))
        ->and($fields[1]->getLabel())->toBe(__('filament-flex-fields::schema-management.slug'));
    $options = (new ReflectionMethod($resource, 'fieldTypeOptions'))->invoke(null);
    expect($options['flex_text_input'])->toBe(FieldType::FlexTextInput->label());
});

it('keeps cached upload and voice note actions translated after their first use', function (): void {
    app()->setLocale('en');
    $upload = FlexFileUpload::make('files')->fileViewer()->fileMetadataEditor();
    $voice = VoiceNoteRecorderField::make('voice');
    Schema::make(new TestableTranslatableForm)->components([$upload, $voice])->getComponents();
    $viewer = $upload->getAction('viewFile');
    $metadata = $upload->getAction('editFileMetadata');
    $delete = $voice->getAction('deleteVoiceNote');
    app()->setLocale('pl');
    expect($viewer->getLabel())->toBe(__('filament-flex-fields::default.file_upload.viewer.open'))
        ->and($viewer->getExtraModalWindowAttributes()['aria-label'])->toBe(__('filament-flex-fields::default.file_upload.viewer.open'))
        ->and($metadata->getLabel())->toBe(__('filament-flex-fields::default.file_upload.metadata.edit'))
        ->and($delete->getLabel())->toBe(__('filament-flex-fields::default.audio.delete'))
        ->and($delete->getModalDescription())->toBe(__('filament-flex-fields::default.audio.delete_confirm'));
});

it('does not embed literal default interface copy in field display or table component setters', function (): void {
    $parser = (new ParserFactory)->createForNewestSupportedVersion();
    $finder = new NodeFinder;
    $violations = [];
    foreach (['Forms', 'Schemas', 'Tables', 'Infolists'] as $area) {
        $directory = __DIR__.'/../../src/Filament/'.$area.'/Components';
        if ($area === 'Tables') {
            $directory = __DIR__.'/../../src/Filament/Tables/Columns';
        }
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            foreach ($finder->findInstanceOf($parser->parse(file_get_contents($file->getPathname())), Node\Expr\MethodCall::class) as $call) {
                if (! $call->name instanceof Node\Identifier || ! in_array($call->name->name, ['label', 'placeholder', 'tooltip', 'helperText', 'hint', 'modalHeading', 'modalDescription', 'searchPrompt', 'scanButtonLabel', 'visitLabel', 'heading', 'description'], true)) {
                    continue;
                }
                $arg = $call->args[0]->value ?? null;
                if ($arg instanceof Node\Scalar\String_ && preg_match('/[a-z]{3}/i', $arg->value) && ! str_starts_with($arg->value, 'https://')) {
                    $violations[] = $file->getPathname().':'.$call->getStartLine().' '.$arg->value;
                }
            }
        }
    }
    expect($violations)->toBe([]);
});

it('keeps retained Todo action labels and matrix row or column controls localized', function (): void {
    app()->setLocale('en');
    $todo = TodoListField::make('tasks');
    Schema::make(new TestableTranslatableForm)->components([$todo])->getComponents();
    $actions = collect($todo->getDefaultActions())->keyBy(fn (Action $action): string => $action->getName());
    $rows = MatrixChoiceBuilder::rowsRepeater();
    $columns = MatrixChoiceBuilder::columnsRepeater();
    app()->setLocale('pl');
    expect($actions['editTodoItem']->getLabel())->toBe(__('filament-flex-fields::default.todo_list.edit_item'))
        ->and($actions['editTodoItem']->getModalHeading())->toBe(__('filament-flex-fields::default.todo_list.edit_modal_heading'))
        ->and($actions['editTodoItem']->getModalSubmitActionLabel())->toBe(__('filament-flex-fields::default.todo_list.edit_save'))
        ->and($actions['confirmDeleteTodoItem']->getLabel())->toBe(__('filament-flex-fields::default.todo_list.delete_item'))
        ->and($rows->getLabel())->toBe(__('filament-flex-fields::default.schema.matrix_rows'))
        ->and($columns->getLabel())->toBe(__('filament-flex-fields::default.schema.matrix_columns'))
        ->and($rows->getAddActionLabel())->toBe(__('filament-flex-fields::default.schema.matrix_rows_add'))
        ->and($columns->getAddActionLabel())->toBe(__('filament-flex-fields::default.schema.matrix_columns_add'));
});

it('localizes generated settings and enum choices without translating stored option codes', function (): void {
    app()->setLocale('en');
    $builder = FieldTypeAutoAdminSchema::class;
    $components = $builder::schemaComponentsFor(FieldType::FlexTextInput);
    $fields = collect($components)->keyBy(fn ($field): string => $field->getName());
    $variant = $fields['type_settings.variant'];
    $english = $variant->getOptions();
    app()->setLocale('pl');
    expect($variant->getLabel())->toBe(__('filament-flex-fields::default.schema.settings.variant'))
        ->and($variant->getOptions()['primary'])->toBe('Podstawowy')
        ->and(array_keys($variant->getOptions()))->toBe(array_keys($english));
    $options = (new ReflectionClass($builder))->getConstant('ENUM_OPTIONS');
    foreach (['en', 'pl', 'de', 'fr', 'es', 'it', 'nl', 'pt_BR'] as $locale) {
        app()->setLocale($locale);
        foreach ($options as $choices) {
            foreach ($choices as $label) {
                if (str_starts_with($label, 'filament-flex-fields::')) {
                    expect(__($label))->toBeString()->not->toBe($label);
                }
            }
        }
    }
});
