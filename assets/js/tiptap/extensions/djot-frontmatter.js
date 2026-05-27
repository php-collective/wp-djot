import { Node } from 'https://esm.sh/@tiptap/core@2';

export const DjotFrontmatter = Node.create({
    name: 'djotFrontmatter',

    group: 'block',

    atom: true,

    selectable: false,

    addAttributes() {
        return {
            djotSrc: {
                default: '',
                parseHTML: element => element.getAttribute('data-djot-src') || '',
                renderHTML: attributes => {
                    if (!attributes.djotSrc) return {};
                    return { 'data-djot-src': attributes.djotSrc };
                },
            },
        };
    },

    parseHTML() {
        return [
            {
                tag: 'div[data-djot-frontmatter]',
            },
        ];
    },

    renderHTML({ HTMLAttributes }) {
        return [
            'div',
            {
                ...HTMLAttributes,
                'data-djot-frontmatter': '',
                hidden: '',
            },
        ];
    },
});

export default DjotFrontmatter;
