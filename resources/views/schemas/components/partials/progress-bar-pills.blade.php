@if ($usesAutoPillCount)
    <div
        class="fff-progress-bar__pills fff-progress-bar__pills--auto"
        role="progressbar"
        @unless ($isIndeterminate)
            aria-valuemin="0"
            aria-valuemax="100"
            aria-valuenow="{{ $percentage }}"
        @endunless
        @if ($isIndeterminate)
            aria-busy="true"
        @endif
        x-data="{
            count: 0,
            percentage: @js($percentage),
            gradientFrom: @js($getGradientFrom()),
            gradientTo: @js($getGradientTo()),
            pillIndices: [],
            observer: null,
            init() {
                this.$nextTick(() => {
                    this.updateCount();
                    this.observer = new ResizeObserver(() => this.updateCount());
                    this.observer.observe(this.$root);
                });
            },
            destroy() {
                this.observer?.disconnect();
            },
            updateCount() {
                const styles = getComputedStyle(this.$root);
                const pillWidth = parseFloat(styles.getPropertyValue('--fff-progress-bar-pill-width'));
                const pillGap = parseFloat(styles.getPropertyValue('--fff-progress-bar-pill-gap'));
                const width = this.$root.clientWidth;
                const step = pillWidth + pillGap;

                if (! step || ! width) {
                    return;
                }

                const nextCount = Math.max(1, Math.floor((width + pillGap) / step));

                if (nextCount !== this.count) {
                    this.count = nextCount;
                    this.pillIndices = Array.from({ length: nextCount }, (_, index) => index);
                }
            },
            activeCount() {
                return Math.round((this.percentage / 100) * this.count);
            },
            isActive(index) {
                return index < this.activeCount();
            },
            pillColor(index) {
                const active = this.activeCount();

                if (index >= active) {
                    return null;
                }

                if (active <= 1) {
                    return this.gradientFrom;
                }

                const ratio = index / (active - 1);

                return this.interpolateColor(this.gradientFrom, this.gradientTo, ratio);
            },
            interpolateColor(from, to, ratio) {
                const parse = (color) => {
                    const match = color.match(/rgb\(\s*(\d+)\s+(\d+)\s+(\d+)\s*\)/);

                    return match ? [Number(match[1]), Number(match[2]), Number(match[3])] : [239, 68, 68];
                };

                const [r1, g1, b1] = parse(from);
                const [r2, g2, b2] = parse(to);
                const r = Math.round(r1 + (r2 - r1) * ratio);
                const g = Math.round(g1 + (g2 - g1) * ratio);
                const b = Math.round(b1 + (b2 - b1) * ratio);

                return `rgb(${r} ${g} ${b})`;
            },
        }"
    >
        <template x-for="index in pillIndices" :key="index">
            <span
                class="fff-progress-bar__pill"
                :class="{ 'is-active': isActive(index) }"
                :style="isActive(index) ? { backgroundColor: pillColor(index) } : {}"
            ></span>
        </template>
    </div>
@else
    <div
        class="fff-progress-bar__pills fff-progress-bar__pills--fixed"
        role="progressbar"
        @unless ($isIndeterminate)
            aria-valuemin="0"
            aria-valuemax="{{ $pillCount }}"
            aria-valuenow="{{ $activePillCount }}"
        @endunless
        @if ($isIndeterminate)
            aria-busy="true"
        @endif
    >
        @for ($pillIndex = 0; $pillIndex < $pillCount; $pillIndex++)
            @php($pillColor = $getPillColorForIndex($pillIndex))

            <span @class([
                'fff-progress-bar__pill',
                'is-active' => filled($pillColor),
            ]) @if (filled($pillColor)) style="--fff-progress-bar-pill-color: {{ $pillColor }}" @endif></span>
        @endfor
    </div>
@endif
