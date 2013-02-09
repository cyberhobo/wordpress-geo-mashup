<?php
/**
 * This is the default template for the comment info window in Geo Mashup maps. 
 *
 * Don't modify this file! It will be overwritten by upgrades.
 *
 * Instead, copy this file to "geo-mashup-comment.php" in your theme directory, 
 * or "comment.php" in the Geo Mashup Custom plugin directory, if you have that 
 * installed. Those files take precedence over this one.
 *
 * For styling of the info window, see map-style-default.css.
 *
 * @package GeoMashup
 */
?>
<div class="locationinfo comment-location-info">
<?php if ( GeoMashupQuery::have_comments() ) : ?>

	<?php GeoMashupQuery::list_comments( 'callback=geo_mashup_comment_default' ); ?>

<?php else : ?>

	<h2 class="center">Not Found</h2>
	<p class="center">Sorry, but you are looking for something that isn't here.</p>

<?php endif; ?>

</div>
<?php 
/**
 * Template callback for GeoMashupQuery::list_comments()
 *
 * Use the newer form of template, where the individual comment template goes in 
 * a function that matches the callback argument to list_comments
 *
 * @since 1.3
 * @access public
 * @package GeoMashup
 *
 * @param object $comment The comment to display
 * @param array $args Arguments from wp_list_comments
 * @param mixed $depth Nested depth
 */
function geo_mashup_comment_default( $comment, $args, $depth ) {
	// Enable the WordPress comment functions
	GeoMashupQuery::set_the_comment( $comment );
	// From here to the closing curly brace should look like a familiar template
?>
	<div id="div-comment-<?php comment_ID() ?>" class="<?php comment_class(''); ?>">
		<div class="comment-author vcard">
		<?php printf(__('<cite class="fn">%s</cite> <span class="says">says:</span>'), get_comment_author_link()) ?>
		</div>
		<div class="comment-meta commentmetadata">
			<a href="<?php echo esc_html( get_comment_link( $comment->comment_ID ) ) ?>"><?php printf(__('%1$s at %2$s'), get_comment_date(),  get_comment_time()) ?></a>
		</div>
		<?php comment_text() ?>

	</div>
<?php } ?>
