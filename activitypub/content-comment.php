<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$content = apply_filters( 'comment_text', $comment->comment_content, $obj );

// Append only a permalink.
$permalink = get_comment_link( $comment );
// Wait, let's try and replace it with a shortlink.
$shortlink = wp_get_shortlink( $comment->comment_post_ID );
if ( ! empty( $shortlink ) ) {
	$permalink = str_replace( get_permalink( $comment->comment_post_ID ), $shortlink, $permalink );
}

$content .= '<p><a href="' . esc_url( $permalink ) . '">' . esc_html( $permalink ) . '</a></p>';

echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
