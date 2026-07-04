<div data-fff-calculator-panel-host hidden aria-hidden="true"></div>

<template x-teleport="body">
    <div class="fff-calculator-panel-portal">
        <div
            class="fff-calculator-panel__backdrop"
            x-cloak
            x-show="isOpen && isMobile"
            x-transition.opacity.duration.320ms
            x-on:click="close()"
            aria-hidden="true"
        ></div>

        <div
            x-ref="panel"
            class="fff-calculator-panel"
            x-cloak
            x-bind:class="panelClasses()"
            x-bind:style="panelStyle()"
            role="dialog"
            aria-modal="true"
            x-bind:aria-label="panelTitle"
            x-bind:aria-hidden="(! isOpen && ! isAnimating).toString()"
        >
            <div class="fff-calculator-panel__shell">
                <div class="fff-calculator-panel__inner">
            <div
                class="fff-calculator-panel__header"
                x-on:pointerdown.prevent="! isMobile && startDrag($event)"
            >
                <div class="fff-calculator-panel__drag" aria-hidden="true">
                    <span></span><span></span><span></span>
                </div>
                <div class="fff-calculator-panel__context">
                    <p class="fff-calculator-panel__eyebrow" x-text="panelTitle"></p>
                    <p
                        class="fff-calculator-panel__field-label"
                        x-text="activeLabel"
                        x-bind:class="{ 'is-switching': isSwitchingContext }"
                    ></p>
                </div>
                <button
                    type="button"
                    class="fff-calculator-panel__close"
                    x-on:click.stop="close()"
                    x-bind:aria-label="closeLabel"
                >
                    <span class="fff-calculator-panel__close-icon" x-html="closeIconSvg()"></span>
                </button>
            </div>

            <div class="fff-calculator-panel__display">
                <p
                    class="fff-calculator-panel__expression"
                    x-show="secondaryDisplayValue() !== ''"
                    x-text="secondaryDisplayValue()"
                    x-bind:class="{ 'is-switching': isSwitchingContext }"
                ></p>
                <p
                    class="fff-calculator-panel__result"
                    x-bind:class="{ 'is-preview': showLivePreview(), 'is-switching': isSwitchingContext }"
                    x-text="primaryDisplayValue()"
                ></p>
            </div>

            <div class="fff-calculator-panel__keypad">
                <template x-for="(row, rowIndex) in keypadRows" :key="rowIndex">
                    <div class="fff-calculator-panel__keypad-row">
                        <template x-for="cell in row" :key="cell.key">
                            <button
                                type="button"
                                class="fff-calculator-panel__key"
                                x-bind:class="keyClasses(cell)"
                                x-bind:style="cell.span ? `grid-column: span ${cell.span}` : null"
                                x-bind:aria-label="keyAriaLabel(cell)"
                                x-on:click="appendToken(cell.key)"
                            >
                                <span
                                    x-show="cell.icon"
                                    class="fff-calculator-panel__key-icon"
                                    x-html="keyIconSvg(cell)"
                                ></span>
                                <span x-show="! cell.icon" x-text="cell.key"></span>
                            </button>
                        </template>
                    </div>
                </template>
            </div>

            <div class="fff-calculator-panel__footer">
                <button type="button" class="fff-calculator-panel__btn fff-calculator-panel__btn--primary" x-on:click="apply()" x-text="applyLabel"></button>
            </div>
                </div>
            </div>
        </div>
    </div>
</template>
