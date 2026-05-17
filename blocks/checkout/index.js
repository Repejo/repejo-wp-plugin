/**
 * Build-free editor registration for the Repejo Checkout block.
 *
 * The block is server-rendered (PHP render_callback), so the editor only
 * needs a static placeholder. We rely solely on globals that WordPress
 * enqueues in the editor by default (wp.blocks, wp.element, wp.blockEditor,
 * wp.components, wp.i18n) so no bundler/build step is required.
 */
( function ( wp ) {
	if ( ! wp || ! wp.blocks ) {
		return;
	}

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var Placeholder = wp.components.Placeholder;

	wp.blocks.registerBlockType( 'repejo/checkout', {
		edit: function () {
			return el(
				'div',
				useBlockProps(),
				el(
					Placeholder,
					{
						icon: 'money-alt',
						label: __( 'Repejo Checkout', 'repejo-wp-plugin' ),
						instructions: __(
							'Checkouten renderas på den publika sidan. Givar-id hämtas automatiskt från den fina URL:en (/sida/<id>).',
							'repejo-wp-plugin'
						),
					}
				)
			);
		},
		// Dynamic block: server-rendered, nothing saved to post content.
		save: function () {
			return null;
		},
	} );
} )( window.wp );
