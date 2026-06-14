<div class="fff-form-layout-demo__header">
    <h3 class="fff-form-layout-demo__title">{{ $title }}</h3>

    @if (filled($description ?? null))
        <p class="fff-form-layout-demo__description">{{ $description }}</p>
    @endif
</div>
