import { Node, mergeAttributes } from 'https://esm.sh/@tiptap/core@2';

/**
 * Djot Definition List extension for Tiptap
 *
 * Renders as definition list syntax in Djot:
 * : Term
 *
 *   Description paragraph
 *
 * Parses <dl>, <dt>, <dd> HTML elements from PHP renderer
 */

export const DefinitionList = Node.create({
    name: 'definitionList',

    group: 'block',

    content: '(definitionTerm | definitionDescription)+',

    parseHTML() {
        return [
            { tag: 'dl' },
        ];
    },

    renderHTML({ HTMLAttributes }) {
        return ['dl', mergeAttributes(HTMLAttributes), 0];
    },
});

export const DefinitionTerm = Node.create({
    name: 'definitionTerm',

    content: 'inline*',

    defining: true,

    parseHTML() {
        return [
            { tag: 'dt' },
        ];
    },

    renderHTML({ HTMLAttributes }) {
        return ['dt', mergeAttributes(HTMLAttributes), 0];
    },
});

export const DefinitionDescription = Node.create({
    name: 'definitionDescription',

    content: 'block+',

    defining: true,

    parseHTML() {
        return [
            { tag: 'dd' },
        ];
    },

    renderHTML({ HTMLAttributes }) {
        return ['dd', mergeAttributes(HTMLAttributes), 0];
    },
});

export default DefinitionList;
