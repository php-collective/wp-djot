import { Node } from 'https://esm.sh/@tiptap/core@2';

/**
 * Djot Mermaid node extension for Tiptap
 *
 * Parses <pre class="mermaid"> blocks and serializes back to ``` mermaid code blocks.
 *
 * HTML input: <pre class="mermaid">graph TD; A-->B;</pre>
 * Djot output: ``` mermaid\ngraph TD; A-->B;\n```
 */
export const DjotMermaid = Node.create({
    name: 'djotMermaid',

    group: 'block',

    content: 'text*',

    marks: '',

    code: true,

    defining: true,

    addAttributes() {
        return {
            content: {
                default: '',
                parseHTML: element => element.textContent || '',
            },
            djotSrc: {
                default: null,
                parseHTML: element => element.getAttribute('data-djot-src'),
                // Don't render to HTML - it's only for serialization
            },
        };
    },

    parseHTML() {
        return [
            {
                tag: 'pre.mermaid',
                priority: 60, // Higher priority than CodeBlock
            },
        ];
    },

    renderHTML({ node }) {
        return ['pre', { class: 'mermaid' }, 0];
    },

    addNodeView() {
        return ({ node, getPos, editor }) => {
            const dom = document.createElement('div');
            dom.className = 'djot-mermaid-wrapper';
            dom.setAttribute('contenteditable', 'false');

            // Label
            const label = document.createElement('div');
            label.className = 'djot-mermaid-label';
            label.textContent = 'Mermaid Diagram';
            label.style.cssText = 'font-size: 11px; color: #666; padding: 4px 8px; background: #f0f0f0; border-radius: 3px 3px 0 0;';
            dom.appendChild(label);

            // Content wrapper for the mermaid preview
            const content = document.createElement('pre');
            content.className = 'mermaid';
            content.textContent = node.textContent;
            content.style.cssText = 'margin: 0; padding: 12px; background: #fafafa; border-radius: 0 0 3px 3px;';
            dom.appendChild(content);

            // Wrapper styling
            dom.style.cssText = 'border: 1px solid #ddd; border-radius: 3px; margin: 8px 0; overflow: hidden;';

            // Try to render with mermaid if available
            if (typeof window.mermaid !== 'undefined') {
                setTimeout(() => {
                    try {
                        const id = 'mermaid-editor-' + Date.now();
                        window.mermaid.render(id, node.textContent).then(result => {
                            content.innerHTML = result.svg;
                        }).catch(() => {
                            // Keep the code preview on error
                        });
                    } catch (e) {
                        // Keep the code preview on error
                    }
                }, 100);
            }

            return { dom };
        };
    },
});

export default DjotMermaid;
