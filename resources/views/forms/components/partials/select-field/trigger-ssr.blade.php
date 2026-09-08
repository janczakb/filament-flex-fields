    @if ($isItemCardVariant && ($itemCardInitialTriggerLabel ?? null) !== null)
        <div
            class="fff-select-item-card-ssr"
            aria-hidden="true"
        >
            <span @class([
                'fff-select-item-card-ssr__value',
                'is-placeholder' => blank($state),
            ])>{{ $itemCardInitialTriggerLabel }}</span>
            <span class="fff-select-item-card-ssr__chevron" aria-hidden="true"></span>
        </div>
    @endif

    @if ($showInitialTriggerSsr)
        <div
            @class([
                'fff-select-trigger-ssr',
                'fi-select-input-ctn' => $field->isClearable() && filled($state) && ! $isMultiple && ! $isDisabled,
                'fi-select-input-ctn-clearable' => $field->isClearable() && filled($state) && ! $isMultiple && ! $isDisabled,
                'fi-select-input-ctn-option-labels-not-wrapped' => ! $canOptionLabelsWrap,
                'fff-select-trigger-ssr--multiple' => $isMultiple,
                'fff-select-trigger-ssr--layout-grid' => $isGridLayout,
                'fff-select-trigger-ssr--clearable' => $field->isClearable() && filled($state) && ! $isMultiple && ! $isDisabled,
                'fff-select-trigger-ssr--inline-field-label' => $showInlineFieldLabel,
                'fff-select-trigger-ssr--inline-search' => $isInlineSearch && $isSearchable && ! $isMultiple,
                'fff-select-trigger-ssr--rich-list-trigger' => $useRichListTriggerDisplay,
                'fff-user-select-trigger-ssr' => $isUserSelectField,
            ])
            aria-hidden="true"
        >
            <span class="fff-select-trigger-ssr__btn">
                @if ($showInlineFieldLabel)
                    <span class="fff-select-inline-field-label">{{ $fieldLabel }}</span>
                @endif

                @if ($isInlineSearch && $isSearchable && ! $isMultiple)
                    <span class="fff-select-inline-search-ctn">
                        <input
                            type="text"
                            class="fi-input"
                            id="{{ $id }}-ssr-search"
                            readonly
                            tabindex="-1"
                            aria-hidden="true"
                            autocomplete="off"
                            @if (filled($headlessTriggerDir))
                                dir="{{ $headlessTriggerDir }}"
                            @endif
                            value="{{ $headlessInlineSearchSsrValue }}"
                        />
                    </span>
                @else
                    <span class="fff-select-trigger-ssr__value-ctn fi-select-input-value-ctn">
                        @if ($isMultiple)
                            @if (($initialMultipleTriggerHtml ?? null) !== null)
                                <span class="fi-select-input-value-label">{!! $initialMultipleTriggerHtml !!}</span>
                            @elseif ($initialTriggerBadges !== [])
                                <span class="fi-select-input-value-badges-ctn">
                                    @foreach ($initialTriggerBadges as $badge)
                                        <span class="fi-badge fi-size-md">
                                            <span class="fi-badge-label-ctn">
                                                <span class="fi-badge-label">
                                                    @if ($isHtmlAllowed)
                                                        {!! $badge['label'] !!}
                                                    @else
                                                        {{ $badge['label'] }}
                                                    @endif
                                                </span>
                                            </span>
                                            <span class="fi-badge-delete-btn" aria-hidden="true">
                                                {!! $chipRemoveIconHtml !!}
                                            </span>
                                        </span>
                                    @endforeach
                                </span>
                            @else
                                <span class="fi-select-input-placeholder">{{ $getPlaceholder() }}</span>
                            @endif
                        @else
                            <span @class([
                                'fi-select-input-value-label',
                                'fi-select-input-placeholder' => $isInitialTriggerPlaceholder,
                            ])>
                                @if ($isHtmlAllowed)
                                    {!! $initialTriggerLabel !!}
                                @else
                                    {{ $initialTriggerLabel }}
                                @endif
                            </span>
                        @endif
                    </span>
                @endif
            </span>

            @if ($field->isClearable() && filled($state) && ! $isMultiple && ! $isDisabled)
                <button
                    type="button"
                    class="fi-select-input-value-remove-btn"
                    aria-hidden="true"
                    tabindex="-1"
                >
                    {!! $clearIconHtml !!}
                </button>
            @endif
        </div>
    @endif
