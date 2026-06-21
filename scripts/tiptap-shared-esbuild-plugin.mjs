const tiptapSharedKeys = {
    '@tiptap/core': 'core',
    '@tiptap/pm/state': 'pmState',
    '@tiptap/pm/view': 'pmView',
    '@tiptap/pm/model': 'pmModel',
}

export function tiptapSharedEsbuildPlugin() {
    return {
        name: 'tiptap-shared',
        setup(build) {
            build.onResolve({ filter: /^@tiptap\/(core|pm\/(state|view|model))$/ }, (args) => ({
                path: args.path,
                namespace: 'tiptap-shared',
            }))

            build.onLoad({ filter: /.*/, namespace: 'tiptap-shared' }, async (args) => {
                const realModule = await import(args.path)
                const namedExports = Object.keys(realModule).filter(
                    (key) => key !== '__esModule' && key !== 'default',
                )

                const key = tiptapSharedKeys[args.path]
                let code = `const __module = window.FilamentRichEditor.tiptap.${key};\n`

                if (namedExports.length) {
                    code += `export const { ${namedExports.join(', ')} } = __module;\n`
                }

                code += 'export default __module?.default ?? __module;\n'

                return { contents: code, loader: 'js' }
            })
        },
    }
}
