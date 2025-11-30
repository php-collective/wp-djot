( function( wp ) {
    const { registerBlockType } = wp.blocks;
    const { useState, useEffect, useCallback } = wp.element;
    const { TextareaControl, PanelBody, ToggleControl, Placeholder, Spinner } = wp.components;
    const { InspectorControls, useBlockProps } = wp.blockEditor;
    const { __ } = wp.i18n;
    const apiFetch = wp.apiFetch;

    registerBlockType( 'wp-djot/djot', {
        edit: function( props ) {
            const { attributes, setAttributes } = props;
            const { content } = attributes;
            const [ preview, setPreview ] = useState( '' );
            const [ isPreviewMode, setIsPreviewMode ] = useState( false );
            const [ isLoading, setIsLoading ] = useState( false );
            const blockProps = useBlockProps( {
                className: 'wp-djot-block',
            } );

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

            function onChangeContent( newContent ) {
                setAttributes( { content: newContent } );
            }

            return wp.element.createElement(
                'div',
                blockProps,
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
                    )
                ),
                content || isPreviewMode
                    ? wp.element.createElement(
                          'div',
                          { className: 'wp-djot-block-wrapper' },
                          ! isPreviewMode &&
                              wp.element.createElement( TextareaControl, {
                                  label: __( 'Djot Content', 'wp-djot' ),
                                  value: content,
                                  onChange: onChangeContent,
                                  rows: 10,
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
                                          },
                                          __( 'Edit', 'wp-djot' )
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
                              instructions: __( 'Write content using Djot markup language.', 'wp-djot' ),
                          },
                          wp.element.createElement( TextareaControl, {
                              value: content,
                              onChange: onChangeContent,
                              rows: 6,
                              className: 'wp-djot-editor',
                              placeholder: __( '# Hello World\n\nThis is _emphasized_ and *strong* text.', 'wp-djot' ),
                          } )
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
