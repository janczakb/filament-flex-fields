function stripWordMarkup(html, aggressive) {
    const template = document.createElement('template')
    template.innerHTML = html

    template.content.querySelectorAll('style, meta, link, xml, o\\:p, w\\:*').forEach((node) => node.remove())

    template.content.querySelectorAll('[style]').forEach((node) => node.removeAttribute('style'))
    template.content.querySelectorAll('[class]').forEach((node) => node.removeAttribute('class'))

    if (aggressive) {
        template.content.querySelectorAll('span, font').forEach((node) => {
            const parent = node.parentNode

            if (! parent) {
                return
            }

            while (node.firstChild) {
                parent.insertBefore(node.firstChild, node)
            }

            parent.removeChild(node)
        })
    }

    template.content.querySelectorAll('font').forEach((node) => {
        const parent = node.parentNode

        if (! parent) {
            return
        }

        while (node.firstChild) {
            parent.insertBefore(node.firstChild, node)
        }

        parent.removeChild(node)
    })

    return template.innerHTML
}

export default function flexRichEditorPasteCleanupExtension() {
    const tiptap = window.FilamentRichEditor?.tiptap
    const Extension = tiptap?.core?.Extension
    const Plugin = tiptap?.pmState?.Plugin

    if (! Extension || ! Plugin) {
        return null
    }

    const mode = window.__flexRichEditorPasteCleanupMode === 'aggressive'
        ? 'aggressive'
        : 'standard'

    return Extension.create({
        name: 'flexRichEditorPasteCleanup',

        addProseMirrorPlugins() {
            return [
                new Plugin({
                    props: {
                        transformPastedHTML(html) {
                            return stripWordMarkup(html, mode === 'aggressive')
                        },
                    },
                }),
            ]
        },
    })
}

if (typeof window !== 'undefined') {
    window.flexRichEditorPasteCleanupExtension = flexRichEditorPasteCleanupExtension
}
