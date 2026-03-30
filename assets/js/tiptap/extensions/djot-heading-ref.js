import { Mark } from 'https://esm.sh/@tiptap/core@2';

/**
 * Djot Heading Reference mark extension for Tiptap
 *
 * Parses heading reference links and serializes back to [[Heading]] syntax.
 *
 * Round-trip mode HTML: <a href="#id" class="heading-ref" data-djot-heading-ref="Target">display</a>
 * With custom display: adds data-djot-heading-ref-display="display text"
 * Djot output: [[Heading Text]] or [[Heading Text|display text]]
 */
export const DjotHeadingRef = Mark.create({
    name: 'djotHeadingRef',

    addAttributes() {
        return {
            headingRef: {
                default: null,
                parseHTML: element => {
                    // Round-trip mode uses data-djot-heading-ref
                    return element.getAttribute('data-djot-heading-ref')
                        || element.getAttribute('data-heading-ref');
                },
                renderHTML: attributes => {
                    if (!attributes.headingRef) return {};
                    return { 'data-djot-heading-ref': attributes.headingRef };
                },
            },
            href: {
                default: null,
                parseHTML: element => element.getAttribute('href'),
                renderHTML: attributes => {
                    if (!attributes.href) return {};
                    return { href: attributes.href };
                },
            },
        };
    },

    parseHTML() {
        return [
            // Round-trip mode format (data-djot-heading-ref)
            {
                tag: 'a.heading-ref[data-djot-heading-ref]',
                priority: 60, // Higher priority than Link extension
            },
            // Legacy format (data-heading-ref) for backwards compatibility
            {
                tag: 'a.heading-ref[data-heading-ref]',
                priority: 60,
            },
        ];
    },

    renderHTML({ HTMLAttributes }) {
        return ['a', {
            class: 'heading-ref',
            ...HTMLAttributes
        }, 0];
    },
});

export default DjotHeadingRef;
