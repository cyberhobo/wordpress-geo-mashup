<?php
/**
 * This is the default template for the User info window in Geo Mashup maps. 
 *
 * Don't modify this file! It will be overwritten by upgrades.
 *
 * Instead, copy this file to "geo-mashup-user.php" in your theme directory, 
 * or "user.php" in the Geo Mashup Custom plugin directory, if you have that 
 * installed. Those files take precedence over this one.
 *
 * For styling of the info window, see map-style-default.css.
 */
global $users;
?>
<div class="locationinfo user-location-info">
<?php if ( count( $users ) > 0 ) : ?>

	<?php foreach( $users as $user ) : ?>
	<div id="div-user-<?php echo $user->ID ?>" class="vcard">
		<div class="fn">
		<?php printf(__('<span class="type">Display Name</span>: <span class="value">%s</span>'), $user->display_name) ?>
		</div>
		<?php if ( isset( $user->user_url ) && strlen( $user->user_url ) > 7 ) : ?>
		<div class="url">
		<?php printf(__('<span class="type">Website</span>: <a class="value" href="%s">%s</a>'), $user->user_url, $user->user_url) ?>
		</div>
		<?php endif; ?>
	</div>
	<?php endforeach; ?>

<?php else : ?>

	<h2 class="center">Not Found</h2>
	<p class="center">Sorry, but you are looking for something that isn't here.</p>

<?php endif; ?>

</div>
