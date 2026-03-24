/**
 * WP Djot Visual Editor
 *
 * A Tiptap-based WYSIWYG editor for Djot markup.
 * Loaded as ES module with dynamic imports from esm.sh CDN.
 */

// Import from local modules
import { DjotKit } from './djot-kit.js';
import { serializeToDjot } from './serializer.js';

let editorInstance = null;

/**
 * Initialize the visual editor
 *
 * @param {HTMLElement} container - Container element for the editor
 * @param {string} initialContent - Initial HTML content
 * @param {Function} onChange - Callback when content changes (receives Djot markup)
 * @returns {Object} Editor instance with control methods
 */
export async function initVisualEditor(container, initialContent, onChange) {
    // Dynamic imports from CDN
    const { Editor } = await import('https://esm.sh/@tiptap/core@2');
    const Placeholder = (await import('https://esm.sh/@tiptap/extension-placeholder@2')).default;

    // Destroy existing editor if any
    if (editorInstance) {
        editorInstance.destroy();
    }

    // Create editor element
    const editorEl = document.createElement('div');
    editorEl.className = 'wpdjot-visual-editor';
    container.innerHTML = '';
    container.appendChild(editorEl);

    // Initialize Tiptap editor
    editorInstance = new Editor({
        element: editorEl,
        extensions: [
            DjotKit,
            Placeholder.configure({
                placeholder: 'Start typing...',
            }),
        ],
        content: initialContent || '<p></p>',
        onUpdate: ({ editor }) => {
            if (onChange) {
                const djot = serializeToDjot(editor.getJSON());
                onChange(djot);
            }
        },
    });

    return {
        editor: editorInstance,
        getDjot: () => serializeToDjot(editorInstance.getJSON()),
        getHTML: () => editorInstance.getHTML(),
        setContent: (html) => editorInstance.commands.setContent(html),
        destroy: () => {
            if (editorInstance) {
                editorInstance.destroy();
                editorInstance = null;
            }
        },
        // Editor commands for toolbar
        commands: {
            bold: () => editorInstance.chain().focus().toggleBold().run(),
            italic: () => editorInstance.chain().focus().toggleItalic().run(),
            code: () => editorInstance.chain().focus().toggleCode().run(),
            highlight: () => editorInstance.chain().focus().toggleHighlight().run(),
            strikethrough: () => editorInstance.chain().focus().toggleStrike().run(),
            superscript: () => editorInstance.chain().focus().toggleSuperscript().run(),
            subscript: () => editorInstance.chain().focus().toggleSubscript().run(),
            djotInsert: () => editorInstance.chain().focus().toggleDjotInsert().run(),
            djotDelete: () => editorInstance.chain().focus().toggleDjotDelete().run(),
            heading: (level) => editorInstance.chain().focus().toggleHeading({ level }).run(),
            paragraph: () => editorInstance.chain().focus().setParagraph().run(),
            bulletList: () => editorInstance.chain().focus().toggleBulletList().run(),
            orderedList: () => editorInstance.chain().focus().toggleOrderedList().run(),
            taskList: () => editorInstance.chain().focus().toggleTaskList().run(),
            blockquote: () => editorInstance.chain().focus().toggleBlockquote().run(),
            codeBlock: () => editorInstance.chain().focus().toggleCodeBlock().run(),
            horizontalRule: () => editorInstance.chain().focus().setHorizontalRule().run(),
            link: (href) => {
                if (href) {
                    editorInstance.chain().focus().setLink({ href }).run();
                } else {
                    editorInstance.chain().focus().unsetLink().run();
                }
            },
            image: (src, alt) => editorInstance.chain().focus().setImage({ src, alt }).run(),
            table: (rows, cols) => editorInstance.chain().focus().insertTable({ rows, cols, withHeaderRow: true }).run(),
            djotDiv: (className) => editorInstance.chain().focus().setDjotDiv({ class: className }).run(),
            djotSpan: (className) => editorInstance.chain().focus().setDjotSpan({ class: className }).run(),
            djotFootnote: (label) => editorInstance.chain().focus().insertDjotFootnote({ label }).run(),
            undo: () => editorInstance.chain().focus().undo().run(),
            redo: () => editorInstance.chain().focus().redo().run(),
        },
        // Check if mark/node is active
        isActive: (name, attrs) => editorInstance.isActive(name, attrs),
    };
}

/**
 * Get the current editor instance
 */
export function getEditor() {
    return editorInstance;
}

// Export for global access
window.WpDjotVisualEditor = {
    init: initVisualEditor,
    getEditor,
    serializeToDjot,
};
