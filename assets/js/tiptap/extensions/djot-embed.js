import { Node } from 'https://esm.sh/@tiptap/core@2';

/**
 * Djot Embed node extension for Tiptap
 *
 * Preserves embedded content (videos, oEmbed) that can't be natively edited.
 * Stores the original Djot source for round-trip preservation.
 *
 * @example
 * HTML input: <figure data-djot-src="![caption](url){video}">...</figure>
 * Djot output: ![caption](url){video}
 */
export const DjotEmbed = Node.create({
    name: 'djotEmbed',

    group: 'block',

    atom: true, // Cannot be edited inside

    addAttributes() {
        return {
            djotSrc: {
                default: null,
                parseHTML: element => element.getAttribute('data-djot-src'),
                renderHTML: attributes => {
                    if (!attributes.djotSrc) return {};
                    return { 'data-djot-src': attributes.djotSrc };
                },
            },
            htmlContent: {
                default: '',
                parseHTML: element => element.innerHTML,
                renderHTML: () => ({}), // Don't render as attribute
            },
            caption: {
                default: null,
                parseHTML: element => {
                    const figcaption = element.querySelector('figcaption');
                    return figcaption ? figcaption.textContent : null;
                },
                renderHTML: () => ({}),
            },
        };
    },

    parseHTML() {
        return [
            {
                tag: 'figure[data-djot-src]',
            },
            {
                tag: 'div[data-djot-src]',
            },
        ];
    },

    renderHTML({ node }) {
        // For visual display, show the embed with its content
        const wrapper = document.createElement('div');
        wrapper.className = 'djot-embed-wrapper';
        wrapper.setAttribute('data-djot-src', node.attrs.djotSrc || '');
        wrapper.setAttribute('contenteditable', 'false');

        // Create inner content area
        wrapper.innerHTML = node.attrs.htmlContent || '';

        // Add a visual indicator
        const label = document.createElement('div');
        label.className = 'djot-embed-label';
        label.textContent = 'Embedded content';
        wrapper.insertBefore(label, wrapper.firstChild);

        return wrapper;
    },

    // Use a NodeView for proper rendering with innerHTML
    addNodeView() {
        return ({ node }) => {
            const dom = document.createElement('div');
            dom.className = 'djot-embed-wrapper';
            dom.setAttribute('data-djot-src', node.attrs.djotSrc || '');
            dom.setAttribute('contenteditable', 'false');

            // Add label
            const label = document.createElement('div');
            label.className = 'djot-embed-label';
            label.textContent = node.attrs.caption ? `Video: ${node.attrs.caption}` : 'Embedded content';
            label.style.cssText = 'font-size: 11px; color: #666; padding: 4px 8px; background: #f0f0f0; border-radius: 3px 3px 0 0;';
            dom.appendChild(label);

            // Add content wrapper
            const contentWrapper = document.createElement('div');
            contentWrapper.className = 'djot-embed-content';
            contentWrapper.innerHTML = node.attrs.htmlContent || '';
            dom.appendChild(contentWrapper);

            // Style the wrapper
            dom.style.cssText = 'border: 1px solid #ddd; border-radius: 3px; margin: 8px 0; overflow: hidden;';

            return {
                dom,
            };
        };
    },
});

export default DjotEmbed;
