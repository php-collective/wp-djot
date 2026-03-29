import { Node } from 'https://esm.sh/@tiptap/core@2';

/**
 * Djot Code Group node extension for Tiptap
 *
 * Parses code-group HTML and stores for display, but cannot serialize back to Djot
 * without the original source (needs data-djot-src attribute from PHP).
 *
 * HTML input: <div class="code-group">...</div>
 */
export const DjotCodeGroup = Node.create({
    name: 'djotCodeGroup',

    group: 'block',

    atom: true, // Cannot be edited inside

    addAttributes() {
        return {
            htmlContent: {
                default: '',
                parseHTML: element => element.outerHTML,
            },
            djotSrc: {
                default: null,
                parseHTML: element => element.getAttribute('data-djot-src'),
            },
        };
    },

    parseHTML() {
        return [
            {
                tag: 'div.code-group',
                priority: 60, // Higher than default to catch before other parsers
            },
        ];
    },

    renderHTML({ node }) {
        return ['div', { class: 'djot-code-group-wrapper' }, 0];
    },

    addNodeView() {
        return ({ node }) => {
            const dom = document.createElement('div');
            dom.className = 'djot-code-group-wrapper';
            dom.setAttribute('contenteditable', 'false');

            // Label
            const label = document.createElement('div');
            label.className = 'djot-code-group-label';
            label.textContent = 'Code Group (read-only in visual mode)';
            label.style.cssText = 'font-size: 11px; color: #666; padding: 4px 8px; background: #f0f0f0; border-radius: 3px 3px 0 0;';
            dom.appendChild(label);

            // Content - render the original HTML
            const content = document.createElement('div');
            content.className = 'djot-code-group-content';
            content.innerHTML = node.attrs.htmlContent || '';
            content.style.cssText = 'border: 1px solid #ddd; border-top: none; border-radius: 0 0 3px 3px; overflow: hidden;';
            dom.appendChild(content);

            return { dom };
        };
    },
});

export default DjotCodeGroup;
