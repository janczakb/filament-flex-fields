@php
    $layout = $layout ?? 'list';
@endphp

@if ($layout === 'tag')
  <span class="fff-user-select-option fff-user-select-option--tag">
      @include('filament-flex-fields::forms.components.partials.user-select-avatar', [
          'image' => $image,
          'initials' => $initials,
          'verified' => $verified,
          'layout' => $layout,
      ])

      <span class="fff-user-select-option__name">{{ $label }}</span>
  </span>
@elseif ($layout === 'trigger')
  <span class="fff-user-select-option fff-user-select-option--trigger">
      @include('filament-flex-fields::forms.components.partials.user-select-avatar', [
          'image' => $image,
          'initials' => $initials,
          'verified' => $verified,
          'layout' => $layout,
      ])

      <span class="fff-user-select-option__content">
          <span class="fff-user-select-option__name">{{ $label }}</span>

          @if (filled($description))
              <span class="fff-user-select-option__email">{{ $description }}</span>
          @endif
      </span>
  </span>
@else
  <span class="fff-user-select-option fff-user-select-option--list">
      @include('filament-flex-fields::forms.components.partials.user-select-avatar', [
          'image' => $image,
          'initials' => $initials,
          'verified' => $verified,
          'layout' => $layout,
      ])

      <span class="fff-user-select-option__content">
          <span class="fff-user-select-option__name">{{ $label }}</span>

          @if (filled($description))
              <span class="fff-user-select-option__email">{{ $description }}</span>
          @endif
      </span>
  </span>
@endif
