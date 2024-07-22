<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$shortlink = wp_get_shortlink( $post->ID );
if ( ! empty( $shortlink ) ) {
	$permalink = $shortlink;
} else {
	$permalink = get_permalink( $post );
}
?>

<?php echo apply_filters( 'the_content', $post->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<p><a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $permalink ); ?></a></p>
