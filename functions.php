<?php

/**
 * Messes with the Bookmarks shortcode's output.
 */
add_filter( 'wp_list_bookmarks', function ( $output ) {
	// Add a fake "mystery man" favicon to links that don't have one. Requires `link_before='<span>'` and
	// `link_after='</span>'`.
	if ( preg_match_all( '~<span>.+?</span>~', $output, $matches ) ) {
		$count = count( $matches[0] );

		for ( $i = 0; $i < $count; $i++ ) {
			if ( false === strpos( $matches[0][ $i ], '<img ' ) ) {
				$output = str_replace(
					$matches[0][ $i ],
					str_replace( '<span>', '<span><img src="' . esc_url( home_url( 'wp-content/plugins/indieblocks/assets/mm.png' ) ) . '" width="32" height="32" alt="">', $matches[0][ $i ] ),
					$output
				);
			}
		}
	}

	// Another ugly hack, where we move the images out of the `span` elements, so that the text can be styled sparately.
	$output = preg_replace( '~<span>(<img[^>]+>)~', "$1 <span>", $output ); // phpcs:ignore Squiz.Strings.DoubleQuoteUsage.NotRequired

	return $output;
} );

/**
 * Registers the `janboddez/post-meta` block.
 */
add_action( 'init', function () {
	register_block_type_from_metadata(
		__DIR__ . '/blocks/post-meta',
		array(
			'render_callback' => 'janboddez_render_post_meta_block',
		)
	);
} );

/**
 * Renders the `janboddez/post-meta` block.
 *
 * @param  array    $attributes Block attributes.
 * @param  string   $content    Block default content.
 * @param  WP_Block $block      Block instance.
 * @return string               Output HTML.
 */
function janboddez_render_post_meta_block( $attributes, $content, $block ) {
	if ( ! isset( $block->context['postId'] ) ) {
		return '';
	}

	if ( ! isset( $block->context['postType'] ) ) {
		return '';
	}

	if ( ! empty( $attributes['location'] ) && 'top' === $attributes['location'] ) {
		if ( in_array( $block->context['postType'], array( 'post', 'page' ), true ) ) {
			// Just render the block as defined in the template.
			return $block->render( array( 'dynamic' => false ) );
		} else {
			// We don't want "top" output for notes and the like (and will render the "bottom" block instead).
			return '';
		}
	}

	if ( ! empty( $attributes['location'] ) && 'bottom' === $attributes['location'] ) {
		if ( in_array( $block->context['postType'], array( 'post', 'page' ), true ) ) {
			$output = '';

			// Post tags to the left.
			$output .= (
				new WP_Block(
					array(
						'blockName' => 'core/post-terms',
						'attrs'     => array(
							'term'   => 'post_tag',
							'prefix' => __( 'Tagged: ' ),
						),
					),
					array(
						'postId'   => $block->context['postId'],
						'postType' => $block->context['postType'],
					)
				)
			)->render();

			// And a Syndication block to the right.
			$output .= (
				new WP_Block(
					array(
						'blockName' => 'indieblocks/syndication',
						'attrs'     => array(
							'className' => 'alignright',
							'prefix'    => __( 'Also on ' ),
						),
					),
					array(
						'postId'   => $block->context['postId'],
						'postType' => $block->context['postType'],
					)
				)
			)->render();

			if ( empty( $output ) ) {
				return '';
			}

			// Generate block wrapper HTML.
			$wrapper_attributes = get_block_wrapper_attributes();

			$output = '<div ' . $wrapper_attributes . ' >' .
				$output .
			'</div>';

			// Add custom classes and styles.
			$processor = new \WP_HTML_Tag_Processor( $output );

			$processor->next_tag( 'div' );
			$processor->add_class( 'has-tertiary-color has-text-color has-small-font-size' );

			$style = $processor->get_attribute( 'style' );
			if ( null === $style ) {
				$processor->set_attribute( 'style', esc_attr( 'display:flex;gap:var(--wp--preset--spacing--30);justify-content:space-between;flex-wrap:nowrap !important;' ) );
			} else {
				$processor->set_attribute( 'style', esc_attr( rtrim( $style, ';' ) . ';display:flex;gap:var(--wp--preset--spacing--30);justify-content:space-between;flex-wrap:nowrap !important;' ) );
			}

			$output = $processor->get_updated_html();
		} else {
			// Just render the block as defined in the template.
			$output = $block->render( array( 'dynamic' => false ) );
		}

		return $output;
	}
}

/**
 * Loads `highlight.js`.
 */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'janboddez-style', get_stylesheet_uri(), array(), wp_get_theme()->get( 'Version' ) );

	wp_register_script( 'hljs', get_stylesheet_directory_uri() . '/assets/js/highlight.min.js', array(), '11.7.0', true );

	wp_enqueue_style( 'highlight', get_stylesheet_directory_uri() . '/assets/css/highlight.css' );
	wp_enqueue_script( 'highlight-js', get_stylesheet_directory_uri() . '/assets/js/highlight.js', array( 'hljs' ), false, true );
} );

/**
 * Adds "RSS-only" post support.
 *
 * @todo: Move to mu-plugin.
 */
add_action( 'pre_get_posts', function ( $query ) {
	if ( is_admin() ) {
		return $query;
	}

	if ( ! $query->is_main_query() ) {
		return $query;
	}

	if ( $query->is_feed() ) {
		return $query;
	}

	if ( ! empty( $query->query_vars['suppress_filters'] ) ) {
		return $query;
	}

	$rss_club = get_category_by_slug( 'rss-club' ); // What's in a name?
	if ( empty( $rss_club->term_id ) || is_category( $rss_club->term_id ) ) {
		return $query;
	}

	$query->set( 'category__not_in', array( $rss_club->term_id ) );
} );

/**
 * Replaces front-end social icons with our own.
 *
 * We probably shouldn't do this (this way).
 */
add_filter( 'render_block_core/social-link', function ( $block_content, $parsed_block, $block ) {
	// Last I checked core did not support Pixelfed.
	if ( ! empty( $parsed_block['attrs']['url'] ) && 0 === strpos( $parsed_block['attrs']['url'], 'https://pixelfed.social' ) ) {
		$processor = new \WP_HTML_Tag_Processor( $block_content );

		$processor->next_tag( 'li' );
		$processor->set_attribute( 'class', esc_attr( 'wp-social-link wp-social-link-pixelfed wp-block-social-link' ) );

		$processor->next_tag( 'path' );
		$processor->set_attribute( 'd', esc_attr( 'M12 22C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10zm-.794-7.817h1.835c1.728 0 3.129-1.364 3.129-3.046 0-1.682-1.401-3.046-3.13-3.046h-2.647c-.997 0-1.805.787-1.805 1.757v6.84z' ) );

		$block_content = $processor->get_updated_html();
	}

	// The rest is feed (RSS), Github, and Mastodon icons that somehow go better together than WordPress' defaults.
	if ( ! empty( $parsed_block['attrs']['service'] ) && 'feed' === $parsed_block['attrs']['service'] ) {
		$processor = new \WP_HTML_Tag_Processor( $block_content );

		$processor->next_tag( 'path' );
		$processor->set_attribute( 'd', esc_attr( 'M7.909 18.545a2.455 2.455 0 1 1-4.91 0 2.455 2.455 0 0 1 4.91 0zm6.545 1.573a.827.827 0 0 1-.218.613.778.778 0 0 1-.6.269H11.91a.807.807 0 0 1-.806-.742 8.18 8.18 0 0 0-7.363-7.363.808.808 0 0 1-.741-.806v-1.725c0-.23.09-.448.268-.601a.784.784 0 0 1 .55-.218h.064a11.481 11.481 0 0 1 7.222 3.35 11.482 11.482 0 0 1 3.35 7.223zm6.545.025a.779.779 0 0 1-.23.601.783.783 0 0 1-.588.256h-1.828a.814.814 0 0 1-.819-.767c-.421-7.428-6.34-13.347-13.767-13.781A.812.812 0 0 1 3 5.646V3.818c0-.23.09-.435.256-.588A.793.793 0 0 1 3.818 3h.038c4.475.23 8.68 2.11 11.85 5.292A18 18 0 0 1 21 20.143z' ) );

		$block_content = $processor->get_updated_html();
	}

	if ( ! empty( $parsed_block['attrs']['service'] ) && 'github' === $parsed_block['attrs']['service'] ) {
		$processor = new \WP_HTML_Tag_Processor( $block_content );

		$processor->next_tag( 'path' );
		$processor->set_attribute( 'd', esc_attr( 'M12 2.492c5.52 0 10 4.479 10 10 0 4.414-2.865 8.164-6.836 9.492-.508.09-.69-.221-.69-.482 0-.325.013-1.406.013-2.747 0-.938-.313-1.537-.677-1.85 2.226-.247 4.57-1.093 4.57-4.934 0-1.094-.39-1.98-1.028-2.682.104-.26.442-1.276-.105-2.657-.833-.26-2.747 1.029-2.747 1.029a9.417 9.417 0 0 0-5 0S7.586 6.37 6.753 6.632c-.547 1.38-.209 2.396-.105 2.657A3.873 3.873 0 0 0 5.62 11.97c0 3.828 2.33 4.687 4.557 4.935-.286.26-.547.703-.638 1.34-.573.261-2.031.704-2.904-.832-.546-.951-1.536-1.03-1.536-1.03-.977-.012-.065.613-.065.613.65.3 1.107 1.458 1.107 1.458.586 1.784 3.372 1.185 3.372 1.185 0 .833.013 1.615.013 1.862 0 .26-.182.573-.69.482C4.865 20.656 2 16.906 2 12.492c0-5.521 4.48-10 10-10zM5.79 16.854c.025-.052-.014-.118-.092-.157-.078-.026-.143-.013-.17.026-.025.052.014.118.092.157.065.039.143.026.17-.026zm.403.442c.052-.039.039-.13-.026-.208-.065-.065-.157-.091-.209-.039-.052.039-.039.13.026.208.065.065.157.091.209.04zm.39.586c.065-.052.065-.156 0-.247-.052-.091-.156-.13-.221-.078-.065.039-.065.143 0 .234.065.091.17.13.221.091zm.547.547c.052-.052.026-.17-.052-.247-.091-.091-.208-.104-.26-.04-.065.053-.04.17.052.248.09.091.208.104.26.04zm.742.326c.026-.078-.052-.17-.169-.209-.104-.026-.221.013-.247.091-.026.078.052.17.169.196.104.039.221 0 .247-.078zm.82.065c0-.091-.103-.157-.22-.144-.118 0-.209.065-.209.144 0 .09.091.156.221.143.118 0 .209-.065.209-.143zm.756-.13c-.013-.078-.117-.13-.234-.118-.118.026-.196.104-.183.196.013.078.117.13.235.104.117-.026.195-.104.182-.182z' ) );

		$block_content = $processor->get_updated_html();
	}

	if ( ! empty( $parsed_block['attrs']['service'] ) && 'mastodon' === $parsed_block['attrs']['service'] ) {
		$processor = new \WP_HTML_Tag_Processor( $block_content );

		$processor->next_tag( 'path' );
		$processor->set_attribute( 'd', esc_attr( 'M21.377 14.59c-.288 1.48-2.579 3.102-5.21 3.416-1.372.164-2.723.314-4.163.248-2.356-.108-4.215-.562-4.215-.562 0 .23.014.448.042.652.306 2.325 2.306 2.464 4.2 2.529 1.91.065 3.612-.471 3.612-.471l.079 1.728s-1.337.718-3.718.85c-1.314.072-2.944-.033-4.844-.536-4.119-1.09-4.824-5.481-4.935-9.936-.033-1.323-.013-2.57-.013-3.613 0-4.556 2.985-5.891 2.985-5.891C6.702 2.313 9.284 2.022 11.969 2h.066c2.685.022 5.269.313 6.774 1.004 0 0 2.984 1.335 2.984 5.89 0 0 .038 3.362-.416 5.695zm-3.104-5.342c0-1.127-.277-2.032-.864-2.686-.594-.663-1.373-1.002-2.34-1.002-1.118 0-1.965.43-2.525 1.29L12 7.761l-.544-.913c-.56-.86-1.407-1.29-2.525-1.29-.967 0-1.746.34-2.34 1.003-.577.663-.864 1.559-.864 2.686v5.516h2.186V9.41c0-1.128.474-1.701 1.424-1.701 1.05 0 1.577.68 1.577 2.023v2.93h2.172v-2.93c0-1.344.527-2.023 1.577-2.023.95 0 1.424.573 1.424 1.701v5.354h2.186V9.248z' ) );

		$block_content = $processor->get_updated_html();
	}

	return $block_content;
}, 10, 3 );

/**
 * Adds a shortlink to the Post Date block.
 */
add_filter( 'render_block_core/post-date', function ( $block_content, $parsed_block, $block ) {
	if ( empty( $block->context['postId'] ) ) {
		return $block_content;
	}

	$short_url = get_post_meta( $block->context['postId'], 'short_url', true );

	if ( '' === $short_url ) {
		return $block_content;
	}

	$link = sprintf( '<span class="shortlink"><a href="%s" rel="shortlink">%s</a></span>', esc_url( $short_url ), esc_html( preg_replace( '~https?://~', '', $short_url ) ) );

	return preg_replace( '~</div>$~', '<span class="sep" aria-hidden="true"> • </span>' . $link . '</div>', $block_content );
}, 10, 3 );
