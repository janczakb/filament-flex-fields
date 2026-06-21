import Youtube from '@tiptap/extension-youtube'

export default function flexRichEditorYoutubeExtension() {
    const config = window.__flexRichEditorYoutubeConfig ?? {}

    return Youtube.configure({
        inline: false,
        width: config.width ?? 640,
        height: config.height ?? 480,
        nocookie: config.nocookie ?? true,
        allowFullscreen: config.allowFullscreen ?? true,
        controls: config.controls ?? true,
        addPasteHandler: config.addPasteHandler ?? true,
        HTMLAttributes: {
            class: 'fff-rich-editor__youtube-iframe',
        },
    })
}

if (typeof window !== 'undefined') {
    window.flexRichEditorYoutubeExtension = flexRichEditorYoutubeExtension
}
