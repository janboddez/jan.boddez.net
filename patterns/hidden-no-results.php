<?php
/**
 * Title: Hidden No Results Content
 * Slug: janboddeznet/hidden-no-results-content
 * Inserter: no
 */
?>

<!-- wp:paragraph {"fontFamily":"merriweather"} -->
<p class="has-merriweather-font-family"><?php echo esc_html_x( 'Sorry, but nothing matched your search terms. Please try again with some different keywords.', 'Message explaining that there are no results returned from a search', 'indielol' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:search {"label":"<?php echo esc_html_x( 'Search', 'label', 'indielol' ); ?>","placeholder":"<?php echo esc_attr_x( 'Search...', 'placeholder for search field', 'indielol' ); ?>","showLabel":false,"buttonText":"<?php esc_attr_e( 'Search', 'indielol' ); ?>","buttonUseIcon":true,"style":{"border":{"radius":"4px","width":"1px"}},"borderColor":"tertiary"} /-->

<!-- wp:spacer {"height":"var(--wp--preset--spacing--80)"} -->
<div style="height:var(--wp--preset--spacing--80)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
