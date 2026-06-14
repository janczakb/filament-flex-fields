import { fromEditableSlug, normalizeEditableSlug, normalizeSlug, slugify, toEditableSlug } from '../support/slug-utils.js'

export default function slugFieldFormComponent(config = {}) {
    return {
        state: config.state ?? '',
        statePath: config.statePath ?? null,
        sourcePath: config.sourcePath ?? null,
        sourceLive: config.sourceLive ?? true,
        separator: config.separator ?? '-',
        maxLength: config.maxLength ?? null,
        urlHost: config.urlHost ?? null,
        urlPath: config.urlPath ?? null,
        urlPostfix: config.urlPostfix ?? null,
        urlHostVisible: config.urlHostVisible ?? true,
        permalinkPreview: config.permalinkPreview ?? true,
        visitUrl: config.visitUrl ?? null,
        showVisitLink: config.showVisitLink ?? true,
        canVisitLink: config.canVisitLink ?? false,
        showCopyButton: config.showCopyButton ?? true,
        showRegenerateButton: config.showRegenerateButton ?? true,
        showActionButtonLabels: config.showActionButtonLabels ?? true,
        autoUpdateDisabledPath: config.autoUpdateDisabledPath ?? null,
        inlineEditPendingPath: config.inlineEditPendingPath ?? null,
        inlineEditing: config.inlineEditing ?? true,
        autoGenerate: config.autoGenerate ?? true,
        preserveOnEdit: config.preserveOnEdit ?? true,
        allowHomepage: config.allowHomepage ?? false,
        disabled: config.disabled ?? false,
        slugReadOnly: config.slugReadOnly ?? false,
        debounceMs: config.debounceMs ?? 400,
        recordSlug: config.recordSlug ?? null,
        slugSourceLocale: config.slugSourceLocale ?? null,
        selfHealingPermalink: config.selfHealingPermalink ?? false,
        permalinkRecordKey: config.permalinkRecordKey ?? null,
        selfHealingSeparator: config.selfHealingSeparator ?? '-',
        placeholder: config.placeholder ?? '',
        labels: config.labels ?? {},
        serverGenerate: config.serverGenerate ?? false,
        componentKey: config.componentKey ?? null,
        liveUniqueValidation: config.liveUniqueValidation ?? false,
        uniqueTakenMessage: config.uniqueTakenMessage ?? '',
        initialAutoSyncDisabled: config.initialAutoSyncDisabled ?? false,

        slug: '',
        draftSlug: '',
        mode: 'view',
        autoSyncDisabled: false,
        displayReady: false,
        hasManualCustomization: false,
        isFocused: false,
        copyFeedback: false,
        debounceTimer: null,
        uniqueError: null,
        isConfirming: false,
        lastSourceValue: null,

        init() {
            this.slug = normalizeSlug(this.state ?? '', this.separator, { allowHomepage: this.allowHomepage })
            this.draftSlug = toEditableSlug(this.slug, { allowHomepage: this.allowHomepage })
            this.autoSyncDisabled = this.initialAutoSyncDisabled

            this.$nextTick(() => {
                this.displayReady = true
            })

            this.$watch('state', (value) => {
                const normalized = normalizeSlug(value ?? '', this.separator, { allowHomepage: this.allowHomepage })

                if (normalized !== this.slug) {
                    this.slug = normalized
                    this.draftSlug = toEditableSlug(normalized, { allowHomepage: this.allowHomepage })
                }
            })

            if (this.sourcePath && this.sourceLive && this.autoGenerate) {
                this.bindSourceWatcher()
            }
        },

        shouldDisableAutoSyncInitially() {
            if (! this.preserveOnEdit) {
                return false
            }

            return filled(this.recordSlug)
        },

        bindSourceWatcher() {
            if (! this.$wire || ! this.sourcePath) {
                return
            }

            const path = this.resolveWireSourcePath()

            if (! path) {
                return
            }

            this.lastSourceValue = this.readSourceValue()

            this.$wire.$watch(path, (value) => {
                if (value === this.lastSourceValue) {
                    return
                }

                this.lastSourceValue = value
                this.scheduleGenerateFromSource(value)
            })
        },

        resolveWireSourcePath() {
            const path = String(this.sourcePath ?? '').trim()

            if (path === '') {
                return null
            }

            if (path.startsWith('data.')) {
                return path
            }

            if (String(this.statePath ?? '').startsWith('data.')) {
                return `data.${path}`
            }

            return path
        },

        readSourceValue() {
            const path = this.resolveWireSourcePath()

            if (! this.$wire || ! path) {
                return null
            }

            return this.$wire.$get(path)
        },

        scheduleGenerateFromSource(source) {
            if (! this.autoGenerate || this.autoSyncDisabled || this.mode === 'edit') {
                return
            }

            clearTimeout(this.debounceTimer)

            this.debounceTimer = setTimeout(() => {
                this.generateFromSource(source)
            }, this.debounceMs)
        },

        generateFromSource(source = null) {
            const resolvedSource = source ?? this.readSourceValue()

            if (! resolvedSource) {
                return
            }

            if (this.serverGenerate && this.componentKey && this.$wire?.callSchemaComponentMethod) {
                this.$wire
                    .callSchemaComponentMethod(this.componentKey, 'generateSlugPreview', {
                        source: resolvedSource,
                    })
                    .then((generated) => {
                        if (typeof generated === 'string' && generated !== '') {
                            this.applySlug(generated, { customized: false })
                            this.commitStateToWire(this.slug)
                        }
                    })
                    .catch(() => {
                        this.applyClientGeneratedSlug(resolvedSource)
                    })

                return
            }

            this.applyClientGeneratedSlug(resolvedSource)
            this.commitStateToWire(this.slug)
        },

        applyClientGeneratedSlug(source, options = {}) {
            const generated = slugify(source, this.separator, this.maxLength)
            this.applySlug(generated, { customized: false, ...options })
        },

        startEditing() {
            if (! this.canEdit()) {
                return
            }

            this.uniqueError = null
            this.draftSlug = toEditableSlug(this.slug, { allowHomepage: this.allowHomepage })
            this.mode = 'edit'
            this.markInlineEditPending()

            this.$nextTick(() => {
                this.$refs.slugInput?.focus()
                this.$refs.slugInput?.select()
            })
        },

        async confirmEditing() {
            if (this.isConfirming) {
                return
            }

            const normalized = fromEditableSlug(this.draftSlug, this.separator, { allowHomepage: this.allowHomepage })

            if (this.liveUniqueValidation && this.componentKey && this.$wire?.callSchemaComponentMethod) {
                this.isConfirming = true

                const isAvailable = await this.checkSlugAvailability(normalized)

                this.isConfirming = false

                if (! isAvailable) {
                    return
                }
            }

            this.applySlug(normalized, { customized: true })
            this.mode = 'view'
            this.markAutoUpdateDisabled()
            this.clearInlineEditPending()
            this.uniqueError = null
            this.commitStateToWire(this.slug)
        },

        cancelEditing() {
            this.draftSlug = toEditableSlug(this.slug, { allowHomepage: this.allowHomepage })
            this.mode = 'view'
            this.clearInlineEditPending()
            this.uniqueError = null
        },

        resetSlug() {
            if (! this.canEdit()) {
                return
            }

            this.mode = 'view'
            this.clearInlineEditPending()
            this.uniqueError = null
            this.clearAutoUpdateDisabledFlag()

            const finalize = () => {
                this.autoSyncDisabled = false
                this.hasManualCustomization = false
                this.commitStateToWire(this.slug)
            }

            const source = this.readSourceValue()

            if (! source) {
                this.autoSyncDisabled = false
                finalize()

                return
            }

            if (this.serverGenerate && this.componentKey && this.$wire?.callSchemaComponentMethod) {
                this.$wire
                    .callSchemaComponentMethod(this.componentKey, 'generateSlugPreview', {
                        source,
                    })
                    .then((generated) => {
                        if (typeof generated === 'string' && generated !== '') {
                            this.applySlug(generated, { customized: false, reenableAutoSync: true })
                        } else {
                            this.autoSyncDisabled = false
                        }

                        finalize()
                    })
                    .catch(() => {
                        this.applyClientGeneratedSlug(source, { reenableAutoSync: true })
                        finalize()
                    })

                return
            }

            this.applyClientGeneratedSlug(source, { reenableAutoSync: true })
            finalize()
        },

        onDraftInput(event) {
            this.draftSlug = normalizeEditableSlug(event.target.value, this.separator)
        },

        onDirectInput(event) {
            if (! this.canEdit()) {
                return
            }

            const normalized = fromEditableSlug(event.target.value, this.separator, { allowHomepage: this.allowHomepage })
            this.slug = normalized
            this.draftSlug = toEditableSlug(normalized, { allowHomepage: this.allowHomepage })
            this.autoSyncDisabled = true
            this.hasManualCustomization = true
            this.state = normalized
            this.markAutoUpdateDisabled()
        },

        onFocus() {
            this.isFocused = true
        },

        onBlur() {
            this.isFocused = false

            if (! this.inlineEditing) {
                this.commitStateToWire(this.slug)
            }
        },

        applySlug(value, { customized = false, reenableAutoSync = false } = {}) {
            this.slug = normalizeSlug(value, this.separator, { allowHomepage: this.allowHomepage })
            this.draftSlug = toEditableSlug(this.slug, { allowHomepage: this.allowHomepage })
            this.state = this.slug

            if (reenableAutoSync) {
                this.autoSyncDisabled = false
                this.hasManualCustomization = false
            } else if (customized) {
                this.autoSyncDisabled = true
                this.hasManualCustomization = true
            } else {
                this.autoSyncDisabled = this.shouldDisableAutoSyncInitially()
            }
        },

        clearAutoUpdateDisabledFlag() {
            if (! this.autoUpdateDisabledPath || ! this.$wire) {
                return
            }

            this.$wire.set(this.autoUpdateDisabledPath, false, true)
        },

        markAutoUpdateDisabled() {
            if (! this.autoUpdateDisabledPath || ! this.$wire) {
                return
            }

            this.$wire.set(this.autoUpdateDisabledPath, true, true)
        },

        markInlineEditPending() {
            if (! this.inlineEditPendingPath || ! this.$wire) {
                return
            }

            this.$wire.set(this.inlineEditPendingPath, true, true)
        },

        clearInlineEditPending() {
            if (! this.inlineEditPendingPath || ! this.$wire) {
                return
            }

            this.$wire.set(this.inlineEditPendingPath, false, true)
        },

        commitStateToWire(value) {
            if (! this.$wire || ! this.statePath) {
                return
            }

            const normalized = normalizeSlug(value, this.separator, { allowHomepage: this.allowHomepage })

            this.$wire.set(this.statePath, normalized === '' ? null : normalized, true)
        },

        async checkSlugAvailability(slugValue) {
            if (! filled(slugValue)) {
                this.uniqueError = null

                return true
            }

            try {
                const result = await this.$wire.callSchemaComponentMethod(
                    this.componentKey,
                    'checkSlugAvailability',
                    { slug: slugValue },
                )

                const isAvailable = result?.available !== false

                this.uniqueError = isAvailable
                    ? null
                    : (result?.message ?? this.uniqueTakenMessage)

                return isAvailable
            } catch {
                this.uniqueError = null

                return true
            }
        },

        fullUrl() {
            if (this.visitUrl) {
                return this.visitUrl
            }

            const host = this.urlHost ?? ''
            const path = this.urlPath ?? ''
            const postfix = this.urlPostfix ?? ''

            if (! host && ! path && ! postfix) {
                return null
            }

            return `${host}${path}${this.permalinkSlugSegment()}${postfix}`
        },

        permalinkSlugSegment() {
            if (this.allowHomepage && this.slug === '/') {
                return ''
            }

            if (! filled(this.slug)) {
                return ''
            }

            const slugForUrl = this.resolvePermalinkSlugForUrl(this.slug)
            const path = this.urlPath ?? ''

            if (path.endsWith('/')) {
                return slugForUrl
            }

            return `/${slugForUrl}`
        },

        resolvePermalinkSlugForUrl(slug) {
            if (
                this.selfHealingPermalink
                && this.permalinkRecordKey !== null
                && this.permalinkRecordKey !== undefined
                && filled(slug)
            ) {
                return this.buildSelfHealingRouteKey(slug, this.permalinkRecordKey)
            }

            return slug
        },

        buildSelfHealingRouteKey(slug, identifier) {
            if (! filled(slug)) {
                return String(identifier)
            }

            return `${slug}${this.selfHealingSeparator}${identifier}`
        },

        slugPathSeparatorVisible() {
            const path = this.urlPath ?? ''

            if (path.endsWith('/')) {
                return false
            }

            return filled(this.urlHost) || filled(path)
        },

        async copyUrl() {
            const url = this.fullUrl()

            if (! url || ! navigator?.clipboard) {
                return
            }

            try {
                await navigator.clipboard.writeText(url)
                this.copyFeedback = true
                setTimeout(() => {
                    this.copyFeedback = false
                }, 1600)
            } catch {
                // Clipboard unavailable.
            }
        },

        openVisitUrl() {
            const url = this.fullUrl()

            if (url) {
                window.open(url, '_blank', 'noopener,noreferrer')
            }
        },

        badgeLabel() {
            if (this.autoSyncDisabled) {
                return this.labels.custom ?? 'Custom'
            }

            return this.labels.auto ?? 'Auto'
        },

        isChangedFromRecord() {
            if (! filled(this.recordSlug)) {
                return false
            }

            return this.slug !== this.recordSlug
        },

        canEdit() {
            return ! this.disabled && ! this.slugReadOnly
        },

        showInlineEditor() {
            return this.inlineEditing && this.canEdit() && ! this.slugReadOnly
        },

        hasSecondaryActions() {
            return (this.showRegenerateButton && this.canRegenerate())
                || (this.showCopyButton && this.fullUrl())
                || (this.showVisitLink && this.canVisitLink && this.fullUrl())
        },

        canRegenerate() {
            if (! this.showRegenerateButton || ! this.canEdit() || ! this.sourcePath || ! this.hasManualCustomization) {
                return false
            }

            if (this.inlineEditing && this.mode === 'edit') {
                return false
            }

            const source = this.readSourceValue()

            if (! filled(source)) {
                return false
            }

            const expected = normalizeSlug(
                slugify(source, this.separator, this.maxLength),
                this.separator,
                { allowHomepage: this.allowHomepage },
            )

            return this.slug !== expected || this.autoSyncDisabled
        },

        homepageSlashVisible() {
            return this.slugPathSeparatorVisible()
        },

        editableSlugValue() {
            return toEditableSlug(this.slug, { allowHomepage: this.allowHomepage })
        },

        displaySlug() {
            if (this.allowHomepage && this.slug === '/') {
                return ''
            }

            return this.slug || this.placeholder
        },
    }
}

function filled(value) {
    return value !== null && value !== undefined && String(value).trim() !== ''
}
