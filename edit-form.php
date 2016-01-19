<?php
/**
 * A function wrapper for the location editor HTML.
 *
 * @package GeoMashup
 */

/**
 * Print the Geo Mashup location editor HTML for an object.
 *
 * Goals for this interface are to make it usable for any kind of locatable
 * object, to be usable without javascript, functional on the front end or admin, 
 * and eventually adaptable to editing multiple locations for an object.
 *
 * It's assumed this will go inside an existing form for editing the object, 
 * such as the WordPress admin post edit form.
 *
 * @since 1.2
 * @see geo-mashup-ui-managers.php
 * @see geo-mashup-location-editor.js
 * @uses edit-form.css
 * @access public
 *
 * @param string $object_name The type of object, e.g. 'post', 'user', etc.
 * @param string $object_id The ID of the object being edited.
 * @param string $ui_manager Optionally the name of UI Manager class to use for AJAX operations.
 */
function geo_mashup_edit_form( $object_name, $object_id, $ui_manager = '' ) {
	global $geo_mashup_options;

	$help_class = 'geo-mashup-js';
	$add_input_style = 'style="display:none;"';
	$update_input_style = $delete_input_style = '';
	$coordinate_string = '';

	// Load any existing location for the object
	$location = GeoMashupDB::get_object_location( $object_name, $object_id );
	if ( empty( $location ) ) {
		$location = GeoMashupDB::blank_object_location();
		$help_class = '';
		$add_input_style = '';
		$update_input_style = $delete_input_style = 'style="display:none;"';
	} else {
		$coordinate_string = $location->lat . ',' . $location->lng;
	}

	$post_location_name = $location->saved_name;
	$kml_url = '';

	// Set a Geo date default when needed & possible
	$date_missing = ( empty( $location->geo_date ) || '0000-00-00 00:00:00' == $location->geo_date );
	if ( 'post' == $object_name) {
		if ( $date_missing ) {

			// Geo date defaults to post date
			$post = get_post( $object_id );
			$location->geo_date = $post->post_date;
			if ( !empty( $location->id ) ) {
				GeoMashupDB::set_object_location( $object_name, $object_id, $location->id, false, $location->geo_date );
			}

		}

		// For posts, look for a KML attachment
		$kml_urls = GeoMashup::get_kml_attachment_urls( $object_id );
		if (count($kml_urls)>0) {
			$kml_url = array_pop($kml_urls);
		}

	} else if ( 'user' == $object_name && $date_missing ) {

		// Geo date defaults to registration date
		$user = get_userdata( $object_id );
		$location->geo_date = $user->user_registered;
		if ( !empty( $location->id ) ) {
			GeoMashupDB::set_object_location( $object_name, $object_id, $location->id, false, $location->geo_date );
		}

	} else if ( 'comment' == $object_name && $date_missing ) {

		// Geo date defaults to comment date
		$comment = get_comment( $object_id );
		$location->geo_date = $comment->comment_date;
		if ( !empty( $location->id ) ) {
			GeoMashupDB::set_object_location( $object_name, $object_id, $location->id, false, $location->geo_date );
		}

	}	
	if ( empty( $location->geo_date ) ) {
		$location_datetime = mktime();
	} else {
		$location_datetime = strtotime( $location->geo_date );
	}
	$location_date = date( 'M j, Y', $location_datetime );
	$location_hour = date( 'G', $location_datetime );
	$location_minute = date( 'i', $location_datetime );

	// Load saved locations
	$saved_locations = GeoMashupDB::get_saved_locations( );
	$saved_location_options = array();
	if ( ! empty( $saved_locations ) ) {
		foreach ( $saved_locations as $saved_location ) {
			$escaped_name = str_replace( array( "\r\n", "\r", "\n" ), '', $saved_location->saved_name );
			if ( $saved_location->id != $location->id ) 
				$selected = '';
			else
				$selected = ' selected="selected"';
			$saved_location_options[] = '<option value="' . esc_attr( $saved_location->id . '|' . $saved_location->lat . '|' .
				$saved_location->lng . '|' . $saved_location->address ) . '"' . $selected . '>' . esc_html( $escaped_name ) . '</option>';
		}
	}
	$saved_location_options = implode( '', $saved_location_options );

	$nonce = wp_create_nonce('geo-mashup-edit');

	$static_maps_base_url = 'http://maps.google.com/maps/api/staticmap?key=' .
		$geo_mashup_options->get( 'overall', 'googlev3_key' );
?>
	<div id="geo_mashup_location_editor">
	<div id="geo_mashup_ajax_message" class="geo-mashup-js ui-state-highlight"></div>
	<input id="geo_mashup_nonce" name="geo_mashup_nonce" type="hidden" value="<?php echo $nonce; ?>" />
	<input id="geo_mashup_changed" name="geo_mashup_changed" type="hidden" value="" />
	<?php ob_start(); ?>
	<table id="geo-mashup-location-table">
		<thead class="ui-widget-header">
		<tr>
			<th><?php _e( 'Address', 'GeoMashup' ); ?></th>
			<th><?php _e( 'Saved Name', 'GeoMashup' ); ?></th>
			<th><?php _e( 'Geo Date', 'GeoMashup' ); ?></th>
		</tr>
		</thead>
		<tbody class="ui-widget-content">
		<tr id="geo_mashup_display" class="geo-mashup-display-row">
			<td class="geo-mashup-info">
				<div class="geo-mashup-address"><?php echo esc_html( $location->address ); ?></div>
				<div class="geo-mashup-coordinates"><?php echo esc_attr( $coordinate_string ); ?></div>
			</td>
			<td id="geo_mashup_saved_name_ui">
				<input id="geo_mashup_location_name" name="geo_mashup_location_name" size="50" type="text" value="<?php echo esc_attr( $post_location_name ); ?>" />
			</td>
			<td id="geo_mashup_date_ui">
				<input id="geo_mashup_date" name="geo_mashup_date" type="text" size="20" value="<?php echo esc_attr( $location_date ); ?>" /><br />
				@
				<input id="geo_mashup_hour" name="geo_mashup_hour" type="text" size="2" maxlength="2" value="<?php echo esc_attr( $location_hour ); ?>" />
				:
				<input id="geo_mashup_minute" name="geo_mashup_minute" type="text" size="2" maxlength="2" value="<?php echo esc_attr( $location_minute ); ?>" />
			</td>
			<td id="geo_mashup_ajax_buttons">
			</td>

		</tr>
		</tbody>
	</table>
	<?php $location_table_html = ob_get_clean(); ?>
	<?php ob_start(); ?>
	<div id="geo_mashup_map" class="geo-mashup-js">
		<?php _e('Loading Google map. Check Geo Mashup options if the map fails to load.', 'GeoMashup'); ?>
	</div>
	<?php if ( ! empty( $location->id ) ) : ?>
	<noscript>
		<div id="geo_mashup_static_map">
			<img src="<?php echo $static_maps_base_url; ?>&amp;size=400x300&amp;zoom=4&amp;markers=size:small|color:green|<?php echo esc_attr( $location->lat . ',' . $location->lng ); ?>" 
				alt="<?php _e( 'Location Map Image', 'GeoMashup' ); ?>" />
		</div>
	</noscript>
	<?php endif; ?>
	<?php $map_html = ob_get_clean(); ?>
	<?php ob_start(); ?>
	<label for="geo_mashup_search"><?php _e('Find a new location:', 'GeoMashup'); ?>
	<input	id="geo_mashup_search" name="geo_mashup_search" type="text" size="35" />
	</label>

	<?php _e( 'or select from', 'GeoMashup' ); ?> 
	<select id="geo_mashup_select" name="geo_mashup_select"> 
		<option value=""><?php _e('[Saved Locations]','GeoMashup'); ?></option>
		<?php echo $saved_location_options; ?>
	</select>
	<?php $search_html = ob_get_clean(); ?>

	<?php echo empty( $location->id ) ? $search_html . $map_html . $location_table_html : $location_table_html . $map_html . $search_html; ?>

	<input id="geo_mashup_ui_manager" name="geo_mashup_ui_manager" type="hidden" value="<?php echo $ui_manager; ?>" />
	<input id="geo_mashup_object_id" name="geo_mashup_object_id" type="hidden" value="<?php echo $object_id; ?>" />
	<input id="geo_mashup_no_js" name="geo_mashup_no_js" type="hidden" value="true" />
	<input id="geo_mashup_location_id" name="geo_mashup_location_id" type="hidden" value="<?php echo esc_attr( $location->id ); ?>" />
	<input id="geo_mashup_location" name="geo_mashup_location" type="hidden" value="<?php echo esc_attr( $coordinate_string ); ?>" />
	<input id="geo_mashup_geoname" name="geo_mashup_geoname" type="hidden" value="<?php echo esc_attr( $location->geoname ); ?>" />
	<input id="geo_mashup_address" name="geo_mashup_address" type="hidden" value="<?php echo esc_attr( $location->address ); ?>" />
	<input id="geo_mashup_postal_code" name="geo_mashup_postal_code" type="hidden" value="<?php echo esc_attr( $location->postal_code ); ?>" />
	<input id="geo_mashup_country_code" name="geo_mashup_country_code" type="hidden" value="<?php echo esc_attr( $location->country_code ); ?>" />
	<input id="geo_mashup_admin_code" name="geo_mashup_admin_code" type="hidden" value="<?php echo esc_attr( $location->admin_code ); ?>" />
	<input id="geo_mashup_admin_name" name="geo_mashup_admin_name" type="hidden" value="" />
	<input id="geo_mashup_kml_url" name="geo_mashup_kml_url" type="hidden" value="<?php echo $kml_url; ?>" />
	<input id="geo_mashup_sub_admin_code" name="geo_mashup_sub_admin_code" type="hidden" value="<?php echo esc_attr( $location->sub_admin_code ); ?>" />
	<input id="geo_mashup_sub_admin_name" name="geo_mashup_sub_admin_name" type="hidden" value="" />
	<input id="geo_mashup_locality_name" name="geo_mashup_locality_name" type="hidden" value="<?php echo esc_attr( $location->locality_name ); ?>" />
	<div id="geo_mashup_submit" class="submit">
		<input id="geo_mashup_add_location" name="geo_mashup_add_location" type="submit" <?php echo $add_input_style; ?> value="<?php _e( 'Add Location', 'GeoMashup' ); ?>" />
		<input id="geo_mashup_delete_location" name="geo_mashup_delete_location" type="submit" <?php echo $delete_input_style; ?> value="<?php _e( 'Delete', 'GeoMashup' ); ?>" />
		<input id="geo_mashup_update_location" name="geo_mashup_update_location" type="submit" <?php echo $update_input_style; ?> value="<?php _e( 'Save', 'GeoMashup' ); ?>" />
	</div>
	<div id="geo-mashup-inline-help-link-wrap" class="geo-mashup-js">
		<a href="#geo-mashup-inline-help" id="geo-mashup-inline-help-link"><?php _e('help', 'GeoMashup'); ?><span class="ui-icon ui-icon-triangle-1-s"></span></a>
	</div>
	<div id="geo-mashup-inline-help" class="<?php echo $help_class; ?> ui-widget-content">
		<p><?php _e( '<em>Saved Name</em> is an optional name you may use to add entries to the Saved Locations menu.', 'GeoMashup' ); ?></p>
		<p><?php _e( '<em>Geo Date</em> associates a date (most formats work) and time with a location. Leave the default value if uncertain.', 'GeoMashup' ); ?></p>
		<div class="geo-mashup-js">
			<p><?php _e('Put a green pin at a new location. There are many ways to do it:', 'GeoMashup'); ?></p>
			<ul>
				<li><?php _e('Search for a location name.', 'GeoMashup'); ?></li>
				<li><?php _e('For multiple search results, mouse over pins to see location names, and click a result pin to select that location.', 'GeoMashup'); ?></li>
				<li><?php _e('Search for a decimal latitude and longitude separated by a comma, like <em>40.123,-105.456</em>. Seven decimal places are stored. Negative latitude is used for the southern hemisphere, and negative longitude for the western hemisphere.', 'GeoMashup'); ?></li> 
				<li><?php _e('Search for a street address, like <em>123 main st, anytown, acity</em>.', 'GeoMashup'); ?></li>
				<li><?php _e('Click on the location. Zoom in if necessary so you can refine the location by dragging it or clicking a new location.', 'GeoMashup'); ?></li>
			</ul>
			<p><?php _e('To execute a search, type search text into the Find Location box and hit the enter key. If you type a name next to "Save As", the location will be saved under that name and added to the Saved Locations dropdown list.', 'GeoMashup'); ?></p>
			<p><?php _e('To remove the location (green pin), clear the search box and hit the enter key.', 'GeoMashup'); ?></p>
			<p><?php _e('When you are satisfied with the location, save or update.', 'GeoMashup'); ?></p>
		</div>
		<noscript>
			<div>
				<p><?php _e( 'To add or update location choose a saved location, or find a new location using one of these formats:', 'GeoMashup' ); ?></p>
				<ul>
					<li><?php _e('A place name like <em>Yellowstone National Park</em>', 'GeoMashup'); ?></li>
					<li><?php _e('A decimal latitude and longitude, like <em>40.123,-105.456</em>.', 'GeoMashup'); ?></li> 
					<li><?php _e('A full or partial street address, like <em>123 main st, anytown, acity 12345 USA</em>.', 'GeoMashup'); ?></li>
				</ul>
				<p><?php _e( 'When you save or update, the closest match available will be saved as the location.', 'GeoMashup' ); ?></p>
			</div>
		</noscript>

	</div>
	</div><!-- id="geo_mashup_location_editor" -->
<?php
}
?>
