<?php
/**
 * Default Geo Mashup Search widget form template.
 *
 * THIS FILE WILL BE OVERWRITTEN BY AUTOMATIC UPGRADES
 * See the geo-mashup-search.php plugin file for license
 *
 * Copy this to a file named geo-mashup-search-form.php in your active theme folder
 * to customize. For bonus points delete this message in the copy!
 *
 * Variables in scope:
 * $widget     object      The widget generating this form
 * $widget_id	string		The unique widget identifier
 * $instance   array       The widget instance data
 * $action_url string      The URL of the page chosen to display results
 * $categories array       Category objects to include in the category menu, if any.
 * $radii      array       Radius distances to include in the radius menu, if any.
 */
?>
<form class="geo-mashup-search-form" method="post" action="<?php echo $action_url; ?>">

<input name="object_name" type="hidden" value="<?php echo esc_attr( $instance['object_name'] ); ?>" />
<input name="results_page_id" type="hidden" value="<?php echo esc_attr( $instance['results_page_id'] ); ?>" />
<?php if ( !empty( $categories ) ) : ?>
	<label for="<?php echo $widget_id; ?>-categories"><?php _e( 'find', 'GeoMashup' ); ?>
	<select id="<?php echo $widget_id; ?>-categories" name="map_cat">
	<?php foreach ( $categories as $cat ) : ?>
		<option value="<?php echo $cat->term_id; ?>"<?php
			if ( $widget->get_default_value( $_POST, 'map_cat' ) == $cat->term_id )
				echo ' selected="selected"';
		?>><?php echo $cat->name; ?></option>
	<?php endforeach; ?>
	</select>
	<?php _e( 'posts', 'GeoMashup' ); ?></label>
<?php endif; // Categories ?>

<?php if ( !empty( $radii ) ) : ?>
	<label for="<?php echo $widget_id; ?>-radius"><?php _e( 'within', 'GeoMashup' ); ?></label>
	<select id="<?php echo $widget_id; ?>-radius" name="radius">
	<?php foreach ( $radii as $radius ) : ?>
		<option value="<?php echo $radius; ?>"<?php
			if ( $widget->get_default_value( $_POST, 'radius' ) == $radius )
					echo ' selected="selected"';
		?>><?php echo $radius; ?></option>
	<?php endforeach; ?>
	</select>
	<?php echo esc_html( $instance['units'] ); ?>
<?php endif; // Radius ?>

	<input name="units" type="hidden" value="<?php echo esc_attr( $instance['units'] ); ?>" />
	
	<label for="<?php echo $widget_id; ?>-input"><?php _e( empty( $radii ) ? 'near' : 'of', 'GeoMashup' ); ?></label>
	<input id="<?php echo $widget_id; ?>-input" class="geo-mashup-search-input" name="location_text" type="text" value="<?php
		if ( !empty( $_POST['location_text'] ) ) {
			echo esc_attr( $_POST['location_text'] );
		} else if ( !empty( $instance['default_search_text'] ) ) {
			echo esc_attr( $instance['default_search_text'] );
		}
	?>" />
	<input id="<?php echo $widget_id; ?>-submit" name="geo_mashup_search_submit" type="submit" value="<?php _e( 'Search', 'GeoMashup' ); ?>" />
<?php if ( !empty( $instance['find_me_button'] ) ) : ?>
	<input name="geolocation" type="hidden" value="" />
	<button id="<?php echo $widget_id; ?>-find-me" class="geo-mashup-search-find-me" style="display:none;"><?php echo $instance['find_me_button']; ?></button>
<?php endif; ?>
</form>
