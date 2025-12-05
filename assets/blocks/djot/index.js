( function( wp ) {
    const { registerBlockType } = wp.blocks;
    const { useState, useEffect, useCallback, useRef } = wp.element;
    const { TextareaControl, PanelBody, ToggleControl, Placeholder, Spinner, ToolbarGroup, ToolbarButton, ToolbarDropdownMenu, Modal, TextControl, Button } = wp.components;
    const { InspectorControls, BlockControls, useBlockProps } = wp.blockEditor;
    const { __ } = wp.i18n;
    const apiFetch = wp.apiFetch;

    // Icons for toolbar
    const icons = {
        bold: wp.element.createElement( 'svg', { width: 24, height: 24, viewBox: '0 0 24 24' },
            wp.element.createElement( 'path', { d: 'M6 4v13h5.5c2.7 0 4.5-1.5 4.5-4 0-1.8-1.1-3.2-2.8-3.6v-.1c1.3-.4 2.3-1.6 2.3-3.1 0-2.1-1.6-3.2-4-3.2H6zm3 5V6.5h1.8c1 0 1.7.5 1.7 1.3s-.7 1.2-1.7 1.2H9zm0 5.5V11h2c1.2 0 1.9.6 1.9 1.5s-.7 1.5-1.9 1.5H9z' } )
        ),
        italic: wp.element.createElement( 'svg', { width: 24, height: 24, viewBox: '0 0 24 24' },
            wp.element.createElement( 'path', { d: 'M10 4v2h2.2l-2.7 11H7v2h7v-2h-2.2l2.7-11H17V4z' } )
        ),
        code: wp.element.createElement( 'svg', { width: 24, height: 24, viewBox: '0 0 24 24' },
            wp.element.createElement( 'path', { d: 'M9.4 16.6L4.8 12l4.6-4.6L8 6l-6 6 6 6 1.4-1.4zm5.2 0l4.6-4.6-4.6-4.6L16 6l6 6-6 6-1.4-1.4z' } )
        ),
        link: wp.element.createElement( 'svg', { width: 24, height: 24, viewBox: '0 0 24 24' },
            wp.element.createElement( 'path', { d: 'M3.9 12c0-1.7 1.4-3.1 3.1-3.1h4V7H7c-2.8 0-5 2.2-5 5s2.2 5 5 5h4v-1.9H7c-1.7 0-3.1-1.4-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.7 0 3.1 1.4 3.1 3.1s-1.4 3.1-3.1 3.1h-4V17h4c2.8 0 5-2.2 5-5s-2.2-5-5-5z' } )
        ),
        image: wp.element.createElement( 'svg', { width: 24, height: 24, viewBox: '0 0 24 24' },
            wp.element.createElement( 'path', { d: 'M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3 3.5-4.5 4.5 6H5l3.5-4.5z' } )
        ),
        heading: wp.element.createElement( 'svg', { width: 24, height: 24, viewBox: '0 0 24 24' },
            wp.element.createElement( 'path', { d: 'M5 4v3h5.5v12h3V7H19V4H5z' } )
        ),
        quote: wp.element.createElement( 'svg', { width: 24, height: 24, viewBox: '0 0 24 24' },
            wp.element.createElement( 'path', { d: 'M6 17h3l2-4V7H5v6h3zm8 0h3l2-4V7h-6v6h3z' } )
        ),
        listUl: wp.element.createElement( 'svg', { width: 24, height: 24, viewBox: '0 0 24 24' },
            wp.element.createElement( 'path', { d: 'M4 10.5c-.8 0-1.5.7-1.5 1.5s.7 1.5 1.5 1.5 1.5-.7 1.5-1.5-.7-1.5-1.5-1.5zm0-6c-.8 0-1.5.7-1.5 1.5S3.2 7.5 4 7.5 5.5 6.8 5.5 6 4.8 4.5 4 4.5zm0 12c-.8 0-1.5.7-1.5 1.5s.7 1.5 1.5 1.5 1.5-.7 1.5-1.5-.7-1.5-1.5-1.5zM7 19h14v-2H7v2zm0-6h14v-2H7v2zm0-8v2h14V5H7z' } )
        ),
        listOl: wp.element.createElement( 'svg', { width: 24, height: 24, viewBox: '0 0 24 24' },
            wp.element.createElement( 'path', { d: 'M2 17h2v.5H3v1h1v.5H2v1h3v-4H2v1zm1-9h1V4H2v1h1v3zm-1 3h1.8L2 13.1v.9h3v-1H3.2L5 10.9V10H2v1zm5-6v2h14V5H7zm0 14h14v-2H7v2zm0-6h14v-2H7v2z' } )
        ),
        codeBlock: wp.element.createElement( 'svg', { width: 24, height: 24, viewBox: '0 0 24 24' },
            wp.element.createElement( 'path', { d: 'M9.4 16.6L4.8 12l4.6-4.6L8 6l-6 6 6 6 1.4-1.4zm5.2 0l4.6-4.6-4.6-4.6L16 6l6 6-6 6-1.4-1.4z' } ),
            wp.element.createElement( 'rect', { x: 2, y: 2, width: 20, height: 20, fill: 'none', stroke: 'currentColor', strokeWidth: 1.5, rx: 2 } )
        ),
        superscript: wp.element.createElement( 'svg', { width: 24, height: 24, viewBox: '0 0 24 24' },
            wp.element.createElement( 'path', { d: 'M16 7.5c0-1.1.9-2 2-2s2 .9 2 2h-1c0-.6-.4-1-1-1s-1 .4-1 1 .4 1 1 1h1v1h-1c-1.1 0-2-.9-2-2zm-8 5L4 19h2.5l2-3.5 2 3.5H13l-4-6.5L13 6h-2.5L8.5 9.5 6.5 6H4l4 6.5z' } )
        ),
        subscript: wp.element.createElement( 'svg', { width: 24, height: 24, viewBox: '0 0 24 24' },
            wp.element.createElement( 'path', { d: 'M16 15.5c0-1.1.9-2 2-2s2 .9 2 2h-1c0-.6-.4-1-1-1s-1 .4-1 1 .4 1 1 1h1v1h-1c-1.1 0-2-.9-2-2zM8 12.5L4 19h2.5l2-3.5 2 3.5H13l-4-6.5L13 6h-2.5L8.5 9.5 6.5 6H4l4 6.5z' } )
        ),
        highlight: wp.element.createElement( 'svg', { width: 24, height: 24, viewBox: '0 0 24 24' },
            wp.element.createElement( 'path', { d: 'M17 5H7c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 12H7V7h10v10z', fill: 'currentColor' } ),
            wp.element.createElement( 'rect', { x: 8, y: 8, width: 8, height: 8, fill: 'yellow', opacity: 0.5 } )
        ),
        insert: wp.element.createElement( 'svg', { width: 24, height: 24, viewBox: '0 0 24 24' },
            wp.element.createElement( 'path', { d: 'M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z' } )
        ),
        delete: wp.element.createElement( 'svg', { width: 24, height: 24, viewBox: '0 0 24 24' },
            wp.element.createElement( 'path', { d: 'M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM8 9h8v10H8V9zm7.5-5l-1-1h-5l-1 1H5v2h14V4h-3.5z' } )
        ),
        strikethrough: wp.element.createElement( 'svg', { width: 24, height: 24, viewBox: '0 0 24 24' },
            wp.element.createElement( 'path', { d: 'M10 19h4v-3h-4v3zM5 4v3h5v3h4V7h5V4H5zM3 14h18v-2H3v2z' } )
        ),
        horizontalRule: wp.element.createElement( 'svg', { width: 24, height: 24, viewBox: '0 0 24 24' },
            wp.element.createElement( 'path', { d: 'M4 11h16v2H4z' } )
        ),
        footnote: wp.element.createElement( 'svg', { width: 24, height: 24, viewBox: '0 0 24 24' },
            wp.element.createElement( 'text', { x: 6, y: 16, fontSize: 12, fontWeight: 'bold' }, 'F' ),
            wp.element.createElement( 'text', { x: 14, y: 12, fontSize: 8 }, '1' )
        ),
        div: wp.element.createElement( 'svg', { width: 24, height: 24, viewBox: '0 0 24 24' },
            wp.element.createElement( 'rect', { x: 3, y: 3, width: 18, height: 18, fill: 'none', stroke: 'currentColor', strokeWidth: 2, strokeDasharray: '4 2' } )
        ),
        span: wp.element.createElement( 'svg', { width: 24, height: 24, viewBox: '0 0 24 24' },
            wp.element.createElement( 'path', { d: 'M4 9h16v6H4z', fill: 'none', stroke: 'currentColor', strokeWidth: 2 } )
        ),
        table: wp.element.createElement( 'svg', { width: 24, height: 24, viewBox: '0 0 24 24' },
            wp.element.createElement( 'path', { d: 'M3 3v18h18V3H3zm8 16H5v-6h6v6zm0-8H5V5h6v6zm8 8h-6v-6h6v6zm0-8h-6V5h6v6z' } )
        ),
    };

    registerBlockType( 'wp-djot/djot', {
        edit: function( props ) {
            const { attributes, setAttributes } = props;
            const { content } = attributes;
            const [ preview, setPreview ] = useState( '' );
            const [ isPreviewMode, setIsPreviewMode ] = useState( false );
            const [ isLoading, setIsLoading ] = useState( false );
            const [ showLinkModal, setShowLinkModal ] = useState( false );
            const [ showImageModal, setShowImageModal ] = useState( false );
            const [ linkUrl, setLinkUrl ] = useState( '' );
            const [ linkText, setLinkText ] = useState( '' );
            const [ imageUrl, setImageUrl ] = useState( '' );
            const [ imageAlt, setImageAlt ] = useState( '' );
            const textareaRef = useRef( null );
            const [ selectionStart, setSelectionStart ] = useState( 0 );
            const [ selectionEnd, setSelectionEnd ] = useState( 0 );

            const blockProps = useBlockProps( {
                className: 'wp-djot-block',
            } );

            // Track selection in textarea
            function updateSelection() {
                if ( textareaRef.current ) {
                    const textarea = textareaRef.current.querySelector( 'textarea' );
                    if ( textarea ) {
                        setSelectionStart( textarea.selectionStart );
                        setSelectionEnd( textarea.selectionEnd );
                    }
                }
            }

            // Track scroll position for undo/redo recovery
            const lastScrollY = useRef( window.scrollY );
            const lastScrollX = useRef( window.scrollX );
            const isInternalChange = useRef( false );

            // Continuously track scroll position
            useEffect( function() {
                function saveScroll() {
                    lastScrollY.current = window.scrollY;
                    lastScrollX.current = window.scrollX;
                }

                document.addEventListener( 'scroll', saveScroll, { passive: true, capture: true } );
                document.addEventListener( 'keydown', saveScroll, { passive: true } );
                document.addEventListener( 'mousedown', saveScroll, { passive: true } );

                return function() {
                    document.removeEventListener( 'scroll', saveScroll, { capture: true } );
                    document.removeEventListener( 'keydown', saveScroll );
                    document.removeEventListener( 'mousedown', saveScroll );
                };
            }, [] );

            // Restore scroll after any content change (handles undo/redo and toolbar)
            useEffect( function() {
                if ( isInternalChange.current ) {
                    isInternalChange.current = false;
                    return;
                }

                // External content change (undo/redo) - restore scroll
                if ( ! isPreviewMode ) {
                    var savedY = lastScrollY.current;
                    var savedX = lastScrollX.current;
                    requestAnimationFrame( function() {
                        window.scrollTo( savedX, savedY );
                    } );
                }
            }, [ content ] );

            // Restore focus and scroll position after React re-render
            function restoreFocus( textarea, cursorPos ) {
                const scrollY = window.scrollY;
                const scrollX = window.scrollX;
                isInternalChange.current = true;

                requestAnimationFrame( function() {
                    if ( textarea ) {
                        textarea.focus( { preventScroll: true } );
                        textarea.setSelectionRange( cursorPos, cursorPos );
                    }
                    window.scrollTo( scrollX, scrollY );
                } );
            }

            // Insert text at cursor or wrap selection
            function insertMarkup( before, after, placeholder ) {
                after = after || '';
                placeholder = placeholder || '';

                const textarea = textareaRef.current ? textareaRef.current.querySelector( 'textarea' ) : null;
                if ( ! textarea ) return;

                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                const text = content || '';
                const selectedText = text.substring( start, end );

                let newText;
                let newCursorPos;

                if ( selectedText ) {
                    // Wrap selection
                    newText = text.substring( 0, start ) + before + selectedText + after + text.substring( end );
                    newCursorPos = start + before.length + selectedText.length + after.length;
                } else {
                    // Insert placeholder
                    newText = text.substring( 0, start ) + before + placeholder + after + text.substring( end );
                    newCursorPos = start + before.length + placeholder.length;
                }

                setAttributes( { content: newText } );
                restoreFocus( textarea, newCursorPos );
            }

            // Insert block-level markup (on new line)
            function insertBlockMarkup( markup, placeholder ) {
                const textarea = textareaRef.current ? textareaRef.current.querySelector( 'textarea' ) : null;
                if ( ! textarea ) return;

                const start = textarea.selectionStart;
                const text = content || '';

                // Find start of current line
                let lineStart = start;
                while ( lineStart > 0 && text[ lineStart - 1 ] !== '\n' ) {
                    lineStart--;
                }

                // Check if we need a newline before
                const needsNewlineBefore = lineStart > 0 && text[ lineStart - 1 ] !== '\n';
                const prefix = needsNewlineBefore ? '\n' : '';

                const newText = text.substring( 0, lineStart ) + prefix + markup + placeholder + text.substring( start );
                const newCursorPos = lineStart + prefix.length + markup.length + placeholder.length;

                setAttributes( { content: newText } );
                restoreFocus( textarea, newCursorPos );
            }

            // Insert multi-line block (like code blocks, divs)
            function insertMultiLineBlock( startTag, endTag, placeholder ) {
                const textarea = textareaRef.current ? textareaRef.current.querySelector( 'textarea' ) : null;
                if ( ! textarea ) return;

                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                const text = content || '';
                const selectedText = text.substring( start, end );

                const contentToWrap = selectedText || placeholder;
                const newText = text.substring( 0, start ) + startTag + '\n' + contentToWrap + '\n' + endTag + text.substring( end );
                const newCursorPos = start + startTag.length + 1 + contentToWrap.length;

                setAttributes( { content: newText } );
                restoreFocus( textarea, newCursorPos );
            }

            // Toolbar button handlers
            function onBold() { insertMarkup( '*', '*', 'bold text' ); }
            function onItalic() { insertMarkup( '_', '_', 'italic text' ); }
            function onCode() { insertMarkup( '`', '`', 'code' ); }
            function onSuperscript() { insertMarkup( '^', '^', 'superscript' ); }
            function onSubscript() { insertMarkup( '~', '~', 'subscript' ); }
            function onHighlight() { insertMarkup( '{=', '=}', 'highlighted' ); }
            function onInsert() { insertMarkup( '{+', '+}', 'inserted' ); }
            function onDelete() { insertMarkup( '{-', '-}', 'deleted' ); }
            function onStrikethrough() { insertMarkup( '{~', '~}', 'strikethrough' ); }
            function onSpan() { insertMarkup( '[', ']{.class}', 'text' ); }

            function onLink() {
                const textarea = textareaRef.current ? textareaRef.current.querySelector( 'textarea' ) : null;
                if ( textarea ) {
                    const selectedText = ( content || '' ).substring( textarea.selectionStart, textarea.selectionEnd );
                    setLinkText( selectedText || '' );
                    setLinkUrl( 'https://' );
                }
                setShowLinkModal( true );
            }

            function onInsertLink() {
                if ( linkText.trim() ) {
                    insertMarkup( '[' + linkText + '](', ')', linkUrl );
                } else {
                    // Use autolink syntax when no text provided
                    insertMarkup( '<', '>', linkUrl );
                }
                setShowLinkModal( false );
                setLinkUrl( '' );
                setLinkText( '' );
            }

            function onImage() {
                setImageUrl( 'https://' );
                setImageAlt( '' );
                setShowImageModal( true );
            }

            function onInsertImage() {
                insertMarkup( '![' + imageAlt + '](', ')', imageUrl );
                setShowImageModal( false );
                setImageUrl( '' );
                setImageAlt( '' );
            }

            function onHeading( level ) {
                const hashes = '#'.repeat( level ) + ' ';
                insertBlockMarkup( hashes, 'Heading ' + level );
            }

            function onBlockquote() { insertBlockMarkup( '> ', 'quote' ); }
            function onListUl() { insertBlockMarkup( '- ', 'list item' ); }
            function onListOl() { insertBlockMarkup( '1. ', 'list item' ); }
            function onHorizontalRule() { insertBlockMarkup( '\n---\n', '' ); }
            function onCodeBlock() { insertMultiLineBlock( '\n```', '```\n', 'code here' ); }
            function onDiv() { insertMultiLineBlock( '\n::: note', ':::\n', 'content' ); }
            function onFootnote() { insertMarkup( '[^', ']', 'note' ); }

            function onTable() {
                const tableTemplate = '| Column 1 | Column 2 | Column 3 |\n|----------|----------|----------|\n| Cell 1   | Cell 2   | Cell 3   |\n| Cell 4   | Cell 5   | Cell 6   |';
                insertMultiLineBlock( '\n', '\n', tableTemplate );
            }

            // Keyboard shortcut handler for textarea
            function handleTextareaKeyDown( e ) {
                const isMod = e.ctrlKey || e.metaKey;
                if ( ! isMod ) return;

                var handled = false;

                if ( e.shiftKey ) {
                    switch ( e.key.toLowerCase() ) {
                        case 'x': onStrikethrough(); handled = true; break;
                        case 'e': onCodeBlock(); handled = true; break;
                        case 'h': onHighlight(); handled = true; break;
                        case 'i': onImage(); handled = true; break;
                        case '.': onBlockquote(); handled = true; break;
                        case '8': onListUl(); handled = true; break;
                        case '7': onListOl(); handled = true; break;
                    }
                } else {
                    switch ( e.key.toLowerCase() ) {
                        case 'b': onBold(); handled = true; break;
                        case 'i': onItalic(); handled = true; break;
                        case 'e': onCode(); handled = true; break;
                        case 'k': onLink(); handled = true; break;
                        case '.': onSuperscript(); handled = true; break;
                        case ',': onSubscript(); handled = true; break;
                        case '1': onHeading( 1 ); handled = true; break;
                        case '2': onHeading( 2 ); handled = true; break;
                        case '3': onHeading( 3 ); handled = true; break;
                    }
                }

                if ( handled ) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            }

            // Debounced preview fetch
            const fetchPreview = useCallback(
                debounce( function( djotContent ) {
                    if ( ! djotContent.trim() ) {
                        setPreview( '' );
                        return;
                    }

                    setIsLoading( true );
                    apiFetch( {
                        path: '/wp-djot/v1/render',
                        method: 'POST',
                        data: { content: djotContent },
                    } )
                        .then( function( response ) {
                            setPreview( response.html || '' );
                            setIsLoading( false );
                        } )
                        .catch( function() {
                            setPreview( '<p style="color:red;">Error rendering Djot</p>' );
                            setIsLoading( false );
                        } );
                }, 500 ),
                []
            );

            useEffect( function() {
                if ( isPreviewMode && content ) {
                    fetchPreview( content );
                }
            }, [ content, isPreviewMode ] );

            // Apply syntax highlighting after preview renders
            useEffect( function() {
                if ( isPreviewMode && preview && ! isLoading && typeof window.hljs !== 'undefined' ) {
                    // Small delay to ensure DOM is updated
                    setTimeout( function() {
                        var previewEl = document.querySelector( '.wp-djot-preview.djot-content' );
                        if ( previewEl ) {
                            previewEl.querySelectorAll( 'pre code' ).forEach( function( block ) {
                                window.hljs.highlightElement( block );
                            } );
                        }
                    }, 10 );
                }
            }, [ preview, isLoading, isPreviewMode ] );

            // ESC key exits preview mode
            useEffect( function() {
                if ( ! isPreviewMode ) return;

                function handleKeyDown( e ) {
                    if ( e.key === 'Escape' ) {
                        e.preventDefault();
                        setIsPreviewMode( false );
                    }
                }

                document.addEventListener( 'keydown', handleKeyDown );
                return function() {
                    document.removeEventListener( 'keydown', handleKeyDown );
                };
            }, [ isPreviewMode ] );

            function onChangeContent( newContent ) {
                setAttributes( { content: newContent } );
            }

            // Heading dropdown controls
            const headingControls = [
                { title: __( 'Heading 1', 'wp-djot' ), onClick: function() { onHeading( 1 ); } },
                { title: __( 'Heading 2', 'wp-djot' ), onClick: function() { onHeading( 2 ); } },
                { title: __( 'Heading 3', 'wp-djot' ), onClick: function() { onHeading( 3 ); } },
                { title: __( 'Heading 4', 'wp-djot' ), onClick: function() { onHeading( 4 ); } },
                { title: __( 'Heading 5', 'wp-djot' ), onClick: function() { onHeading( 5 ); } },
                { title: __( 'Heading 6', 'wp-djot' ), onClick: function() { onHeading( 6 ); } },
            ];

            return wp.element.createElement(
                'div',
                blockProps,
                // Block Controls (Toolbar)
                wp.element.createElement(
                    BlockControls,
                    null,
                    // Inline formatting group
                    wp.element.createElement(
                        ToolbarGroup,
                        null,
                        wp.element.createElement( ToolbarButton, {
                            icon: icons.bold,
                            label: __( 'Bold (*text*)', 'wp-djot' ),
                            onClick: onBold,
                        } ),
                        wp.element.createElement( ToolbarButton, {
                            icon: icons.italic,
                            label: __( 'Italic (_text_)', 'wp-djot' ),
                            onClick: onItalic,
                        } ),
                        wp.element.createElement( ToolbarButton, {
                            icon: icons.code,
                            label: __( 'Inline Code (`code`)', 'wp-djot' ),
                            onClick: onCode,
                        } ),
                        wp.element.createElement( ToolbarButton, {
                            icon: icons.strikethrough,
                            label: __( 'Strikethrough ({~text~})', 'wp-djot' ),
                            onClick: onStrikethrough,
                        } )
                    ),
                    // Links and media group
                    wp.element.createElement(
                        ToolbarGroup,
                        null,
                        wp.element.createElement( ToolbarButton, {
                            icon: icons.link,
                            label: __( 'Link ([text](url))', 'wp-djot' ),
                            onClick: onLink,
                        } ),
                        wp.element.createElement( ToolbarButton, {
                            icon: icons.image,
                            label: __( 'Image (![alt](url))', 'wp-djot' ),
                            onClick: onImage,
                        } )
                    ),
                    // Headings dropdown
                    wp.element.createElement(
                        ToolbarGroup,
                        null,
                        wp.element.createElement( ToolbarDropdownMenu, {
                            icon: icons.heading,
                            label: __( 'Headings', 'wp-djot' ),
                            controls: headingControls,
                        } )
                    ),
                    // Block elements group
                    wp.element.createElement(
                        ToolbarGroup,
                        null,
                        wp.element.createElement( ToolbarButton, {
                            icon: icons.quote,
                            label: __( 'Blockquote (> quote)', 'wp-djot' ),
                            onClick: onBlockquote,
                        } ),
                        wp.element.createElement( ToolbarButton, {
                            icon: icons.listUl,
                            label: __( 'Unordered List (- item)', 'wp-djot' ),
                            onClick: onListUl,
                        } ),
                        wp.element.createElement( ToolbarButton, {
                            icon: icons.listOl,
                            label: __( 'Ordered List (1. item)', 'wp-djot' ),
                            onClick: onListOl,
                        } ),
                        wp.element.createElement( ToolbarButton, {
                            icon: icons.codeBlock,
                            label: __( 'Code Block (```)', 'wp-djot' ),
                            onClick: onCodeBlock,
                        } )
                    ),
                    // Djot-specific formatting
                    wp.element.createElement(
                        ToolbarGroup,
                        null,
                        wp.element.createElement( ToolbarButton, {
                            icon: icons.superscript,
                            label: __( 'Superscript (^text^)', 'wp-djot' ),
                            onClick: onSuperscript,
                        } ),
                        wp.element.createElement( ToolbarButton, {
                            icon: icons.subscript,
                            label: __( 'Subscript (~text~)', 'wp-djot' ),
                            onClick: onSubscript,
                        } ),
                        wp.element.createElement( ToolbarButton, {
                            icon: icons.highlight,
                            label: __( 'Highlight ({=text=})', 'wp-djot' ),
                            onClick: onHighlight,
                        } )
                    ),
                    // Insert/Delete/More
                    wp.element.createElement(
                        ToolbarGroup,
                        null,
                        wp.element.createElement( ToolbarButton, {
                            icon: icons.insert,
                            label: __( 'Insert ({+text+})', 'wp-djot' ),
                            onClick: onInsert,
                        } ),
                        wp.element.createElement( ToolbarButton, {
                            icon: icons.delete,
                            label: __( 'Delete ({-text-})', 'wp-djot' ),
                            onClick: onDelete,
                        } ),
                        wp.element.createElement( ToolbarButton, {
                            icon: icons.horizontalRule,
                            label: __( 'Horizontal Rule (---)', 'wp-djot' ),
                            onClick: onHorizontalRule,
                        } )
                    ),
                    // Advanced group
                    wp.element.createElement(
                        ToolbarGroup,
                        null,
                        wp.element.createElement( ToolbarButton, {
                            icon: icons.table,
                            label: __( 'Table', 'wp-djot' ),
                            onClick: onTable,
                        } ),
                        wp.element.createElement( ToolbarButton, {
                            icon: icons.div,
                            label: __( 'Div Block (::: class)', 'wp-djot' ),
                            onClick: onDiv,
                        } ),
                        wp.element.createElement( ToolbarButton, {
                            icon: icons.span,
                            label: __( 'Span with Class ([text]{.class})', 'wp-djot' ),
                            onClick: onSpan,
                        } ),
                        wp.element.createElement( ToolbarButton, {
                            icon: icons.footnote,
                            label: __( 'Footnote ([^note])', 'wp-djot' ),
                            onClick: onFootnote,
                        } )
                    )
                ),
                // Inspector Controls (Sidebar)
                wp.element.createElement(
                    InspectorControls,
                    null,
                    wp.element.createElement(
                        PanelBody,
                        { title: __( 'Djot Settings', 'wp-djot' ) },
                        wp.element.createElement( ToggleControl, {
                            label: __( 'Show Preview', 'wp-djot' ),
                            checked: isPreviewMode,
                            onChange: setIsPreviewMode,
                        } )
                    ),
                    wp.element.createElement(
                        PanelBody,
                        { title: __( 'Syntax Help', 'wp-djot' ), initialOpen: false },
                        wp.element.createElement( 'div', { className: 'wp-djot-syntax-help' },
                            wp.element.createElement( 'p', null, wp.element.createElement( 'strong', null, 'Inline:' ) ),
                            wp.element.createElement( 'code', null, '*bold*' ), ' ',
                            wp.element.createElement( 'code', null, '_italic_' ), ' ',
                            wp.element.createElement( 'code', null, '`code`' ),
                            wp.element.createElement( 'p', null, wp.element.createElement( 'strong', null, 'Links:' ) ),
                            wp.element.createElement( 'code', null, '[text](url)' ),
                            wp.element.createElement( 'p', null, wp.element.createElement( 'strong', null, 'Djot-specific:' ) ),
                            wp.element.createElement( 'code', null, '^super^' ), ' ',
                            wp.element.createElement( 'code', null, '~sub~' ), ' ',
                            wp.element.createElement( 'code', null, '{=highlight=}' ),
                            wp.element.createElement( 'p', null,
                                wp.element.createElement( 'a', { href: 'https://djot.net/', target: '_blank' }, __( 'Full Djot Documentation →', 'wp-djot' ) )
                            )
                        )
                    ),
                    wp.element.createElement(
                        PanelBody,
                        { title: __( 'Keyboard Shortcuts', 'wp-djot' ), initialOpen: false },
                        wp.element.createElement( 'div', { className: 'wp-djot-shortcuts-help', style: { fontSize: '12px' } },
                            wp.element.createElement( 'p', { style: { marginBottom: '8px' } },
                                wp.element.createElement( 'strong', null, 'Formatting:' )
                            ),
                            wp.element.createElement( 'div', null, wp.element.createElement( 'kbd', null, 'Ctrl+B' ), ' Bold' ),
                            wp.element.createElement( 'div', null, wp.element.createElement( 'kbd', null, 'Ctrl+I' ), ' Italic' ),
                            wp.element.createElement( 'div', null, wp.element.createElement( 'kbd', null, 'Ctrl+E' ), ' Inline code' ),
                            wp.element.createElement( 'div', null, wp.element.createElement( 'kbd', null, 'Ctrl+K' ), ' Link' ),
                            wp.element.createElement( 'p', { style: { marginTop: '12px', marginBottom: '8px' } },
                                wp.element.createElement( 'strong', null, 'Headings:' )
                            ),
                            wp.element.createElement( 'div', null, wp.element.createElement( 'kbd', null, 'Ctrl+1/2/3' ), ' H1/H2/H3' ),
                            wp.element.createElement( 'p', { style: { marginTop: '12px', marginBottom: '8px' } },
                                wp.element.createElement( 'strong', null, 'Blocks (Ctrl+Shift):' )
                            ),
                            wp.element.createElement( 'div', null, wp.element.createElement( 'kbd', null, 'Ctrl+Shift+E' ), ' Code block' ),
                            wp.element.createElement( 'div', null, wp.element.createElement( 'kbd', null, 'Ctrl+Shift+.' ), ' Blockquote' ),
                            wp.element.createElement( 'div', null, wp.element.createElement( 'kbd', null, 'Ctrl+Shift+8' ), ' Bullet list' ),
                            wp.element.createElement( 'div', null, wp.element.createElement( 'kbd', null, 'Ctrl+Shift+7' ), ' Numbered list' ),
                            wp.element.createElement( 'p', { style: { marginTop: '12px', marginBottom: '8px' } },
                                wp.element.createElement( 'strong', null, 'Other:' )
                            ),
                            wp.element.createElement( 'div', null, wp.element.createElement( 'kbd', null, 'Ctrl+.' ), ' Superscript' ),
                            wp.element.createElement( 'div', null, wp.element.createElement( 'kbd', null, 'Ctrl+,' ), ' Subscript' ),
                            wp.element.createElement( 'div', null, wp.element.createElement( 'kbd', null, 'Ctrl+Shift+H' ), ' Highlight' ),
                            wp.element.createElement( 'div', null, wp.element.createElement( 'kbd', null, 'Ctrl+Shift+X' ), ' Strikethrough' ),
                            wp.element.createElement( 'div', null, wp.element.createElement( 'kbd', null, 'ESC' ), ' Exit preview' )
                        )
                    )
                ),
                // Link Modal
                showLinkModal && wp.element.createElement(
                    Modal,
                    {
                        title: __( 'Insert Link', 'wp-djot' ),
                        onRequestClose: function() { setShowLinkModal( false ); },
                    },
                    wp.element.createElement( TextControl, {
                        label: __( 'URL', 'wp-djot' ),
                        value: linkUrl,
                        onChange: setLinkUrl,
                        type: 'url',
                    } ),
                    wp.element.createElement( TextControl, {
                        label: __( 'Link Text (optional, leave empty for autolink)', 'wp-djot' ),
                        value: linkText,
                        onChange: setLinkText,
                    } ),
                    wp.element.createElement(
                        'div',
                        { style: { marginTop: '16px' } },
                        wp.element.createElement( Button, {
                            variant: 'primary',
                            onClick: onInsertLink,
                        }, __( 'Insert Link', 'wp-djot' ) ),
                        wp.element.createElement( Button, {
                            variant: 'secondary',
                            onClick: function() { setShowLinkModal( false ); },
                            style: { marginLeft: '8px' },
                        }, __( 'Cancel', 'wp-djot' ) )
                    )
                ),
                // Image Modal
                showImageModal && wp.element.createElement(
                    Modal,
                    {
                        title: __( 'Insert Image', 'wp-djot' ),
                        onRequestClose: function() { setShowImageModal( false ); },
                    },
                    wp.element.createElement( TextControl, {
                        label: __( 'Image URL', 'wp-djot' ),
                        value: imageUrl,
                        onChange: setImageUrl,
                        type: 'url',
                    } ),
                    wp.element.createElement( TextControl, {
                        label: __( 'Alt Text (optional)', 'wp-djot' ),
                        value: imageAlt,
                        onChange: setImageAlt,
                    } ),
                    wp.element.createElement(
                        'div',
                        { style: { marginTop: '16px' } },
                        wp.element.createElement( Button, {
                            variant: 'primary',
                            onClick: onInsertImage,
                        }, __( 'Insert Image', 'wp-djot' ) ),
                        wp.element.createElement( Button, {
                            variant: 'secondary',
                            onClick: function() { setShowImageModal( false ); },
                            style: { marginLeft: '8px' },
                        }, __( 'Cancel', 'wp-djot' ) )
                    )
                ),
                // Main content area
                content || isPreviewMode
                    ? wp.element.createElement(
                          'div',
                          { className: 'wp-djot-block-wrapper', ref: textareaRef },
                          ! isPreviewMode &&
                              wp.element.createElement( TextareaControl, {
                                  label: __( 'Djot Content', 'wp-djot' ),
                                  value: content,
                                  onChange: onChangeContent,
                                  onSelect: updateSelection,
                                  onClick: updateSelection,
                                  onKeyUp: updateSelection,
                                  onKeyDown: handleTextareaKeyDown,
                                  rows: 12,
                                  className: 'wp-djot-editor',
                                  placeholder: __( 'Write your Djot markup here...\n\n# Heading\n\nThis is _emphasized_ and *strong* text.\n\n- List item 1\n- List item 2', 'wp-djot' ),
                              } ),
                          isPreviewMode &&
                              wp.element.createElement(
                                  'div',
                                  { className: 'wp-djot-preview-wrapper' },
                                  wp.element.createElement(
                                      'div',
                                      { className: 'wp-djot-preview-header' },
                                      wp.element.createElement( 'span', null, __( 'Preview', 'wp-djot' ) ),
                                      wp.element.createElement(
                                          'button',
                                          {
                                              className: 'wp-djot-edit-button',
                                              onClick: function() { setIsPreviewMode( false ); },
                                              title: __( 'Press ESC to exit preview', 'wp-djot' ),
                                          },
                                          __( 'Edit (ESC)', 'wp-djot' )
                                      )
                                  ),
                                  isLoading
                                      ? wp.element.createElement( Spinner, null )
                                      : wp.element.createElement( 'div', {
                                            className: 'wp-djot-preview djot-content',
                                            dangerouslySetInnerHTML: { __html: preview },
                                        } )
                              )
                      )
                    : wp.element.createElement(
                          Placeholder,
                          {
                              icon: 'editor-code',
                              label: __( 'Djot', 'wp-djot' ),
                              instructions: __( 'Write content using Djot markup language. Use the toolbar above for formatting.', 'wp-djot' ),
                          },
                          wp.element.createElement(
                              'div',
                              { ref: textareaRef, style: { width: '100%' } },
                              wp.element.createElement( TextareaControl, {
                                  value: content,
                                  onChange: onChangeContent,
                                  onSelect: updateSelection,
                                  onClick: updateSelection,
                                  onKeyUp: updateSelection,
                                  onKeyDown: handleTextareaKeyDown,
                                  rows: 8,
                                  className: 'wp-djot-editor',
                                  placeholder: __( '# Hello World\n\nThis is _emphasized_ and *strong* text.', 'wp-djot' ),
                              } )
                          )
                      )
            );
        },

        save: function() {
            // Dynamic block - rendered on server
            return null;
        },
    } );

    // Simple debounce helper
    function debounce( func, wait ) {
        var timeout;
        return function() {
            var context = this;
            var args = arguments;
            clearTimeout( timeout );
            timeout = setTimeout( function() {
                func.apply( context, args );
            }, wait );
        };
    }
} )( window.wp );
