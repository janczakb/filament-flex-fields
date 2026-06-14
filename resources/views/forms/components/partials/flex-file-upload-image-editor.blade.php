        @if ($hasImageEditor && (! $isDisabled))
            <div
                x-show="isEditorOpen"
                x-cloak
                x-on:click.stop=""
                x-trap.noscroll="isEditorOpen"
                x-on:keydown.escape.prevent.stop="closeEditor"
                @class([
                    'fi-fo-file-upload-editor',
                    'fi-fo-file-upload-editor-circle-cropper' => $hasCircleCropper,
                    'fi-fo-file-upload-editor-crop-only' => ! $isImageEditorExplicitlyEnabled,
                ])
            >
                <div
                    aria-hidden="true"
                    class="fi-fo-file-upload-editor-overlay"
                ></div>

                <div class="fi-fo-file-upload-editor-window">
                    <div class="fi-fo-file-upload-editor-image-ctn">
                        <img
                            x-ref="editor"
                            class="fi-fo-file-upload-editor-image"
                        />
                    </div>

                    <div class="fi-fo-file-upload-editor-control-panel">
                        @if ($isImageEditorExplicitlyEnabled)
                            <div
                                class="fi-fo-file-upload-editor-control-panel-main"
                            >
                                <div
                                    class="fi-fo-file-upload-editor-control-panel-group"
                                >
                                    @foreach ([
                                        [
                                            'label' => __('filament-forms::components.file_upload.editor.fields.x_position.label'),
                                            'ref' => 'xPositionInput',
                                            'unit' => __('filament-forms::components.file_upload.editor.fields.x_position.unit'),
                                            'alpineSaveHandler' => 'editor.setData({...editor.getData(true), x: +$el.value})',
                                        ],
                                        [
                                            'label' => __('filament-forms::components.file_upload.editor.fields.y_position.label'),
                                            'ref' => 'yPositionInput',
                                            'unit' => __('filament-forms::components.file_upload.editor.fields.y_position.unit'),
                                            'alpineSaveHandler' => 'editor.setData({...editor.getData(true), y: +$el.value})',
                                        ],
                                        [
                                            'label' => __('filament-forms::components.file_upload.editor.fields.width.label'),
                                            'ref' => 'widthInput',
                                            'unit' => __('filament-forms::components.file_upload.editor.fields.width.unit'),
                                            'alpineSaveHandler' => 'editor.setData({...editor.getData(true), width: +$el.value})',
                                        ],
                                        [
                                            'label' => __('filament-forms::components.file_upload.editor.fields.height.label'),
                                            'ref' => 'heightInput',
                                            'unit' => __('filament-forms::components.file_upload.editor.fields.height.unit'),
                                            'alpineSaveHandler' => 'editor.setData({...editor.getData(true), height: +$el.value})',
                                        ],
                                        [
                                            'label' => __('filament-forms::components.file_upload.editor.fields.rotation.label'),
                                            'ref' => 'rotationInput',
                                            'unit' => __('filament-forms::components.file_upload.editor.fields.rotation.unit'),
                                            'alpineSaveHandler' => 'editor.rotateTo(+$el.value)',
                                        ],
                                    ] as $input)
                                        <label>
                                            <x-filament::input.wrapper>
                                                <x-slot name="prefix">
                                                    {{ $input['label'] }}
                                                </x-slot>

                                                <input
                                                    x-on:keyup.enter.prevent.stop="editor && {!! $input['alpineSaveHandler'] !!}"
                                                    x-on:blur="editor && {!! $input['alpineSaveHandler'] !!}"
                                                    x-ref="{{ $input['ref'] }}"
                                                    x-on:keydown.enter.prevent
                                                    type="text"
                                                    class="fi-input"
                                                />

                                                <x-slot name="suffix">
                                                    {{ $input['unit'] }}
                                                </x-slot>
                                            </x-filament::input.wrapper>
                                        </label>
                                    @endforeach
                                </div>

                                <div
                                    class="fi-fo-file-upload-editor-control-panel-group"
                                >
                                    @foreach ($getImageEditorActions() as $groupedActions)
                                        <div class="fi-btn-group">
                                            @foreach ($groupedActions as $action)
                                                <button
                                                    aria-label="{{ $action['label'] }}"
                                                    type="button"
                                                    x-on:click.prevent.stop="{{ $action['alpineClickHandler'] }}"
                                                    x-tooltip="{ content: @js($action['label']), theme: $store.theme }"
                                                    class="fi-btn"
                                                >
                                                    {{ $action['iconHtml'] }}
                                                </button>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>

                                @if (count($aspectRatios = $getImageEditorAspectRatioOptionsForJs()))
                                    <div
                                        class="fi-fo-file-upload-editor-control-panel-group"
                                    >
                                        <div
                                            class="fi-fo-file-upload-editor-control-panel-group-title"
                                        >
                                            {{ __('filament-forms::components.file_upload.editor.aspect_ratios.label') }}
                                        </div>

                                        @foreach (collect($aspectRatios)->chunk(5) as $ratiosChunk)
                                            <div class="fi-btn-group">
                                                @foreach ($ratiosChunk as $label => $ratio)
                                                    <button
                                                        type="button"
                                                        x-on:click.prevent.stop="
                                                            currentRatio = @js($label) {!! ';' !!}
                                                            editor.setAspectRatio(@js($ratio))
                                                        "
                                                        x-tooltip="{ content: @js(__('filament-forms::components.file_upload.editor.actions.set_aspect_ratio.label', ['ratio' => $label])), theme: $store.theme }"
                                                        x-bind:class="{ 'fi-active': currentRatio === @js($label) }"
                                                        class="fi-btn"
                                                    >
                                                        {{ $label }}
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif

                        <div
                            class="fi-fo-file-upload-editor-control-panel-footer"
                        >
                            @if ($isImageEditorExplicitlyEnabled)
                                <button
                                    type="button"
                                    x-on:click.prevent="pond.imageEditEditor.oncancel"
                                    class="fi-btn"
                                >
                                    {{ __('filament-forms::components.file_upload.editor.actions.cancel.label') }}
                                </button>

                                <button
                                    type="button"
                                    x-on:click.prevent.stop="editor.reset()"
                                    {{
                                        (new \Illuminate\View\ComponentAttributeBag)
                                            ->color(\Filament\Support\View\Components\ButtonComponent::class, 'danger')
                                            ->class(['fi-btn fi-fo-file-upload-editor-control-panel-reset-action'])
                                    }}
                                >
                                    {{ __('filament-forms::components.file_upload.editor.actions.reset.label') }}
                                </button>

                                <button
                                    type="button"
                                    x-on:click.prevent="saveEditor"
                                    {{
                                        (new \Illuminate\View\ComponentAttributeBag)
                                            ->color(\Filament\Support\View\Components\ButtonComponent::class, 'success')
                                            ->class(['fi-btn'])
                                    }}
                                >
                                    {{ __('filament-forms::components.file_upload.editor.actions.save.label') }}
                                </button>
                            @else
                                <button
                                    type="button"
                                    x-on:click.prevent="saveEditor"
                                    {{
                                        (new \Illuminate\View\ComponentAttributeBag)
                                            ->color(\Filament\Support\View\Components\ButtonComponent::class, 'success')
                                            ->class(['fi-btn'])
                                    }}
                                >
                                    {{ __('filament-forms::components.file_upload.editor.actions.save.label') }}
                                </button>

                                <button
                                    type="button"
                                    x-on:click.prevent="pond.imageEditEditor.oncancel"
                                    class="fi-btn"
                                >
                                    {{ __('filament-forms::components.file_upload.editor.actions.cancel.label') }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
