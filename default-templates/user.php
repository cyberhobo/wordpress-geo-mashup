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
 *
 * @package GeoMashup
 */
?>
<div class="locationinfo user-location-info">
<?php if ( GeoMashupQuery::have_users() ) : ?>

<?php GeoMashupQuery::list_users( 'callback=geo_mashup_user_default_template' ); ?>

<?php else : ?>

	<h2 class="center">Not Found</h2>
	<p class="center">Sorry, but you are looking for something that isn't here.</p>

<?php endif; ?>
</div>
<?php 
/**
 * Template callback for GeoMashupQuery::list_users()
 *
 * @since 1.3
 * @package GeoMashup
 * @param object $user The user to display.
 */
function geo_mashup_user_default_template( $user ) { 
	GeoMashupQuery::set_the_user( $user );
?>
<div id="div-user-<?php echo esc_attr( $user->ID ); ?>" class="vcard">
	<div class="fn">
		<span class="type"><?php _e( 'Display Name' ); ?></span>: <span class="value"><?php echo esc_attr( $user->display_name ); ?></span>
	</div>
	<?php echo GeoMashup::location_info( 'fields=locality_name,admin_code&format=<div class="adr"><span class="locality">%s</span>, <span class="region">%s</span></div>' ); ?>
	<?php if ( isset( $user->user_url ) && strlen( $user->user_url ) > 7 ) : ?>
	<div class="url">
		<span class="type"><?php _e( 'Website' ); ?></span>: <a class="value" href="<?php echo esc_attr( $user->user_url ); ?>"><?php echo esc_attr( $user->user_url ); ?></a>
	</div>
	<?php endif; ?>
</div>
<?php } ?>

