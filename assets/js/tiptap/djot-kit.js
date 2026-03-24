/**
 * DjotKit - A Tiptap extension bundle for Djot markup
 *
 * Modified for WordPress wp-djot plugin to use CDN imports.
 */

import { Extension } from 'https://esm.sh/@tiptap/core@2';
import StarterKit from 'https://esm.sh/@tiptap/starter-kit@2';
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

import { DjotInsert } from './extensions/djot-insert.js';
import { DjotDelete } from './extensions/djot-delete.js';
import { DjotDiv } from './extensions/djot-div.js';
import { DjotSpan } from './extensions/djot-span.js';
import { DjotFootnote } from './extensions/djot-footnote.js';

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
                codeBlock: this.options.codeBlock ?? {
                    HTMLAttributes: {
                        spellcheck: 'false',
                    },
                },
                ...this.options.starterKit,
            }));
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

        // Task list extensions
        if (this.options.taskList !== false) {
            extensions.push(TaskList.configure(this.options.taskList ?? {}));
            extensions.push(TaskItem.configure({
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

        return extensions;
    },
});

export default DjotKit;
