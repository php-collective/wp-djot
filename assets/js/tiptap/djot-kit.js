/**
 * DjotKit - A Tiptap extension bundle for Djot markup
 *
 * Modified for WordPress wp-djot plugin to use CDN imports.
 */

import { Extension } from 'https://esm.sh/@tiptap/core@2';
import StarterKit from 'https://esm.sh/@tiptap/starter-kit@2';
import CodeBlock from 'https://esm.sh/@tiptap/extension-code-block@2';
import Highlight from 'https://esm.sh/@tiptap/extension-highlight@2';
import Subscript from 'https://esm.sh/@tiptap/extension-subscript@2';
import Superscript from 'https://esm.sh/@tiptap/extension-superscript@2';
import Link from 'https://esm.sh/@tiptap/extension-link@2';
import Image from 'https://esm.sh/@tiptap/extension-image@2';
import Table from 'https://esm.sh/@tiptap/extension-table@2';
import TableRow from 'https://esm.sh/@tiptap/extension-table-row@2';
import TableCell from 'https://esm.sh/@tiptap/extension-table-cell@2';
import TableHeader from 'https://esm.sh/@tiptap/extension-table-header@2';
import TaskList from 'https://esm.sh/@tiptap/extension-task-list@2';
import TaskItem from 'https://esm.sh/@tiptap/extension-task-item@2';
import BulletList from 'https://esm.sh/@tiptap/extension-bullet-list@2';
import OrderedList from 'https://esm.sh/@tiptap/extension-ordered-list@2';
import ListItem from 'https://esm.sh/@tiptap/extension-list-item@2';

import { DjotInsert } from './extensions/djot-insert.js';
import { DjotDelete } from './extensions/djot-delete.js';
import { DjotDiv } from './extensions/djot-div.js';
import { DjotSpan } from './extensions/djot-span.js';
import { DjotFootnote } from './extensions/djot-footnote.js';
import { DjotEmbed } from './extensions/djot-embed.js';
import { DjotKbd } from './extensions/djot-kbd.js';
import { DjotAbbreviation } from './extensions/djot-abbr.js';
import { DjotDefinition } from './extensions/djot-dfn.js';
import { DefinitionList, DefinitionTerm, DefinitionDescription } from './extensions/djot-definition-list.js';

/**
 * DjotKit - A Tiptap extension bundle for Djot markup
 *
 * Includes all standard Tiptap extensions plus Djot-specific marks:
 * - DjotInsert: {+text+}
 * - DjotDelete: {-text-}
 * - DjotDiv: ::: containers
 */
export const DjotKit = Extension.create({
    name: 'djotKit',

    addExtensions() {
        const extensions = [];

        // StarterKit provides: Document, Paragraph, Text, Bold, Italic, Code,
        // CodeBlock, Blockquote, BulletList, OrderedList, ListItem, Heading,
        // HardBreak, HorizontalRule, Dropcursor, Gapcursor, History
        if (this.options.starterKit !== false) {
            extensions.push(StarterKit.configure({
                // Disable CodeBlock from StarterKit, we add a custom one below
                codeBlock: false,
                // Disable default lists - we add custom ones that handle task-list and loose detection
                bulletList: false,
                orderedList: false,
                listItem: false,
                ...this.options.starterKit,
            }));
        }

        // Custom CodeBlock that preserves data-language-raw for Torchlight options
        if (this.options.codeBlock !== false) {
            const CustomCodeBlock = CodeBlock.extend({
                addAttributes() {
                    return {
                        ...this.parent?.(),
                        languageRaw: {
                            default: null,
                            parseHTML: element => {
                                // Check parent <pre> for data-language-raw
                                const pre = element.closest('pre');
                                return pre?.getAttribute('data-language-raw') || null;
                            },
                            renderHTML: attributes => {
                                if (!attributes.languageRaw) return {};
                                return { 'data-language-raw': attributes.languageRaw };
                            },
                        },
                    };
                },
            });
            extensions.push(CustomCodeBlock.configure({
                HTMLAttributes: {
                    spellcheck: 'false',
                },
                ...this.options.codeBlock,
            }));
        }

        // Custom BulletList that excludes task-list class and detects loose lists
        if (this.options.bulletList !== false) {
            const CustomBulletList = BulletList.extend({
                addAttributes() {
                    return {
                        ...this.parent?.(),
                        loose: {
                            default: false,
                            parseHTML: element => {
                                // Check if list is loose (items have <p> tags)
                                // Loose lists render as <li><p>text</p></li>
                                // Tight lists render as <li>text</li>
                                for (const li of element.children) {
                                    if (li.tagName !== 'LI') continue;
                                    const firstEl = li.firstElementChild;
                                    if (firstEl && firstEl.tagName === 'P') {
                                        return true;
                                    }
                                }
                                return false;
                            },
                            renderHTML: () => ({}), // Don't render this attribute
                        },
                    };
                },
                parseHTML() {
                    return [
                        {
                            tag: 'ul',
                            getAttrs: element => {
                                // Don't match task-list - let TaskList handle those
                                if (element.classList.contains('task-list')) {
                                    return false;
                                }
                                return {};
                            },
                        },
                    ];
                },
            });
            extensions.push(CustomBulletList.configure(this.options.bulletList ?? {}));
        }

        // Custom OrderedList that detects loose lists
        if (this.options.orderedList !== false) {
            const CustomOrderedList = OrderedList.extend({
                addAttributes() {
                    return {
                        ...this.parent?.(),
                        loose: {
                            default: false,
                            parseHTML: element => {
                                // Check if list is loose (items have <p> tags)
                                // Loose lists render as <li><p>text</p></li>
                                // Tight lists render as <li>text</li>
                                for (const li of element.children) {
                                    if (li.tagName !== 'LI') continue;
                                    const firstEl = li.firstElementChild;
                                    if (firstEl && firstEl.tagName === 'P') {
                                        return true;
                                    }
                                }
                                return false;
                            },
                            renderHTML: () => ({}), // Don't render this attribute
                        },
                    };
                },
            });
            extensions.push(CustomOrderedList.configure(this.options.orderedList ?? {}));
        }

        // Custom ListItem that excludes task items (those with checkboxes)
        if (this.options.listItem !== false) {
            const CustomListItem = ListItem.extend({
                parseHTML() {
                    return [
                        {
                            tag: 'li',
                            getAttrs: element => {
                                // Don't match list items with checkboxes - let TaskItem handle those
                                const checkbox = element.querySelector('input[type="checkbox"]');
                                if (checkbox) {
                                    return false;
                                }
                                return {};
                            },
                        },
                    ];
                },
            });
            extensions.push(CustomListItem.configure(this.options.listItem ?? {}));
        }

        // Highlight mark (built-in, maps to {=text=})
        if (this.options.highlight !== false) {
            extensions.push(Highlight.configure(this.options.highlight ?? {}));
        }

        // Subscript mark (maps to ~text~)
        if (this.options.subscript !== false) {
            extensions.push(Subscript.configure(this.options.subscript ?? {}));
        }

        // Superscript mark (maps to ^text^)
        if (this.options.superscript !== false) {
            extensions.push(Superscript.configure(this.options.superscript ?? {}));
        }

        // Link extension with keyboard shortcut
        if (this.options.link !== false) {
            extensions.push(
                Link.configure({
                    openOnClick: false,
                    ...this.options.link,
                }).extend({
                    addKeyboardShortcuts() {
                        return {
                            'Mod-Shift-k': () => {
                                if (this.editor.isActive('link')) {
                                    return this.editor.chain().focus().unsetLink().run();
                                }
                                const url = prompt('Enter URL:');
                                if (url) {
                                    return this.editor.chain().focus().setLink({ href: url }).run();
                                }
                                return false;
                            },
                        };
                    },
                })
            );
        }

        // Image extension
        if (this.options.image !== false) {
            extensions.push(Image.configure(this.options.image ?? {}));
        }

        // Table extensions
        if (this.options.table !== false) {
            extensions.push(Table.configure({
                resizable: true,
                ...this.options.table,
            }));
            extensions.push(TableRow.configure(this.options.tableRow ?? {}));
            extensions.push(TableCell.configure(this.options.tableCell ?? {}));
            extensions.push(TableHeader.configure(this.options.tableHeader ?? {}));
        }

        // Task list extensions - extend to match PHP output format
        if (this.options.taskList !== false) {
            // Extend TaskList to also match ul.task-list with high priority and detect loose
            const CustomTaskList = TaskList.extend({
                addAttributes() {
                    return {
                        ...this.parent?.(),
                        loose: {
                            default: false,
                            parseHTML: element => {
                                // Check if list is loose (items have <p> tags)
                                // Loose lists render as <li><p>text</p></li>
                                // Tight lists render as <li>text</li>
                                for (const li of element.children) {
                                    if (li.tagName !== 'LI') continue;
                                    const firstEl = li.firstElementChild;
                                    // Skip the checkbox input, check next sibling
                                    const contentEl = firstEl?.tagName === 'INPUT'
                                        ? firstEl.nextElementSibling
                                        : firstEl;
                                    if (contentEl && contentEl.tagName === 'P') {
                                        return true;
                                    }
                                }
                                return false;
                            },
                            renderHTML: () => ({}),
                        },
                    };
                },
                parseHTML() {
                    return [
                        { tag: 'ul[data-type="taskList"]', priority: 60 },
                        { tag: 'ul.task-list', priority: 60 },
                    ];
                },
            });
            extensions.push(CustomTaskList.configure(this.options.taskList ?? {}));

            // Extend TaskItem to also match li with checkbox input with high priority
            const CustomTaskItem = TaskItem.extend({
                addAttributes() {
                    return {
                        ...this.parent?.(),
                        checked: {
                            default: false,
                            keepOnSplit: false,
                            parseHTML: element => {
                                // First check data-checked attribute
                                const dataChecked = element.getAttribute('data-checked');
                                if (dataChecked !== null) {
                                    return dataChecked === 'true';
                                }
                                // Then check for checkbox input
                                const checkbox = element.querySelector('input[type="checkbox"]');
                                return checkbox?.hasAttribute('checked') || false;
                            },
                            renderHTML: attributes => ({
                                'data-checked': attributes.checked,
                            }),
                        },
                    };
                },
                parseHTML() {
                    return [
                        { tag: 'li[data-type="taskItem"]', priority: 60 },
                        // Match list items that contain a checkbox input
                        {
                            tag: 'li',
                            priority: 60,
                            getAttrs: element => {
                                const checkbox = element.querySelector('input[type="checkbox"]');
                                if (checkbox) return {};
                                return false;
                            },
                        },
                    ];
                },
            });
            extensions.push(CustomTaskItem.configure({
                nested: true,
                ...this.options.taskItem,
            }));
        }

        // Djot-specific extensions
        if (this.options.djotInsert !== false) {
            extensions.push(DjotInsert.configure(this.options.djotInsert ?? {}));
        }

        if (this.options.djotDelete !== false) {
            extensions.push(DjotDelete.configure(this.options.djotDelete ?? {}));
        }

        if (this.options.djotDiv !== false) {
            extensions.push(DjotDiv.configure(this.options.djotDiv ?? {}));
        }

        // Span with class mark (maps to [text]{.class})
        if (this.options.djotSpan !== false) {
            extensions.push(DjotSpan.configure(this.options.djotSpan ?? {}));
        }

        // Footnote reference node (maps to [^label])
        if (this.options.djotFootnote !== false) {
            extensions.push(DjotFootnote.configure(this.options.djotFootnote ?? {}));
        }

        // Embed node (preserves videos, oEmbed content)
        if (this.options.djotEmbed !== false) {
            extensions.push(DjotEmbed.configure(this.options.djotEmbed ?? {}));
        }

        // Keyboard mark (maps to [text]{kbd})
        if (this.options.djotKbd !== false) {
            extensions.push(DjotKbd.configure(this.options.djotKbd ?? {}));
        }

        // Abbreviation mark (maps to [text]{abbr="..."})
        if (this.options.djotAbbreviation !== false) {
            extensions.push(DjotAbbreviation.configure(this.options.djotAbbreviation ?? {}));
        }

        // Definition mark (maps to [text]{dfn} or [text]{dfn="..."})
        if (this.options.djotDefinition !== false) {
            extensions.push(DjotDefinition.configure(this.options.djotDefinition ?? {}));
        }

        // Definition list nodes (maps to : Term\n\n  Description)
        if (this.options.definitionList !== false) {
            extensions.push(DefinitionList.configure(this.options.definitionList ?? {}));
            extensions.push(DefinitionTerm.configure(this.options.definitionTerm ?? {}));
            extensions.push(DefinitionDescription.configure(this.options.definitionDescription ?? {}));
        }

        return extensions;
    },
});

export default DjotKit;
