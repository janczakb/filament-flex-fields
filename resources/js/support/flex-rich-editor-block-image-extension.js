function paragraphHasImageBesideOtherContent(paragraph, imageType) {
    let hasImage = false
    let hasOtherInline = false

    paragraph.forEach((child) => {
        if (child.type === imageType) {
            hasImage = true

            return
        }

        if (child.isText) {
            if (child.text.replace(/\u200b/g, '').trim().length > 0) {
                hasOtherInline = true
            }

            return
        }

        hasOtherInline = true
    })

    return hasImage && hasOtherInline
}

function splitParagraphAroundImages(paragraph, paragraphType, imageType) {
    const fragments = []
    let buffer = []

    const flushText = () => {
        if (buffer.length === 0) {
            return
        }

        fragments.push(paragraphType.create(null, buffer))
        buffer = []
    }

    paragraph.forEach((child) => {
        if (child.type === imageType) {
            flushText()
            fragments.push(paragraphType.create(null, [child]))

            return
        }

        if (child.isText && child.text.replace(/\u200b/g, '').trim().length === 0 && buffer.length === 0) {
            return
        }

        buffer.push(child)
    })

    flushText()

    return fragments
}

function isolateParagraphImages(state) {
    const { doc, schema } = state
    const paragraphType = schema.nodes.paragraph
    const imageType = schema.nodes.image

    if (! paragraphType || ! imageType) {
        return null
    }

    const replacements = []

    doc.descendants((node, pos) => {
        if (node.type !== paragraphType) {
            return
        }

        if (! paragraphHasImageBesideOtherContent(node, imageType)) {
            return
        }

        const fragments = splitParagraphAroundImages(node, paragraphType, imageType)

        if (fragments.length <= 1) {
            return
        }

        replacements.push({ from: pos, to: pos + node.nodeSize, fragments })
    })

    if (replacements.length === 0) {
        return null
    }

    let transaction = state.tr

    replacements
        .sort((left, right) => right.from - left.from)
        .forEach(({ from, to, fragments }) => {
            transaction = transaction.replaceWith(from, to, fragments)
        })

    return transaction
}

export default function flexRichEditorBlockImageExtension() {
    const tiptap = window.FilamentRichEditor?.tiptap
    const Extension = tiptap?.core?.Extension
    const Plugin = tiptap?.pmState?.Plugin
    const PluginKey = tiptap?.pmState?.PluginKey

    if (! Extension || ! Plugin || ! PluginKey) {
        return null
    }

    return Extension.create({
        name: 'flexRichEditorBlockImages',

        addProseMirrorPlugins() {
            return [
                new Plugin({
                    key: new PluginKey('flexRichEditorBlockImages'),
                    appendTransaction(transactions, _oldState, newState) {
                        if (! transactions.some((transaction) => transaction.docChanged)) {
                            return null
                        }

                        return isolateParagraphImages(newState)
                    },
                }),
            ]
        },
    })
}

if (typeof window !== 'undefined') {
    window.flexRichEditorBlockImageExtension = flexRichEditorBlockImageExtension
}
