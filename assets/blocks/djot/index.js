( function( wp ) {
    const { registerBlockType } = wp.blocks;
    const { useState, useEffect, useCallback, useRef } = wp.element;
    const { TextareaControl, PanelBody, ToggleControl, Placeholder, Spinner, ToolbarGroup, ToolbarButton, ToolbarDropdownMenu, Modal, TextControl, Button, RangeControl } = wp.components;
    const { PlainText } = wp.blockEditor;
    const { InspectorControls, BlockControls, useBlockProps } = wp.blockEditor;
    const { useDispatch } = wp.data;
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
            wp.element.createElement( 'line', { x1: 4, y1: 12, x2: 8, y2: 12, stroke: 'currentColor', strokeWidth: 2 } ),
            wp.element.createElement( 'circle', { cx: 12, cy: 12, r: 1.5, fill: 'currentColor' } ),
            wp.element.createElement( 'line', { x1: 16, y1: 12, x2: 20, y2: 12, stroke: 'currentColor', strokeWidth: 2 } )
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
        formatTable: wp.element.createElement( 'svg', { width: 24, height: 24, viewBox: '0 0 24 24' },
            wp.element.createElement( 'path', { d: 'M3 3v18h18V3H3zm8 16H5v-6h6v6zm0-8H5V5h6v6zm8 8h-6v-6h6v6zm0-8h-6V5h6v6z' } ),
            wp.element.createElement( 'path', { d: 'M17 17l4 4m0-4l-4 4', stroke: 'currentColor', strokeWidth: 2, fill: 'none' } )
        ),
        taskList: wp.element.createElement( 'svg', { width: 24, height: 24, viewBox: '0 0 24 24' },
            wp.element.createElement( 'rect', { x: 3, y: 4, width: 4, height: 4, fill: 'none', stroke: 'currentColor', strokeWidth: 1.5 } ),
            wp.element.createElement( 'path', { d: 'M4 6l1 1 2-2', stroke: 'currentColor', strokeWidth: 1.5, fill: 'none' } ),
            wp.element.createElement( 'line', { x1: 9, y1: 6, x2: 21, y2: 6, stroke: 'currentColor', strokeWidth: 1.5 } ),
            wp.element.createElement( 'rect', { x: 3, y: 10, width: 4, height: 4, fill: 'none', stroke: 'currentColor', strokeWidth: 1.5 } ),
            wp.element.createElement( 'line', { x1: 9, y1: 12, x2: 21, y2: 12, stroke: 'currentColor', strokeWidth: 1.5 } ),
            wp.element.createElement( 'rect', { x: 3, y: 16, width: 4, height: 4, fill: 'none', stroke: 'currentColor', strokeWidth: 1.5 } ),
            wp.element.createElement( 'line', { x1: 9, y1: 18, x2: 21, y2: 18, stroke: 'currentColor', strokeWidth: 1.5 } )
        ),
        video: wp.element.createElement( 'svg', { width: 24, height: 24, viewBox: '0 0 24 24' },
            wp.element.createElement( 'path', { d: 'M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z' } )
        ),
    };

    registerBlockType( 'wpdjot/djot', {
        edit: function( props ) {
            const { attributes, setAttributes } = props;
            const { content } = attributes;
            const { __unstableMarkLastChangeAsPersistent: markUndoBoundary } = useDispatch( 'core/block-editor' );
            const [ preview, setPreview ] = useState( '' );
            const [ isPreviewMode, setIsPreviewMode ] = useState( false );
            const [ isLoading, setIsLoading ] = useState( false );
            const [ showLinkModal, setShowLinkModal ] = useState( false );
            const [ showImageModal, setShowImageModal ] = useState( false );
            const [ showTableModal, setShowTableModal ] = useState( false );
            const [ linkUrl, setLinkUrl ] = useState( '' );
            const [ linkText, setLinkText ] = useState( '' );
            const [ imageUrl, setImageUrl ] = useState( '' );
            const [ imageAlt, setImageAlt ] = useState( '' );
            const [ tableCols, setTableCols ] = useState( 3 );
            const [ tableRows, setTableRows ] = useState( 2 );
            const [ cursorInTable, setCursorInTable ] = useState( false );
            const [ showImportModal, setShowImportModal ] = useState( false );
            const [ importType, setImportType ] = useState( 'markdown' );
            const [ importInput, setImportInput ] = useState( '' );
            const [ djotPreview, setDjotPreview ] = useState( '' );
            const [ isConverting, setIsConverting ] = useState( false );
            const [ showTaskListModal, setShowTaskListModal ] = useState( false );
            const [ taskListItems, setTaskListItems ] = useState( [ { text: '', checked: false } ] );
            const [ showDefListModal, setShowDefListModal ] = useState( false );
            const [ defListTerms, setDefListTerms ] = useState( [ '' ] );
            const [ defListDefinitions, setDefListDefinitions ] = useState( [ '' ] );
            const [ showVideoModal, setShowVideoModal ] = useState( false );
            const [ videoUrl, setVideoUrl ] = useState( '' );
            const [ videoCaption, setVideoCaption ] = useState( '' );
            const [ videoWidth, setVideoWidth ] = useState( '' );
            const textareaRef = useRef( null );
            const previewRef = useRef( null );
            const [ selectionStart, setSelectionStart ] = useState( 0 );
            const [ selectionEnd, setSelectionEnd ] = useState( 0 );

            const blockProps = useBlockProps( {
                className: 'wpdjot-block',
            } );

            // Track selection in textarea and check if in table
            function updateSelection() {
                if ( textareaRef.current ) {
                    const textarea = textareaRef.current.querySelector( 'textarea' );
                    if ( textarea ) {
                        setSelectionStart( textarea.selectionStart );
                        setSelectionEnd( textarea.selectionEnd );

                        // Check if cursor is in a table
                        var text = content || '';
                        var cursorPos = textarea.selectionStart;
                        var lineStart = cursorPos;
                        while ( lineStart > 0 && text[ lineStart - 1 ] !== '\n' ) {
                            lineStart--;
                        }
                        var lineEnd = cursorPos;
                        while ( lineEnd < text.length && text[ lineEnd ] !== '\n' ) {
                            lineEnd++;
                        }
                        var currentLine = text.substring( lineStart, lineEnd ).trim();
                        setCursorInTable( currentLine.startsWith( '|' ) && currentLine.endsWith( '|' ) );
                    }
                }
            }

            // Track previous content for undo/redo cursor positioning
            const previousContent = useRef( content || '' );
            const isInternalChange = useRef( false );

            // Find position where two strings first differ
            function findDiffPosition( oldStr, newStr ) {
                oldStr = oldStr || '';
                newStr = newStr || '';
                var minLen = Math.min( oldStr.length, newStr.length );
                for ( var i = 0; i < minLen; i++ ) {
                    if ( oldStr[ i ] !== newStr[ i ] ) {
                        return i;
                    }
                }
                // Strings are identical up to minLen, diff is at the end of shorter string
                return minLen;
            }

            // Handle external content changes (undo/redo) - position cursor at change location
            useEffect( function() {
                if ( isInternalChange.current ) {
                    isInternalChange.current = false;
                    previousContent.current = content || '';
                    return;
                }

                // External content change (undo/redo)
                if ( ! isPreviewMode ) {
                    var textarea = textareaRef.current ? textareaRef.current.querySelector( 'textarea' ) : null;
                    if ( textarea ) {
                        var oldContent = previousContent.current;
                        var newContent = content || '';
                        var diffPos = findDiffPosition( oldContent, newContent );

                        // Position cursor at the change location
                        requestAnimationFrame( function() {
                            textarea.focus( { preventScroll: true } );
                            textarea.setSelectionRange( diffPos, diffPos );
                            // Scroll textarea to make cursor visible
                            textarea.blur();
                            textarea.focus();
                        } );
                    }
                }

                previousContent.current = content || '';
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
            function insertMarkup( before, after ) {
                after = after || '';

                const textarea = textareaRef.current ? textareaRef.current.querySelector( 'textarea' ) : null;
                if ( ! textarea ) return;

                // Create undo boundary so this change is a separate undo step
                if ( markUndoBoundary ) {
                    markUndoBoundary();
                }

                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                const text = content || '';
                const selectedText = text.substring( start, end );

                let newText;
                let newCursorPos;

                if ( selectedText ) {
                    // Wrap selection, cursor at end
                    newText = text.substring( 0, start ) + before + selectedText + after + text.substring( end );
                    newCursorPos = start + before.length + selectedText.length + after.length;
                } else {
                    // Insert markers only, cursor between them
                    newText = text.substring( 0, start ) + before + after + text.substring( end );
                    newCursorPos = start + before.length;
                }

                setAttributes( { content: newText } );
                restoreFocus( textarea, newCursorPos );
            }

            // Insert block-level markup at cursor position
            function insertBlockMarkup( markup, placeholder ) {
                const textarea = textareaRef.current ? textareaRef.current.querySelector( 'textarea' ) : null;
                if ( ! textarea ) return;

                // Create undo boundary so this change is a separate undo step
                if ( markUndoBoundary ) {
                    markUndoBoundary();
                }

                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                const text = content || '';
                const selectedText = text.substring( start, end );

                // Use selected text or placeholder
                const blockText = selectedText || placeholder;

                // Check how many newlines needed before
                let prefix = '';
                if ( start === 0 ) {
                    // At beginning, no newlines needed
                } else if ( start >= 2 && text[ start - 1 ] === '\n' && text[ start - 2 ] === '\n' ) {
                    // Already have blank line, no extra needed
                } else if ( text[ start - 1 ] === '\n' ) {
                    // On a newline, need one more for blank line
                    prefix = '\n';
                } else {
                    // In middle of line, need two newlines for blank line
                    prefix = '\n\n';
                }

                // Always add two newlines after for blank line separation
                let suffix = '\n\n';
                if ( end < text.length - 1 && text[ end ] === '\n' && text[ end + 1 ] === '\n' ) {
                    // Already have blank line after
                    suffix = '';
                } else if ( end < text.length && text[ end ] === '\n' ) {
                    // Have one newline, add one more
                    suffix = '\n';
                }

                const newText = text.substring( 0, start ) + prefix + markup + blockText + suffix + text.substring( end );
                const newCursorPos = start + prefix.length + markup.length + blockText.length + suffix.length;

                setAttributes( { content: newText } );
                restoreFocus( textarea, newCursorPos );
            }

            // Insert multi-line block (like code blocks, divs)
            function insertMultiLineBlock( startTag, endTag, placeholder ) {
                const textarea = textareaRef.current ? textareaRef.current.querySelector( 'textarea' ) : null;
                if ( ! textarea ) return;

                // Create undo boundary so this change is a separate undo step
                if ( markUndoBoundary ) {
                    markUndoBoundary();
                }

                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                const text = content || '';
                const selectedText = text.substring( start, end );

                // Check newlines before cursor
                let prefixNewlines = '';
                if ( start === 0 ) {
                    // At beginning, no newlines needed
                } else if ( start >= 2 && text[ start - 1 ] === '\n' && text[ start - 2 ] === '\n' ) {
                    // Already have blank line, no extra needed
                } else if ( text[ start - 1 ] === '\n' ) {
                    // On a newline, need one more for blank line
                    prefixNewlines = '\n';
                } else {
                    // In middle of line, need two newlines
                    prefixNewlines = '\n\n';
                }

                // Check newlines after cursor - always want blank line after block
                let suffixNewlines = '\n\n';
                if ( end < text.length - 1 && text[ end ] === '\n' && text[ end + 1 ] === '\n' ) {
                    // Already have blank line after
                    suffixNewlines = '';
                } else if ( end < text.length && text[ end ] === '\n' ) {
                    // Have one newline, add one more
                    suffixNewlines = '\n';
                }

                const contentToWrap = selectedText || placeholder;
                let blockContent;
                let newCursorPos;

                if ( ! contentToWrap && ! endTag ) {
                    // Simple block like horizontal rule - cursor goes to blank line after
                    blockContent = prefixNewlines + startTag + suffixNewlines;
                    // Position cursor after first newline (on the blank line)
                    newCursorPos = start + prefixNewlines.length + startTag.length + 1;
                } else {
                    // Multi-line block with content
                    blockContent = prefixNewlines + startTag + '\n' + contentToWrap + '\n' + endTag + suffixNewlines;
                    newCursorPos = start + prefixNewlines.length + startTag.length + 1 + contentToWrap.length;
                }

                const newText = text.substring( 0, start ) + blockContent + text.substring( end );

                setAttributes( { content: newText } );
                restoreFocus( textarea, newCursorPos );
            }

            // Toolbar button handlers
            function onBold() { insertMarkup( '*', '*' ); }
            function onItalic() { insertMarkup( '_', '_' ); }
            function onCode() { insertMarkup( '`', '`' ); }
            function onSuperscript() { insertMarkup( '^', '^' ); }
            function onSubscript() { insertMarkup( '~', '~' ); }
            function onHighlight() { insertMarkup( '{=', '=}' ); }
            function onInsert() { insertMarkup( '{+', '+}' ); }
            function onDelete() { insertMarkup( '{-', '-}' ); }
            function onStrikethrough() { insertMarkup( '{~', '~}' ); }
            function onSpan() { insertMarkup( '[', ']{.class}' ); }

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
                const textarea = textareaRef.current ? textareaRef.current.querySelector( 'textarea' ) : null;
                if ( ! textarea ) return;

                // Create undo boundary so this change is a separate undo step
                if ( markUndoBoundary ) {
                    markUndoBoundary();
                }

                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                const text = content || '';

                let linkMarkup;
                if ( linkText.trim() ) {
                    linkMarkup = '[' + linkText + '](' + linkUrl + ')';
                } else {
                    // Use autolink syntax when no text provided
                    linkMarkup = '<' + linkUrl + '>';
                }

                // Replace selection (or insert at cursor) with the complete link
                const newText = text.substring( 0, start ) + linkMarkup + text.substring( end );
                const newCursorPos = start + linkMarkup.length;

                setAttributes( { content: newText } );
                restoreFocus( textarea, newCursorPos );

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
                const textarea = textareaRef.current ? textareaRef.current.querySelector( 'textarea' ) : null;
                if ( ! textarea ) return;

                // Create undo boundary so this change is a separate undo step
                if ( markUndoBoundary ) {
                    markUndoBoundary();
                }

                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                const text = content || '';

                const imageMarkup = '![' + imageAlt + '](' + imageUrl + ')';

                // Replace selection (or insert at cursor) with the complete image
                const newText = text.substring( 0, start ) + imageMarkup + text.substring( end );
                const newCursorPos = start + imageMarkup.length;

                setAttributes( { content: newText } );
                restoreFocus( textarea, newCursorPos );

                setShowImageModal( false );
                setImageUrl( '' );
                setImageAlt( '' );
            }

            function onHeading( level ) {
                const textarea = textareaRef.current ? textareaRef.current.querySelector( 'textarea' ) : null;
                if ( ! textarea ) return;

                // Create undo boundary so this change is a separate undo step
                if ( markUndoBoundary ) {
                    markUndoBoundary();
                }

                const text = content || '';
                const cursorPos = textarea.selectionStart;

                // Find the start of the current line
                let lineStart = cursorPos;
                while ( lineStart > 0 && text[ lineStart - 1 ] !== '\n' ) {
                    lineStart--;
                }

                // Find the end of the current line
                let lineEnd = cursorPos;
                while ( lineEnd < text.length && text[ lineEnd ] !== '\n' ) {
                    lineEnd++;
                }

                const currentLine = text.substring( lineStart, lineEnd );

                // Check if current line has heading markup
                const headingMatch = currentLine.match( /^(#{1,6})\s+(.*)$/ );

                if ( headingMatch ) {
                    // Replace existing heading with new level
                    const headingContent = headingMatch[ 2 ];
                    const newHashes = '#'.repeat( level ) + ' ';
                    const newLine = newHashes + headingContent;
                    const newText = text.substring( 0, lineStart ) + newLine + text.substring( lineEnd );
                    const newCursorPos = lineStart + newLine.length;

                    setAttributes( { content: newText } );
                    restoreFocus( textarea, newCursorPos );
                } else {
                    // No existing heading on line, insert heading prefix
                    const hashes = '#'.repeat( level ) + ' ';
                    insertBlockMarkup( hashes, '' );
                }
            }

            function onBlockquote() { insertBlockMarkup( '> ', '' ); }
            function onListUl() { insertBlockMarkup( '- ', '' ); }
            function onListOl() { insertBlockMarkup( '1. ', '' ); }
            function onHorizontalRule() { insertMultiLineBlock( '---', '', '' ); }
            function onCodeBlock() { insertMultiLineBlock( '```', '```', '' ); }
            function onDiv() { insertMultiLineBlock( '::: note', ':::', '' ); }
            function onFootnote() { insertMarkup( '[^', ']' ); }

            function onTable() {
                setTableCols( 3 );
                setTableRows( 2 );
                setShowTableModal( true );
            }

            function onInsertTable() {
                var cols = Math.max( 1, Math.min( 10, tableCols ) );
                var rows = Math.max( 1, Math.min( 20, tableRows ) );

                // Build header row
                var headerCells = [];
                var separatorCells = [];
                for ( var c = 1; c <= cols; c++ ) {
                    headerCells.push( ' Column ' );
                    separatorCells.push( '--------' );
                }
                var header = '|' + headerCells.join( '|' ) + '|';
                var separator = '|' + separatorCells.join( '|' ) + '|';

                // Build data rows
                var dataRows = [];
                for ( var r = 1; r <= rows; r++ ) {
                    var rowCells = [];
                    for ( var c = 1; c <= cols; c++ ) {
                        rowCells.push( '     ' );
                    }
                    dataRows.push( '|' + rowCells.join( '|' ) + '|' );
                }

                var tableTemplate = header + '\n' + separator + '\n' + dataRows.join( '\n' ) + '\n';
                insertMultiLineBlock( '', '', tableTemplate );
                setShowTableModal( false );
            }

            function onTaskList() {
                setTaskListItems( [ { text: '', checked: false } ] );
                setShowTaskListModal( true );
            }

            function onInsertTaskList() {
                var items = taskListItems.filter( function( item ) { return item.text.trim() !== ''; } );
                if ( items.length === 0 ) {
                    setShowTaskListModal( false );
                    return;
                }

                var taskListText = items.map( function( item ) {
                    return ( item.checked ? '- [x] ' : '- [ ] ' ) + item.text;
                } ).join( '\n' ) + '\n';

                insertMultiLineBlock( '', '', taskListText );
                setShowTaskListModal( false );
            }

            function updateTaskItem( index, field, value ) {
                var newItems = taskListItems.slice();
                newItems[ index ] = Object.assign( {}, newItems[ index ], ( function() { var o = {}; o[ field ] = value; return o; } )() );
                setTaskListItems( newItems );
            }

            function addTaskItem() {
                setTaskListItems( taskListItems.concat( [ { text: '', checked: false } ] ) );
            }

            function removeTaskItem( index ) {
                if ( taskListItems.length <= 1 ) return;
                var newItems = taskListItems.slice();
                newItems.splice( index, 1 );
                setTaskListItems( newItems );
            }

            function onDefList() {
                setDefListTerms( [ '' ] );
                setDefListDefinitions( [ '' ] );
                setShowDefListModal( true );
            }

            function onInsertDefList() {
                var terms = defListTerms.filter( function( t ) { return t.trim() !== ''; } );
                var definitions = defListDefinitions.filter( function( d ) { return d.trim() !== ''; } );
                if ( terms.length === 0 && definitions.length === 0 ) {
                    setShowDefListModal( false );
                    return;
                }

                // Djot spec syntax: `: term` lines, then blank line, then indented definition
                // Multiple dd elements use `: +` continuation marker
                var termsText = terms.map( function( t ) { return ': ' + t; } ).join( '\n' );

                var defsText = definitions.map( function( def, index ) {
                    var defLines = def.split( '\n' );
                    var indentedDef = defLines.map( function( line ) {
                        return '  ' + line;
                    } ).join( '\n' );

                    // First definition follows terms, subsequent ones use `: +` marker
                    if ( index === 0 ) {
                        return indentedDef;
                    }
                    return ': +\n\n' + indentedDef;
                } ).join( '\n\n' );

                var defListText = termsText + '\n\n' + defsText + '\n';

                insertMultiLineBlock( '', '', defListText );
                setShowDefListModal( false );
            }

            function updateDefTerm( index, value ) {
                var newTerms = defListTerms.slice();
                newTerms[ index ] = value;
                setDefListTerms( newTerms );
            }

            function addDefTerm() {
                setDefListTerms( defListTerms.concat( [ '' ] ) );
            }

            function removeDefTerm( index ) {
                if ( defListTerms.length <= 1 ) return;
                var newTerms = defListTerms.slice();
                newTerms.splice( index, 1 );
                setDefListTerms( newTerms );
            }

            function updateDefDefinition( index, value ) {
                var newDefs = defListDefinitions.slice();
                newDefs[ index ] = value;
                setDefListDefinitions( newDefs );
            }

            function addDefDefinition() {
                setDefListDefinitions( defListDefinitions.concat( [ '' ] ) );
            }

            function removeDefDefinition( index ) {
                if ( defListDefinitions.length <= 1 ) return;
                var newDefs = defListDefinitions.slice();
                newDefs.splice( index, 1 );
                setDefListDefinitions( newDefs );
            }

            function onVideo() {
                setVideoUrl( '' );
                setVideoCaption( '' );
                setVideoWidth( '' );
                setShowVideoModal( true );
            }

            function onInsertVideo() {
                if ( ! videoUrl.trim() ) {
                    setShowVideoModal( false );
                    return;
                }

                // Build the Djot syntax: ![caption](url){video width=X}
                var attrs = 'video';
                if ( videoWidth.trim() ) {
                    attrs += ' width=' + videoWidth.trim();
                }
                var videoText = '![' + videoCaption + '](' + videoUrl.trim() + '){' + attrs + '}\n';

                insertMultiLineBlock( '', '', videoText );
                setShowVideoModal( false );
            }

            // Format table at cursor position
            function onFormatTable() {
                var textarea = textareaRef.current ? textareaRef.current.querySelector( 'textarea' ) : null;
                if ( ! textarea ) return;

                // Create undo boundary so this change is a separate undo step
                if ( markUndoBoundary ) {
                    markUndoBoundary();
                }

                var text = content || '';
                var cursorPos = textarea.selectionStart;

                // Find table boundaries
                var lines = text.split( '\n' );
                var lineIndex = 0;
                var charCount = 0;
                for ( var i = 0; i < lines.length; i++ ) {
                    if ( charCount + lines[ i ].length >= cursorPos ) {
                        lineIndex = i;
                        break;
                    }
                    charCount += lines[ i ].length + 1; // +1 for newline
                }

                // Find table start (go up until non-table line)
                var tableStart = lineIndex;
                while ( tableStart > 0 && lines[ tableStart - 1 ].trim().startsWith( '|' ) ) {
                    tableStart--;
                }

                // Find table end (go down until non-table line)
                var tableEnd = lineIndex;
                while ( tableEnd < lines.length - 1 && lines[ tableEnd + 1 ].trim().startsWith( '|' ) ) {
                    tableEnd++;
                }

                // Extract table lines
                var tableLines = lines.slice( tableStart, tableEnd + 1 );
                if ( tableLines.length < 2 ) return; // Need at least header + separator

                // Parse cells
                var parsedRows = tableLines.map( function( line ) {
                    // Remove leading/trailing pipes and split
                    var trimmed = line.trim();
                    if ( trimmed.startsWith( '|' ) ) trimmed = trimmed.substring( 1 );
                    if ( trimmed.endsWith( '|' ) ) trimmed = trimmed.substring( 0, trimmed.length - 1 );
                    return trimmed.split( '|' ).map( function( cell ) {
                        return cell.trim();
                    } );
                } );

                // Find max width per column
                var colWidths = [];
                parsedRows.forEach( function( row, rowIdx ) {
                    row.forEach( function( cell, colIdx ) {
                        // Skip separator row for width calculation (use dashes count)
                        var width = cell.length;
                        if ( rowIdx === 1 && /^[-:]+$/.test( cell ) ) {
                            width = 3; // minimum for separator
                        }
                        if ( ! colWidths[ colIdx ] || width > colWidths[ colIdx ] ) {
                            colWidths[ colIdx ] = Math.max( width, 3 );
                        }
                    } );
                } );

                // Rebuild table with padding
                var formattedLines = parsedRows.map( function( row, rowIdx ) {
                    var cells = row.map( function( cell, colIdx ) {
                        var width = colWidths[ colIdx ] || 3;
                        if ( rowIdx === 1 && /^[-:]+$/.test( cell ) ) {
                            // Separator row - preserve alignment markers
                            var leftAlign = cell.startsWith( ':' );
                            var rightAlign = cell.endsWith( ':' );
                            var dashes = '-'.repeat( width );
                            if ( leftAlign && rightAlign ) {
                                return ':' + '-'.repeat( width - 2 ) + ':';
                            } else if ( leftAlign ) {
                                return ':' + '-'.repeat( width - 1 );
                            } else if ( rightAlign ) {
                                return '-'.repeat( width - 1 ) + ':';
                            }
                            return dashes;
                        }
                        // Pad cell with spaces
                        return cell + ' '.repeat( width - cell.length );
                    } );
                    return '| ' + cells.join( ' | ' ) + ' |';
                } );

                // Replace table in content
                var newLines = lines.slice( 0, tableStart ).concat( formattedLines ).concat( lines.slice( tableEnd + 1 ) );
                var newText = newLines.join( '\n' );

                // Calculate new cursor position (keep it roughly in same place)
                var newCursorPos = 0;
                for ( var i = 0; i < tableStart; i++ ) {
                    newCursorPos += newLines[ i ].length + 1;
                }
                newCursorPos += formattedLines[ 0 ].length; // Put cursor at end of first table line

                setAttributes( { content: newText } );
                restoreFocus( textarea, newCursorPos );
            }

            // Convert Markdown to Djot
            function convertMarkdownToDjot( md ) {
                var result = md;

                // Protect code blocks first (store and replace with placeholders)
                var codeBlocks = [];
                result = result.replace( /```[\s\S]*?```/g, function( match ) {
                    codeBlocks.push( match );
                    return '%%CODEBLOCK' + ( codeBlocks.length - 1 ) + '%%';
                } );

                // Protect inline code
                var inlineCode = [];
                result = result.replace( /`[^`]+`/g, function( match ) {
                    inlineCode.push( match );
                    return '%%INLINECODE' + ( inlineCode.length - 1 ) + '%%';
                } );

                // Convert indented code blocks to fenced (4 spaces or 1 tab)
                result = result.replace( /^((?:(?:    |\t).+\n?)+)/gm, function( match ) {
                    var code = match.replace( /^(    |\t)/gm, '' ).trimEnd();
                    return '```\n' + code + '\n```\n';
                } );

                // Bold: **text** or __text__ → *text*
                // Use placeholder to prevent italic conversion from affecting these
                result = result.replace( /\*\*([^*]+)\*\*/g, '%%DJOTBOLD%%$1%%DJOTBOLDEND%%' );
                result = result.replace( /__([^_]+)__/g, '%%DJOTBOLD%%$1%%DJOTBOLDEND%%' );

                // Italic: *text* or _text_ → _text_ (but not inside words)
                // Only convert *text* that's not already bold
                result = result.replace( /(?<!\*)\*([^*]+)\*(?!\*)/g, '_$1_' );

                // Restore bold markers
                result = result.replace( /%%DJOTBOLD%%/g, '*' );
                result = result.replace( /%%DJOTBOLDEND%%/g, '*' );

                // Strikethrough: ~~text~~ → {~text~}
                result = result.replace( /~~([^~]+)~~/g, '{~$1~}' );

                // Highlight: ==text== → {=text=}
                result = result.replace( /==([^=]+)==/g, '{=$1=}' );

                // Headers: ensure space after # (Djot requires it)
                result = result.replace( /^(#{1,6})([^ #\n])/gm, '$1 $2' );

                // Remove trailing # from headers (Djot treats them as content)
                result = result.replace( /^(#{1,6} .+?)\s*#+\s*$/gm, '$1' );

                // Setext-style headers: convert to ATX style
                // H1: text followed by line of ===
                result = result.replace( /^(.+)\n=+$/gm, '# $1' );
                // H2: text followed by line of ---
                result = result.replace( /^(.+)\n-+$/gm, '## $1' );

                // Ensure blank line after headers (Djot requires it)
                result = result.replace( /^(#{1,6} .+)$(\n?)(?!\n)/gm, '$1\n\n' );

                // Link titles: [text](url "title") → [text](url){title="title"}
                result = result.replace( /\[([^\]]+)\]\(([^)"]+)\s+"([^"]+)"\)/g, '[$1]($2){title="$3"}' );
                result = result.replace( /\[([^\]]+)\]\(([^)']+)\s+'([^']+)'\)/g, "[$1]($2){title='$3'}" );

                // Hard line breaks: trailing two spaces → backslash
                result = result.replace( /  $/gm, '\\' );

                // Blockquotes: ensure space after > (unless followed by newline)
                result = result.replace( /^>([^ \n>])/gm, '> $1' );

                // Ensure blank line before blockquotes
                result = result.replace( /([^\n])\n(>)/gm, '$1\n\n$2' );

                // Ensure blank line before lists
                result = result.replace( /([^\n])\n([-*+] |\d+\. )/gm, '$1\n\n$2' );

                // Raw HTML: wrap in djot raw syntax
                result = result.replace( /<([a-z][a-z0-9]*)([ >])/gi, function( match, tag, after ) {
                    // Skip common safe tags that might be intentional
                    var safeTags = [ 'http', 'https' ];
                    if ( safeTags.indexOf( tag.toLowerCase() ) >= 0 ) {
                        return match;
                    }
                    return '`<' + tag + after.trimEnd() + '`{=html}';
                } );

                // Restore inline code
                inlineCode.forEach( function( code, idx ) {
                    result = result.replace( '%%INLINECODE' + idx + '%%', code );
                } );

                // Restore code blocks
                codeBlocks.forEach( function( block, idx ) {
                    result = result.replace( '%%CODEBLOCK' + idx + '%%', block );
                } );

                // Clean up excessive blank lines (more than 2 consecutive)
                result = result.replace( /\n{3,}/g, '\n\n' );

                return result;
            }

            // Open import modal
            function onImport( type ) {
                setImportType( type );
                setImportInput( '' );
                setDjotPreview( '' );
                setShowImportModal( true );
            }

            // Update preview when import input changes (using server-side converter)
            const debouncedConvert = useCallback(
                debounce( function( input, type ) {
                    if ( ! input.trim() ) {
                        setDjotPreview( '' );
                        setIsConverting( false );
                        return;
                    }

                    var endpoint = type === 'html' ? '/wpdjot/v1/convert-html' : '/wpdjot/v1/convert-markdown';

                    apiFetch( {
                        path: endpoint,
                        method: 'POST',
                        data: { content: input },
                    } )
                        .then( function( response ) {
                            setDjotPreview( response.djot || '' );
                            setIsConverting( false );
                        } )
                        .catch( function() {
                            // Fall back to client-side conversion on error (markdown only)
                            if ( type === 'markdown' ) {
                                setDjotPreview( convertMarkdownToDjot( input ) );
                            }
                            setIsConverting( false );
                        } );
                }, 300 ),
                []
            );

            function onImportInputChange( value ) {
                setImportInput( value );
                setIsConverting( true );
                debouncedConvert( value, importType );
            }

            // Insert converted djot at cursor position
            function onInsertImported() {
                if ( ! djotPreview.trim() ) {
                    setShowImportModal( false );
                    return;
                }

                // Create undo boundary so this change is a separate undo step
                if ( markUndoBoundary ) {
                    markUndoBoundary();
                }

                var textarea = textareaRef.current ? textareaRef.current.querySelector( 'textarea' ) : null;
                var text = content || '';
                var start = textarea ? textarea.selectionStart : text.length;

                // Add newlines if needed
                var prefix = '';
                if ( start > 0 && text[ start - 1 ] !== '\n' ) {
                    prefix = '\n\n';
                } else if ( start > 1 && text[ start - 2 ] !== '\n' ) {
                    prefix = '\n';
                }

                var suffix = '\n\n';
                if ( start < text.length - 1 && text[ start ] === '\n' && text[ start + 1 ] === '\n' ) {
                    suffix = '';
                } else if ( start < text.length && text[ start ] === '\n' ) {
                    suffix = '\n';
                }

                var newText = text.substring( 0, start ) + prefix + djotPreview + suffix + text.substring( start );
                var newCursorPos = start + prefix.length + djotPreview.length;

                setAttributes( { content: newText } );
                setShowImportModal( false );
                setMarkdownInput( '' );
                setDjotPreview( '' );

                if ( textarea ) {
                    restoreFocus( textarea, newCursorPos );
                }
            }

            // Indent/outdent handlers
            function indentLines() {
                var textarea = textareaRef.current ? textareaRef.current.querySelector( 'textarea' ) : null;
                if ( ! textarea ) return;

                // Create undo boundary so this change is a separate undo step
                if ( markUndoBoundary ) {
                    markUndoBoundary();
                }

                var text = content || '';
                var start = textarea.selectionStart;
                var end = textarea.selectionEnd;

                // Find line boundaries
                var lineStart = start;
                while ( lineStart > 0 && text[ lineStart - 1 ] !== '\n' ) {
                    lineStart--;
                }
                var lineEnd = end;
                while ( lineEnd < text.length && text[ lineEnd ] !== '\n' ) {
                    lineEnd++;
                }

                // Get selected lines and indent each
                var selectedText = text.substring( lineStart, lineEnd );
                var indentedText = selectedText.split( '\n' ).map( function( line ) {
                    return '    ' + line;
                } ).join( '\n' );

                var newText = text.substring( 0, lineStart ) + indentedText + text.substring( lineEnd );
                var lineCount = selectedText.split( '\n' ).length;
                var newStart = start + 4;
                var newEnd = end + ( lineCount * 4 );

                setAttributes( { content: newText } );
                requestAnimationFrame( function() {
                    textarea.focus( { preventScroll: true } );
                    textarea.setSelectionRange( newStart, newEnd );
                } );
            }

            function outdentLines() {
                var textarea = textareaRef.current ? textareaRef.current.querySelector( 'textarea' ) : null;
                if ( ! textarea ) return;

                // Create undo boundary so this change is a separate undo step
                if ( markUndoBoundary ) {
                    markUndoBoundary();
                }

                var text = content || '';
                var start = textarea.selectionStart;
                var end = textarea.selectionEnd;

                // Find line boundaries
                var lineStart = start;
                while ( lineStart > 0 && text[ lineStart - 1 ] !== '\n' ) {
                    lineStart--;
                }
                var lineEnd = end;
                while ( lineEnd < text.length && text[ lineEnd ] !== '\n' ) {
                    lineEnd++;
                }

                // Get selected lines and outdent each
                var selectedText = text.substring( lineStart, lineEnd );
                var removedTotal = 0;
                var removedFirst = 0;
                var isFirst = true;
                var outdentedText = selectedText.split( '\n' ).map( function( line ) {
                    var removed = 0;
                    // Remove up to 4 spaces or 1 tab
                    if ( line.startsWith( '    ' ) ) {
                        line = line.substring( 4 );
                        removed = 4;
                    } else if ( line.startsWith( '\t' ) ) {
                        line = line.substring( 1 );
                        removed = 1;
                    } else if ( line.startsWith( '   ' ) ) {
                        line = line.substring( 3 );
                        removed = 3;
                    } else if ( line.startsWith( '  ' ) ) {
                        line = line.substring( 2 );
                        removed = 2;
                    } else if ( line.startsWith( ' ' ) ) {
                        line = line.substring( 1 );
                        removed = 1;
                    }
                    if ( isFirst ) {
                        removedFirst = removed;
                        isFirst = false;
                    }
                    removedTotal += removed;
                    return line;
                } ).join( '\n' );

                var newText = text.substring( 0, lineStart ) + outdentedText + text.substring( lineEnd );
                var newStart = Math.max( lineStart, start - removedFirst );
                var newEnd = Math.max( newStart, end - removedTotal );

                setAttributes( { content: newText } );
                requestAnimationFrame( function() {
                    textarea.focus( { preventScroll: true } );
                    textarea.setSelectionRange( newStart, newEnd );
                } );
            }

            // Keyboard shortcut handler for textarea
            function handleTextareaKeyDown( e ) {
                // Handle Tab/Shift+Tab for indent/outdent
                if ( e.key === 'Tab' ) {
                    e.preventDefault();
                    if ( e.shiftKey ) {
                        outdentLines();
                    } else {
                        indentLines();
                    }
                    return;
                }

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
                        case '4': onHeading( 4 ); handled = true; break;
                        case '5': onHeading( 5 ); handled = true; break;
                        case '6': onHeading( 6 ); handled = true; break;
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
                        path: '/wpdjot/v1/render',
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
                if ( ! isPreviewMode || ! preview || isLoading ) {
                    return;
                }

                function applyHighlighting() {
                    // Use querySelector as fallback since ref may not be ready
                    var previewEl = previewRef.current || document.querySelector( '.wpdjot-preview.djot-content' );
                    if ( previewEl && window.hljs ) {
                        var codeBlocks = previewEl.querySelectorAll( 'pre code' );
                        codeBlocks.forEach( function( block ) {
                            if ( ! block.classList.contains( 'hljs' ) ) {
                                window.hljs.highlightElement( block );
                            }
                        } );
                    }
                }

                // Poll for both hljs and DOM element availability
                var attempts = 0;
                var maxAttempts = 50; // 5 seconds max
                var pollInterval = setInterval( function() {
                    attempts++;
                    var hasHljs = typeof window.hljs !== 'undefined';
                    var hasPreview = previewRef.current || document.querySelector( '.wpdjot-preview.djot-content' );

                    if ( hasHljs && hasPreview ) {
                        clearInterval( pollInterval );
                        setTimeout( applyHighlighting, 10 );
                    } else if ( attempts >= maxAttempts ) {
                        clearInterval( pollInterval );
                    }
                }, 100 );

                return function() { clearInterval( pollInterval ); };
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
                isInternalChange.current = true;
                setAttributes( { content: newContent } );
            }

            // Scroll preview to match cursor position in editor
            function syncPreviewScroll() {
                var textarea = textareaRef.current ? textareaRef.current.querySelector( 'textarea' ) : null;
                var previewEl = previewRef.current;
                if ( ! textarea || ! previewEl ) return;

                var text = content || '';
                var cursorPos = textarea.selectionStart;

                // Calculate percentage through the document based on cursor position
                var percentage = text.length > 0 ? cursorPos / text.length : 0;

                // Apply to preview scroll with a small delay to ensure preview is rendered
                setTimeout( function() {
                    var scrollHeight = previewEl.scrollHeight - previewEl.clientHeight;
                    if ( scrollHeight > 0 ) {
                        previewEl.scrollTop = scrollHeight * percentage;
                    }
                }, 100 );
            }

            // Sync scroll when entering preview mode
            useEffect( function() {
                if ( isPreviewMode && preview && ! isLoading ) {
                    syncPreviewScroll();
                }
            }, [ isPreviewMode, preview, isLoading ] );

            // Heading dropdown controls
            const headingControls = [
                { title: __( 'Heading 1', 'djot-markup-for-wp' ), onClick: function() { onHeading( 1 ); } },
                { title: __( 'Heading 2', 'djot-markup-for-wp' ), onClick: function() { onHeading( 2 ); } },
                { title: __( 'Heading 3', 'djot-markup-for-wp' ), onClick: function() { onHeading( 3 ); } },
                { title: __( 'Heading 4', 'djot-markup-for-wp' ), onClick: function() { onHeading( 4 ); } },
                { title: __( 'Heading 5', 'djot-markup-for-wp' ), onClick: function() { onHeading( 5 ); } },
                { title: __( 'Heading 6', 'djot-markup-for-wp' ), onClick: function() { onHeading( 6 ); } },
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
                            label: __( 'Bold (*text*)', 'djot-markup-for-wp' ),
                            onClick: onBold,
                        } ),
                        wp.element.createElement( ToolbarButton, {
                            icon: icons.italic,
                            label: __( 'Italic (_text_)', 'djot-markup-for-wp' ),
                            onClick: onItalic,
                        } ),
                        wp.element.createElement( ToolbarButton, {
                            icon: icons.code,
                            label: __( 'Inline Code (`code`)', 'djot-markup-for-wp' ),
                            onClick: onCode,
                        } ),
                        wp.element.createElement( ToolbarButton, {
                            icon: icons.strikethrough,
                            label: __( 'Strikethrough ({~text~})', 'djot-markup-for-wp' ),
                            onClick: onStrikethrough,
                        } )
                    ),
                    // Links and media group
                    wp.element.createElement(
                        ToolbarGroup,
                        null,
                        wp.element.createElement( ToolbarButton, {
                            icon: icons.link,
                            label: __( 'Link ([text](url))', 'djot-markup-for-wp' ),
                            onClick: onLink,
                        } ),
                        wp.element.createElement( ToolbarButton, {
                            icon: icons.image,
                            label: __( 'Image (![alt](url))', 'djot-markup-for-wp' ),
                            onClick: onImage,
                        } )
                    ),
                    // Headings dropdown
                    wp.element.createElement(
                        ToolbarGroup,
                        null,
                        wp.element.createElement( ToolbarDropdownMenu, {
                            icon: icons.heading,
                            label: __( 'Headings', 'djot-markup-for-wp' ),
                            controls: headingControls,
                        } )
                    ),
                    // Block elements group
                    wp.element.createElement(
                        ToolbarGroup,
                        null,
                        wp.element.createElement( ToolbarButton, {
                            icon: icons.quote,
                            label: __( 'Blockquote (> quote)', 'djot-markup-for-wp' ),
                            onClick: onBlockquote,
                        } ),
                        wp.element.createElement( ToolbarButton, {
                            icon: icons.listUl,
                            label: __( 'Unordered List (- item)', 'djot-markup-for-wp' ),
                            onClick: onListUl,
                        } ),
                        wp.element.createElement( ToolbarButton, {
                            icon: icons.listOl,
                            label: __( 'Ordered List (1. item)', 'djot-markup-for-wp' ),
                            onClick: onListOl,
                        } ),
                        wp.element.createElement( ToolbarButton, {
                            icon: icons.codeBlock,
                            label: __( 'Code Block (```)', 'djot-markup-for-wp' ),
                            onClick: onCodeBlock,
                        } )
                    ),
                    // Djot-specific formatting
                    wp.element.createElement(
                        ToolbarGroup,
                        null,
                        wp.element.createElement( ToolbarButton, {
                            icon: icons.superscript,
                            label: __( 'Superscript (^text^)', 'djot-markup-for-wp' ),
                            onClick: onSuperscript,
                        } ),
                        wp.element.createElement( ToolbarButton, {
                            icon: icons.subscript,
                            label: __( 'Subscript (~text~)', 'djot-markup-for-wp' ),
                            onClick: onSubscript,
                        } ),
                        wp.element.createElement( ToolbarButton, {
                            icon: icons.highlight,
                            label: __( 'Highlight ({=text=})', 'djot-markup-for-wp' ),
                            onClick: onHighlight,
                        } )
                    ),
                    // Insert/Delete/More
                    wp.element.createElement(
                        ToolbarGroup,
                        null,
                        wp.element.createElement( ToolbarButton, {
                            icon: icons.insert,
                            label: __( 'Insert ({+text+})', 'djot-markup-for-wp' ),
                            onClick: onInsert,
                        } ),
                        wp.element.createElement( ToolbarButton, {
                            icon: icons.delete,
                            label: __( 'Delete ({-text-})', 'djot-markup-for-wp' ),
                            onClick: onDelete,
                        } ),
                        wp.element.createElement( ToolbarButton, {
                            icon: icons.horizontalRule,
                            label: __( 'Horizontal Rule (---)', 'djot-markup-for-wp' ),
                            onClick: onHorizontalRule,
                        } )
                    ),
                    // Advanced group
                    wp.element.createElement(
                        ToolbarGroup,
                        null,
                        wp.element.createElement( ToolbarButton, {
                            icon: icons.table,
                            label: __( 'Table', 'djot-markup-for-wp' ),
                            onClick: onTable,
                        } ),
                        cursorInTable && wp.element.createElement( ToolbarButton, {
                            icon: icons.formatTable,
                            label: __( 'Format Table', 'djot-markup-for-wp' ),
                            onClick: onFormatTable,
                        } ),
                        wp.element.createElement( ToolbarButton, {
                            icon: icons.div,
                            label: __( 'Div Block (::: class)', 'djot-markup-for-wp' ),
                            onClick: onDiv,
                        } ),
                        wp.element.createElement( ToolbarButton, {
                            icon: icons.span,
                            label: __( 'Span with Class ([text]{.class})', 'djot-markup-for-wp' ),
                            onClick: onSpan,
                        } ),
                        wp.element.createElement( ToolbarButton, {
                            icon: icons.footnote,
                            label: __( 'Footnote ([^note])', 'djot-markup-for-wp' ),
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
                        { title: __( 'Djot Settings', 'djot-markup-for-wp' ) },
                        wp.element.createElement( ToggleControl, {
                            label: __( 'Show Preview', 'djot-markup-for-wp' ),
                            checked: isPreviewMode,
                            onChange: setIsPreviewMode,
                            __nextHasNoMarginBottom: true,
                        } )
                    ),
                    wp.element.createElement(
                        PanelBody,
                        { title: __( 'Syntax Help', 'djot-markup-for-wp' ), initialOpen: false },
                        wp.element.createElement( 'div', { className: 'wpdjot-syntax-help' },
                            wp.element.createElement( 'p', { style: { marginTop: 0, marginBottom: '2px' } }, wp.element.createElement( 'strong', null, 'Inline:' ) ),
                            wp.element.createElement( 'div', { style: { marginBottom: '12px' } },
                                wp.element.createElement( 'code', null, '*bold*' ), ' ',
                                wp.element.createElement( 'code', null, '_italic_' ), ' ',
                                wp.element.createElement( 'code', null, '`code`' )
                            ),
                            wp.element.createElement( 'p', { style: { marginTop: 0, marginBottom: '2px' } }, wp.element.createElement( 'strong', null, 'Links/Images:' ) ),
                            wp.element.createElement( 'div', { style: { marginBottom: '12px' } },
                                wp.element.createElement( 'code', null, '[text](url)' ), wp.element.createElement( 'br' ),
                                wp.element.createElement( 'code', null, '![alt](src)' )
                            ),
                            wp.element.createElement( 'p', { style: { marginTop: 0, marginBottom: '2px' } }, wp.element.createElement( 'strong', null, 'Djot-specific:' ) ),
                            wp.element.createElement( 'div', { style: { marginBottom: '12px' } },
                                wp.element.createElement( 'code', null, '^super^' ), ' ',
                                wp.element.createElement( 'code', null, '~sub~' ), ' ',
                                wp.element.createElement( 'code', null, '{=highlight=}' )
                            ),
                            wp.element.createElement( 'p', { style: { marginTop: 0, marginBottom: '2px' } }, wp.element.createElement( 'strong', null, 'Semantic:' ) ),
                            wp.element.createElement( 'div', { style: { marginBottom: '12px' } },
                                wp.element.createElement( 'code', null, '[CSS]{abbr="title"}' ), wp.element.createElement( 'br' ),
                                wp.element.createElement( 'code', null, '[Ctrl+C]{kbd}' ), wp.element.createElement( 'br' ),
                                wp.element.createElement( 'code', null, '[term]{dfn}' )
                            ),
                            wp.element.createElement( 'p', { style: { marginTop: 0, marginBottom: 0 } },
                                wp.element.createElement( 'a', { href: 'https://djot.net/', target: '_blank' }, __( 'Full Djot Documentation →', 'djot-markup-for-wp' ) )
                            )
                        )
                    ),
                    wp.element.createElement(
                        PanelBody,
                        { title: __( 'Keyboard Shortcuts', 'djot-markup-for-wp' ), initialOpen: false },
                        wp.element.createElement( 'div', { className: 'wpdjot-shortcuts-help', style: { fontSize: '12px' } },
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
                            wp.element.createElement( 'div', null, wp.element.createElement( 'kbd', null, 'Ctrl+[1-6]' ), ' H1-H6' ),
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
                            wp.element.createElement( 'div', null, wp.element.createElement( 'kbd', null, 'Tab' ), ' Indent' ),
                            wp.element.createElement( 'div', null, wp.element.createElement( 'kbd', null, 'Shift+Tab' ), ' Outdent' ),
                            wp.element.createElement( 'div', null, wp.element.createElement( 'kbd', null, 'ESC' ), ' Exit preview' )
                        )
                    ),
                    wp.element.createElement(
                        PanelBody,
                        { title: __( 'Tools', 'djot-markup-for-wp' ), initialOpen: false },
                        wp.element.createElement( 'div', { style: { display: 'flex', flexDirection: 'column', gap: '8px' } },
                            wp.element.createElement( Button, {
                                variant: 'secondary',
                                icon: icons.taskList,
                                onClick: onTaskList,
                                style: { width: '100%', justifyContent: 'center' },
                            }, __( 'Insert Task List', 'djot-markup-for-wp' ) ),
                            wp.element.createElement( Button, {
                                variant: 'secondary',
                                onClick: onDefList,
                                style: { width: '100%', justifyContent: 'center' },
                            }, __( 'Insert Definition List', 'djot-markup-for-wp' ) ),
                            wp.element.createElement( Button, {
                                variant: 'secondary',
                                onClick: function() { onImport( 'markdown' ); },
                                style: { width: '100%', justifyContent: 'center' },
                            }, __( 'Import Markdown', 'djot-markup-for-wp' ) ),
                            wp.element.createElement( Button, {
                                variant: 'secondary',
                                onClick: function() { onImport( 'html' ); },
                                style: { width: '100%', justifyContent: 'center' },
                            }, __( 'Import HTML', 'djot-markup-for-wp' ) ),
                            wp.element.createElement( Button, {
                                variant: 'secondary',
                                icon: icons.video,
                                onClick: onVideo,
                                style: { width: '100%', justifyContent: 'center' },
                            }, __( 'Insert Video', 'djot-markup-for-wp' ) )
                        )
                    )
                ),
                // Link Modal
                showLinkModal && wp.element.createElement(
                    Modal,
                    {
                        title: __( 'Insert Link', 'djot-markup-for-wp' ),
                        onRequestClose: function() { setShowLinkModal( false ); },
                    },
                    wp.element.createElement( TextControl, {
                        label: __( 'URL', 'djot-markup-for-wp' ),
                        value: linkUrl,
                        onChange: setLinkUrl,
                        type: 'url',
                    } ),
                    wp.element.createElement( TextControl, {
                        label: __( 'Link Text (optional, leave empty for autolink)', 'djot-markup-for-wp' ),
                        value: linkText,
                        onChange: setLinkText,
                    } ),
                    wp.element.createElement(
                        'div',
                        { style: { marginTop: '16px' } },
                        wp.element.createElement( Button, {
                            variant: 'primary',
                            onClick: onInsertLink,
                        }, __( 'Insert Link', 'djot-markup-for-wp' ) ),
                        wp.element.createElement( Button, {
                            variant: 'secondary',
                            onClick: function() { setShowLinkModal( false ); },
                            style: { marginLeft: '8px' },
                        }, __( 'Cancel', 'djot-markup-for-wp' ) )
                    )
                ),
                // Image Modal
                showImageModal && wp.element.createElement(
                    Modal,
                    {
                        title: __( 'Insert Image', 'djot-markup-for-wp' ),
                        onRequestClose: function() { setShowImageModal( false ); },
                    },
                    wp.element.createElement( TextControl, {
                        label: __( 'Image URL', 'djot-markup-for-wp' ),
                        value: imageUrl,
                        onChange: setImageUrl,
                        type: 'url',
                    } ),
                    wp.element.createElement( TextControl, {
                        label: __( 'Alt Text (optional)', 'djot-markup-for-wp' ),
                        value: imageAlt,
                        onChange: setImageAlt,
                    } ),
                    wp.element.createElement(
                        'div',
                        { style: { marginTop: '16px' } },
                        wp.element.createElement( Button, {
                            variant: 'primary',
                            onClick: onInsertImage,
                        }, __( 'Insert Image', 'djot-markup-for-wp' ) ),
                        wp.element.createElement( Button, {
                            variant: 'secondary',
                            onClick: function() { setShowImageModal( false ); },
                            style: { marginLeft: '8px' },
                        }, __( 'Cancel', 'djot-markup-for-wp' ) )
                    )
                ),
                // Table Modal
                showTableModal && wp.element.createElement(
                    Modal,
                    {
                        title: __( 'Insert Table', 'djot-markup-for-wp' ),
                        onRequestClose: function() { setShowTableModal( false ); },
                    },
                    wp.element.createElement( RangeControl, {
                        label: __( 'Columns', 'djot-markup-for-wp' ),
                        value: tableCols,
                        onChange: setTableCols,
                        min: 1,
                        max: 10,
                    } ),
                    wp.element.createElement( RangeControl, {
                        label: __( 'Rows (excluding header)', 'djot-markup-for-wp' ),
                        value: tableRows,
                        onChange: setTableRows,
                        min: 1,
                        max: 20,
                    } ),
                    wp.element.createElement(
                        'div',
                        { style: { marginTop: '16px' } },
                        wp.element.createElement( Button, {
                            variant: 'primary',
                            onClick: onInsertTable,
                        }, __( 'Insert Table', 'djot-markup-for-wp' ) ),
                        wp.element.createElement( Button, {
                            variant: 'secondary',
                            onClick: function() { setShowTableModal( false ); },
                            style: { marginLeft: '8px' },
                        }, __( 'Cancel', 'djot-markup-for-wp' ) )
                    )
                ),
                // Import Modal (Markdown/HTML)
                showImportModal && wp.element.createElement(
                    Modal,
                    {
                        title: importType === 'html' ? __( 'Import HTML', 'djot-markup-for-wp' ) : __( 'Import Markdown', 'djot-markup-for-wp' ),
                        onRequestClose: function() { setShowImportModal( false ); },
                        style: { width: '600px', maxWidth: '90vw' },
                    },
                    wp.element.createElement( 'div', { style: { display: 'flex', gap: '16px' } },
                        wp.element.createElement( 'div', { style: { flex: 1 } },
                            wp.element.createElement( 'label', { style: { display: 'block', marginBottom: '8px', fontWeight: 600 } },
                                importType === 'html' ? __( 'HTML Input', 'djot-markup-for-wp' ) : __( 'Markdown Input', 'djot-markup-for-wp' )
                            ),
                            wp.element.createElement( 'textarea', {
                                value: importInput,
                                onChange: function( e ) { onImportInputChange( e.target.value ); },
                                style: { width: '100%', height: '200px', fontFamily: 'monospace', fontSize: '13px', padding: '8px' },
                                placeholder: importType === 'html' ? __( 'Paste your HTML here...', 'djot-markup-for-wp' ) : __( 'Paste your Markdown here...', 'djot-markup-for-wp' ),
                            } )
                        ),
                        wp.element.createElement( 'div', { style: { flex: 1, position: 'relative' } },
                            wp.element.createElement( 'label', { style: { display: 'block', marginBottom: '8px', fontWeight: 600 } },
                                __( 'Djot Preview', 'djot-markup-for-wp' ),
                                isConverting && wp.element.createElement( 'span', { style: { marginLeft: '8px', fontSize: '11px', color: '#999' } }, __( 'Converting...', 'djot-markup-for-wp' ) )
                            ),
                            wp.element.createElement( 'textarea', {
                                value: djotPreview,
                                readOnly: true,
                                style: { width: '100%', height: '200px', fontFamily: 'monospace', fontSize: '13px', padding: '8px', background: '#f9f9f9' },
                                placeholder: __( 'Converted Djot will appear here...', 'djot-markup-for-wp' ),
                            } )
                        )
                    ),
                    importType === 'markdown' && wp.element.createElement( 'p', { style: { marginTop: '12px', fontSize: '12px', color: '#666' } },
                        __( 'Converts: **bold** → *bold*, *italic* → _italic_, ~~strike~~ → {~strike~}, ==highlight== → {=highlight=}', 'djot-markup-for-wp' )
                    ),
                    wp.element.createElement(
                        'div',
                        { style: { marginTop: '16px' } },
                        wp.element.createElement( Button, {
                            variant: 'primary',
                            onClick: onInsertImported,
                            disabled: isConverting || ! djotPreview.trim(),
                        }, __( 'Insert', 'djot-markup-for-wp' ) ),
                        wp.element.createElement( Button, {
                            variant: 'secondary',
                            onClick: function() { setShowImportModal( false ); },
                            style: { marginLeft: '8px' },
                        }, __( 'Cancel', 'djot-markup-for-wp' ) )
                    )
                ),
                // Task List Modal
                showTaskListModal && wp.element.createElement(
                    Modal,
                    {
                        title: __( 'Insert Task List', 'djot-markup-for-wp' ),
                        onRequestClose: function() { setShowTaskListModal( false ); },
                        style: { width: '400px', maxWidth: '90vw' },
                    },
                    wp.element.createElement( 'div', { style: { marginBottom: '16px' } },
                        taskListItems.map( function( item, index ) {
                            return wp.element.createElement( 'div', {
                                key: index,
                                style: { display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '8px' },
                            },
                                wp.element.createElement( 'input', {
                                    type: 'checkbox',
                                    checked: item.checked,
                                    onChange: function( e ) { updateTaskItem( index, 'checked', e.target.checked ); },
                                    style: { width: '18px', height: '18px', cursor: 'pointer' },
                                } ),
                                wp.element.createElement( 'input', {
                                    type: 'text',
                                    value: item.text,
                                    onChange: function( e ) { updateTaskItem( index, 'text', e.target.value ); },
                                    placeholder: __( 'Task item...', 'djot-markup-for-wp' ),
                                    style: { flex: 1, padding: '6px 8px', border: '1px solid #ccc', borderRadius: '4px' },
                                    onKeyDown: function( e ) {
                                        if ( e.key === 'Enter' ) {
                                            e.preventDefault();
                                            addTaskItem();
                                        }
                                    },
                                } ),
                                taskListItems.length > 1 && wp.element.createElement( Button, {
                                    variant: 'tertiary',
                                    isDestructive: true,
                                    onClick: function() { removeTaskItem( index ); },
                                    style: { padding: '4px' },
                                }, '✕' )
                            );
                        } )
                    ),
                    wp.element.createElement( Button, {
                        variant: 'secondary',
                        onClick: addTaskItem,
                        style: { marginBottom: '16px' },
                    }, __( '+ Add Item', 'djot-markup-for-wp' ) ),
                    wp.element.createElement(
                        'div',
                        { style: { marginTop: '16px', borderTop: '1px solid #ddd', paddingTop: '16px' } },
                        wp.element.createElement( Button, {
                            variant: 'primary',
                            onClick: onInsertTaskList,
                        }, __( 'Insert Task List', 'djot-markup-for-wp' ) ),
                        wp.element.createElement( Button, {
                            variant: 'secondary',
                            onClick: function() { setShowTaskListModal( false ); },
                            style: { marginLeft: '8px' },
                        }, __( 'Cancel', 'djot-markup-for-wp' ) )
                    )
                ),
                // Definition List Modal
                showDefListModal && wp.element.createElement(
                    Modal,
                    {
                        title: __( 'Insert Definition List', 'djot-markup-for-wp' ),
                        onRequestClose: function() { setShowDefListModal( false ); },
                        style: { width: '500px', maxWidth: '90vw' },
                    },
                    // Terms section
                    wp.element.createElement( 'div', { style: { marginBottom: '16px' } },
                        wp.element.createElement( 'label', { style: { display: 'block', fontWeight: 600, marginBottom: '8px' } },
                            __( 'Terms (dt):', 'djot-markup-for-wp' )
                        ),
                        defListTerms.map( function( term, index ) {
                            return wp.element.createElement( 'div', {
                                key: index,
                                style: { display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '8px' },
                            },
                                wp.element.createElement( 'input', {
                                    type: 'text',
                                    value: term,
                                    onChange: function( e ) { updateDefTerm( index, e.target.value ); },
                                    placeholder: __( 'Term...', 'djot-markup-for-wp' ),
                                    style: { flex: 1, padding: '6px 8px', border: '1px solid #ccc', borderRadius: '4px' },
                                    onKeyDown: function( e ) {
                                        if ( e.key === 'Enter' ) {
                                            e.preventDefault();
                                            addDefTerm();
                                        }
                                    },
                                } ),
                                defListTerms.length > 1 && wp.element.createElement( Button, {
                                    variant: 'tertiary',
                                    isDestructive: true,
                                    onClick: function() { removeDefTerm( index ); },
                                    style: { padding: '4px' },
                                }, '✕' )
                            );
                        } ),
                        wp.element.createElement( Button, {
                            variant: 'secondary',
                            onClick: addDefTerm,
                            style: { marginTop: '4px' },
                            isSmall: true,
                        }, __( '+ Add Term', 'djot-markup-for-wp' ) )
                    ),
                    // Definitions section
                    wp.element.createElement( 'div', { style: { marginBottom: '16px' } },
                        wp.element.createElement( 'label', { style: { display: 'block', fontWeight: 600, marginBottom: '8px' } },
                            __( 'Definitions (dd):', 'djot-markup-for-wp' )
                        ),
                        defListDefinitions.map( function( definition, index ) {
                            return wp.element.createElement( 'div', {
                                key: index,
                                style: { display: 'flex', alignItems: 'flex-start', gap: '8px', marginBottom: '8px' },
                            },
                                wp.element.createElement( 'textarea', {
                                    value: definition,
                                    onChange: function( e ) { updateDefDefinition( index, e.target.value ); },
                                    placeholder: __( 'Definition... (use blank lines for multiple paragraphs)', 'djot-markup-for-wp' ),
                                    style: { flex: 1, padding: '8px', border: '1px solid #ccc', borderRadius: '4px', minHeight: '60px', resize: 'vertical', boxSizing: 'border-box' },
                                } ),
                                defListDefinitions.length > 1 && wp.element.createElement( Button, {
                                    variant: 'tertiary',
                                    isDestructive: true,
                                    onClick: function() { removeDefDefinition( index ); },
                                    style: { padding: '4px', marginTop: '4px' },
                                }, '✕' )
                            );
                        } ),
                        wp.element.createElement( Button, {
                            variant: 'secondary',
                            onClick: addDefDefinition,
                            style: { marginTop: '4px' },
                            isSmall: true,
                        }, __( '+ Add Definition', 'djot-markup-for-wp' ) )
                    ),
                    wp.element.createElement( 'p', { style: { fontSize: '12px', color: '#666', marginTop: '0' } },
                        __( 'Tip: Press Enter in term field to add another term', 'djot-markup-for-wp' )
                    ),
                    wp.element.createElement(
                        'div',
                        { style: { marginTop: '16px', borderTop: '1px solid #ddd', paddingTop: '16px' } },
                        wp.element.createElement( Button, {
                            variant: 'primary',
                            onClick: onInsertDefList,
                        }, __( 'Insert', 'djot-markup-for-wp' ) ),
                        wp.element.createElement( Button, {
                            variant: 'secondary',
                            onClick: function() { setShowDefListModal( false ); },
                            style: { marginLeft: '8px' },
                        }, __( 'Cancel', 'djot-markup-for-wp' ) )
                    )
                ),
                // Video Modal
                showVideoModal && wp.element.createElement(
                    Modal,
                    {
                        title: __( 'Insert Video', 'djot-markup-for-wp' ),
                        onRequestClose: function() { setShowVideoModal( false ); },
                    },
                    wp.element.createElement( TextControl, {
                        label: __( 'Video URL', 'djot-markup-for-wp' ),
                        value: videoUrl,
                        onChange: setVideoUrl,
                        type: 'url',
                        placeholder: 'https://www.youtube.com/watch?v=...',
                        help: __( 'YouTube, Vimeo, TikTok, Twitter, and other oEmbed-supported URLs', 'djot-markup-for-wp' ),
                    } ),
                    wp.element.createElement( TextControl, {
                        label: __( 'Caption (optional)', 'djot-markup-for-wp' ),
                        value: videoCaption,
                        onChange: setVideoCaption,
                    } ),
                    wp.element.createElement( TextControl, {
                        label: __( 'Width (optional)', 'djot-markup-for-wp' ),
                        value: videoWidth,
                        onChange: setVideoWidth,
                        type: 'number',
                        placeholder: '650',
                        help: __( 'Width in pixels', 'djot-markup-for-wp' ),
                    } ),
                    wp.element.createElement(
                        'div',
                        { style: { marginTop: '16px' } },
                        wp.element.createElement( Button, {
                            variant: 'primary',
                            onClick: onInsertVideo,
                        }, __( 'Insert Video', 'djot-markup-for-wp' ) ),
                        wp.element.createElement( Button, {
                            variant: 'secondary',
                            onClick: function() { setShowVideoModal( false ); },
                            style: { marginLeft: '8px' },
                        }, __( 'Cancel', 'djot-markup-for-wp' ) )
                    )
                ),
                // Main content area
                content || isPreviewMode
                    ? wp.element.createElement(
                          'div',
                          { className: 'wpdjot-block-wrapper', ref: textareaRef },
                          ! isPreviewMode &&
                              wp.element.createElement( PlainText, {
                                  value: content,
                                  onChange: onChangeContent,
                                  onSelect: updateSelection,
                                  onClick: updateSelection,
                                  onKeyUp: updateSelection,
                                  onKeyDown: handleTextareaKeyDown,
                                  className: 'wpdjot-editor',
                                  placeholder: __( 'Write your Djot markup here...\n\n# Heading\n\nThis is _emphasized_ and *strong* text.\n\n- List item 1\n- List item 2', 'djot-markup-for-wp' ),
                              } ),
                          isPreviewMode &&
                              wp.element.createElement(
                                  'div',
                                  { className: 'wpdjot-preview-wrapper' },
                                  wp.element.createElement(
                                      'div',
                                      { className: 'wpdjot-preview-header' },
                                      wp.element.createElement( 'span', null, __( 'Preview', 'djot-markup-for-wp' ) ),
                                      wp.element.createElement(
                                          'button',
                                          {
                                              className: 'wpdjot-edit-button',
                                              onClick: function() { setIsPreviewMode( false ); },
                                              title: __( 'Press ESC to exit preview', 'djot-markup-for-wp' ),
                                          },
                                          __( 'Edit (ESC)', 'djot-markup-for-wp' )
                                      )
                                  ),
                                  isLoading
                                      ? wp.element.createElement( Spinner, null )
                                      : wp.element.createElement( 'div', {
                                            ref: previewRef,
                                            className: 'wpdjot-preview djot-content',
                                            dangerouslySetInnerHTML: { __html: preview },
                                        } )
                              )
                      )
                    : wp.element.createElement(
                          Placeholder,
                          {
                              icon: 'editor-code',
                              label: __( 'Djot', 'djot-markup-for-wp' ),
                              instructions: __( 'Write content using Djot markup language. Use the toolbar above for formatting.', 'djot-markup-for-wp' ),
                          },
                          wp.element.createElement(
                              'div',
                              { ref: textareaRef, style: { width: '100%' } },
                              wp.element.createElement( PlainText, {
                                  value: content,
                                  onChange: onChangeContent,
                                  onSelect: updateSelection,
                                  onClick: updateSelection,
                                  onKeyUp: updateSelection,
                                  onKeyDown: handleTextareaKeyDown,
                                  className: 'wpdjot-editor',
                                  placeholder: __( '# Hello World\n\nThis is _emphasized_ and *strong* text.', 'djot-markup-for-wp' ),
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
