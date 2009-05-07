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
 */
global $comments;
?>
<div class="locationinfo comment-location-info">
<?php if ( count( $comments ) > 0 ) : ?>

	<?php foreach( $comments as $comment ) : ?>
	<div id="div-comment-<?php comment_ID() ?>" class="<?php comment_class(''); ?>">
		<div class="comment-author vcard">
		<?php printf(__('<cite class="fn">%s</cite> <span class="says">says:</span>'), get_comment_author_link()) ?>
		</div>
		<div class="comment-meta commentmetadata">
			<a href="<?php echo htmlspecialchars( get_comment_link( $comment->comment_ID ) ) ?>"><?php printf(__('%1$s at %2$s'), get_comment_date(),  get_comment_time()) ?></a>
		</div>
		<?php comment_text() ?>

	</div>
	<?php endforeach; ?>

<?php else : ?>

	<h2 class="center">Not Found</h2>
	<p class="center">Sorry, but you are looking for something that isn't here.</p>

<?php endif; ?>

</div>
