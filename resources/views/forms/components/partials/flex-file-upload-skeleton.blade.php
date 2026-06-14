<div
    @class([
        'fff-flex-file-upload__skeleton',
        'fff-flex-file-upload__skeleton--avatar' => $isAvatar,
        'is-disabled' => $isDisabled,
    ])
    aria-hidden="true"
>
    <div class="fff-flex-file-upload__skeleton-dropzone">
        @unless ($isAvatar)
            <span class="fff-flex-file-upload__skeleton-icon"></span>
            <span class="fff-flex-file-upload__skeleton-line is-primary"></span>
            <span class="fff-flex-file-upload__skeleton-line is-secondary"></span>
        @endunless
        <span class="fff-flex-file-upload__skeleton-shimmer"></span>
    </div>
</div>
