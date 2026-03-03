/**
 * Torchlight Annotations - Block Inspector Controls
 *
 * Adds quick-insert buttons for Torchlight code annotations to the Djot block sidebar.
 */
(function() {
    'use strict';

    const { createHigherOrderComponent } = wp.compose;
    const { InspectorControls } = wp.blockEditor;
    const { PanelBody, Button } = wp.components;
    const { createElement: el, Fragment } = wp.element;
    const { addFilter } = wp.hooks;

    // Torchlight annotations
    const annotations = [
        { label: 'Highlight', code: '// [tl! highlight]', desc: 'Highlight this line' },
        { label: 'Focus', code: '// [tl! focus]', desc: 'Focus this line (dim others)' },
        { label: 'Add (+)', code: '// [tl! ++]', desc: 'Mark as added (diff)' },
        { label: 'Remove (-)', code: '// [tl! --]', desc: 'Mark as removed (diff)' },
    ];

    const rangeAnnotations = [
        { label: 'Highlight Start', code: '// [tl! highlight:start]' },
        { label: 'Highlight End', code: '// [tl! highlight:end]' },
        { label: 'Focus Start', code: '// [tl! focus:start]' },
        { label: 'Focus End', code: '// [tl! focus:end]' },
    ];

    /**
     * Insert annotation at cursor position in the Djot textarea
     */
    function insertAnnotation(code) {
        const textarea = document.querySelector('.wpdjot-block textarea, .wpdjot-editor textarea');

        if (!textarea) {
            navigator.clipboard.writeText(code).then(function() {
                wp.data.dispatch('core/notices').createNotice(
                    'info',
                    'Copied: ' + code,
                    { type: 'snackbar', isDismissible: true }
                );
            });
            return;
        }

        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const value = textarea.value;

        textarea.value = value.substring(0, start) + code + value.substring(end);

        const newPos = start + code.length;
        textarea.selectionStart = newPos;
        textarea.selectionEnd = newPos;

        textarea.dispatchEvent(new Event('input', { bubbles: true }));
        textarea.focus();
    }

    /**
     * Annotation button component
     */
    function AnnotationButton({ label, code, desc }) {
        return el(Button, {
            variant: 'secondary',
            size: 'small',
            onClick: function() { insertAnnotation(code); },
            title: desc || code,
            style: { marginRight: '4px', marginBottom: '4px' }
        }, label);
    }

    /**
     * Add Inspector Controls to Djot block
     */
    const withTorchlightControls = createHigherOrderComponent(function(BlockEdit) {
        return function(props) {
            if (props.name !== 'wpdjot/djot' && props.name !== 'wp-djot/djot') {
                return el(BlockEdit, props);
            }

            return el(Fragment, null,
                el(BlockEdit, props),
                el(InspectorControls, null,
                    el(PanelBody, { title: 'Code Annotations', initialOpen: false },
                        el('p', { style: { fontSize: '12px', color: '#757575', marginTop: 0 } },
                            'Insert Torchlight annotations at cursor:'
                        ),
                        el('div', { style: { marginBottom: '12px' } },
                            annotations.map(function(a) {
                                return el(AnnotationButton, { key: a.code, label: a.label, code: a.code, desc: a.desc });
                            })
                        ),
                        el('p', { style: { fontSize: '12px', color: '#757575', marginBottom: '4px' } },
                            'Range annotations:'
                        ),
                        el('div', { style: { marginBottom: '12px' } },
                            rangeAnnotations.map(function(a) {
                                return el(AnnotationButton, { key: a.code, label: a.label, code: a.code });
                            })
                        ),
                        el('div', {
                            style: {
                                padding: '8px',
                                background: '#f0f0f0',
                                borderRadius: '4px',
                                fontSize: '11px',
                                lineHeight: '1.5'
                            }
                        },
                            el('strong', null, 'Fence options:'),
                            el('br'),
                            el('code', null, '``` php #'), ' — line numbers',
                            el('br'),
                            el('code', null, '``` php #=42'), ' — start at 42'
                        )
                    )
                )
            );
        };
    }, 'withTorchlightControls');

    addFilter(
        'editor.BlockEdit',
        'wpdjot/torchlight-controls',
        withTorchlightControls
    );
})();
