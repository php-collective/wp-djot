import { Mark } from 'https://esm.sh/@tiptap/core@2';

/**
 * Djot Heading Reference mark extension for Tiptap
 *
 * Parses heading reference links and serializes back to [[Heading]] syntax.
 *
 * HTML input: <a href="#id" class="heading-ref" data-heading-ref="Heading Text">display</a>
 * Djot output: [[Heading Text]] or [[Heading Text|display text]]
 */
export const DjotHeadingRef = Mark.create({
    name: 'djotHeadingRef',

    addAttributes() {
        return {
            headingRef: {
                default: null,
                parseHTML: element => element.getAttribute('data-heading-ref'),
                renderHTML: attributes => {
                    if (!attributes.headingRef) return {};
                    return { 'data-heading-ref': attributes.headingRef };
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
            {
                tag: 'a.heading-ref[data-heading-ref]',
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
