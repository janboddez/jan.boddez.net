( function ( blocks, element, blockEditor, components, i18n ) {
	var el = element.createElement;

	var BlockControls = blockEditor.BlockControls;
	var InnerBlocks   = blockEditor.InnerBlocks;
	var useBlockProps = blockEditor.useBlockProps;

	var __      = i18n.__;
	var sprintf = i18n.sprintf;

	blocks.registerBlockType( 'janboddez/post-meta', {
		description: __( 'Tie together several other post-related blocks.', 'janboddez' ),
		edit: ( props ) => {
			var location = props.attributes.location;

			return el( 'div', useBlockProps(),
				el( BlockControls ),
				el( blockEditor.InspectorControls, { key: 'inspector' },
					el( components.PanelBody, {
							title: __( 'Settings', 'janboddez' ),
							initialOpen: true,
						},
						el( components.RadioControl, {
							label: __( 'Block Location', 'janboddez' ),
							selected: location,
							options: [
								{ label: __( 'Top', 'janboddez' ), value: 'top' },
								{ label: __( 'Bottom', 'janboddez' ), value: 'bottom' },
							],
							onChange: ( value ) => { props.setAttributes( { location: value } ) },
						} )
					)
				),
				el( InnerBlocks, {
					template: [ [ 'core/paragraph' ] ],
					templateLock: false,
				} )
			);
		},
		save: ( props ) => el( 'div', useBlockProps.save(),
			el( InnerBlocks.Content )
		),
	} );
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n );
