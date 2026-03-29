import { Node } from 'https://esm.sh/@tiptap/core@2';

/**
 * Djot Tabs node extension for Tiptap
 *
 * Parses tabs HTML and stores for display, but cannot serialize back to Djot
 * without the original source (needs data-djot-src attribute from PHP).
 *
 * HTML input: <div class="tabs">...</div>
 */
export const DjotTabs = Node.create({
    name: 'djotTabs',

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
                tag: 'div.tabs',
                priority: 60, // Higher than default to catch before other parsers
            },
        ];
    },

    renderHTML({ node }) {
        return ['div', { class: 'djot-tabs-wrapper' }, 0];
    },

    addNodeView() {
        return ({ node }) => {
            const dom = document.createElement('div');
            dom.className = 'djot-tabs-wrapper';
            dom.setAttribute('contenteditable', 'false');

            // Label
            const label = document.createElement('div');
            label.className = 'djot-tabs-label';
            label.textContent = 'Tabs (read-only in visual mode)';
            label.style.cssText = 'font-size: 11px; color: #666; padding: 4px 8px; background: #f0f0f0; border-radius: 3px 3px 0 0;';
            dom.appendChild(label);

            // Content - render the original HTML
            const content = document.createElement('div');
            content.className = 'djot-tabs-content';
            content.innerHTML = node.attrs.htmlContent || '';
            content.style.cssText = 'border: 1px solid #ddd; border-top: none; border-radius: 0 0 3px 3px; overflow: hidden;';
            dom.appendChild(content);

            return { dom };
        };
    },
});

export default DjotTabs;
