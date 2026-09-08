                    @if ($isUserSelectField)
                        <div
                            x-show="shouldShowHeadlessUserSelectSkeleton()"
                            x-cloak
                            class="fff-user-select__dropdown-skeleton"
                            role="status"
                            aria-live="polite"
                            x-bind:aria-busy="shouldShowHeadlessUserSelectSkeleton() ? 'true' : 'false'"
                            x-bind:aria-label="headlessUserSelectSkeletonAriaLabel()"
                        >
                            <template x-for="(row, index) in headlessUserSelectSkeletonRows()" :key="index">
                                <div
                                    class="fff-user-select__dropdown-skeleton-option"
                                    x-bind:style="'--fff-user-select-skeleton-i: ' + index"
                                >
                                    <span class="fff-user-select__dropdown-skeleton-avatar" aria-hidden="true"></span>
                                    <span class="fff-user-select__dropdown-skeleton-body">
                                        <span class="fff-user-select__dropdown-skeleton-line is-primary"></span>
                                        <span class="fff-user-select__dropdown-skeleton-line is-secondary"></span>
                                    </span>
                                </div>
                            </template>
                        </div>

                        <div
                            x-show="shouldShowHeadlessUserSelectEmptyState()"
                            x-cloak
                            class="fff-user-select__dropdown-empty"
                            role="status"
                        >
                            <span class="fff-user-select__dropdown-empty-icon" aria-hidden="true" x-html="headlessUserSelectEmptyIconHtml()"></span>
                            <span class="fff-user-select__dropdown-empty-title" x-text="headlessUserSelectEmptyTitle()"></span>
                            <span class="fff-user-select__dropdown-empty-hint" x-text="headlessUserSelectEmptyHint()"></span>
                        </div>
                    @else
                        <div
                            x-show="shouldShowHeadlessSelectSkeleton()"
                            x-cloak
                            class="fff-select-dropdown-loading"
                            role="status"
                            aria-live="polite"
                            x-bind:aria-busy="shouldShowHeadlessSelectSkeleton() ? 'true' : 'false'"
                            x-bind:aria-label="headlessSelectSkeletonAriaLabel()"
                        >
                            <x-filament::loading-indicator class="fff-select-dropdown-loading__spinner" />
                            <span class="fff-select-dropdown-loading__label" x-text="headlessSelectSkeletonAriaLabel()"></span>
                        </div>

                        <div
                            x-show="shouldShowHeadlessSelectEmptyState()"
                            x-cloak
                            class="fff-select-dropdown-empty"
                            role="status"
                        >
                            <span class="fff-select-dropdown-empty-icon" aria-hidden="true" x-html="headlessSelectEmptyIconHtml()"></span>
                            <span class="fff-select-dropdown-empty-title" x-text="headlessSelectEmptyTitle()"></span>
                            <span class="fff-select-dropdown-empty-hint" x-text="headlessSelectEmptyHint()"></span>
                        </div>
                    @endif
                </div>
