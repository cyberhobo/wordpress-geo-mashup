<?php
function geo_mashup_edit_form() {
	global $post_ID;

	$location = GeoMashupDB::get_post_location( $post_ID );
	$location = ( empty( $location ) ) ? GeoMashupDB::blank_location( ) : $location;
	$post_location_name = $location->saved_name;
	$kml_url = '';
	$kml_urls = GeoMashup::get_kml_attachment_urls($post_ID);
	if (count($kml_urls)>0) {
		$kml_url = array_pop($kml_urls);
	}
	$saved_locations = GeoMashupDB::get_saved_locations( );
	$locations_json = '{';
	if ( !empty( $saved_locations ) ) {
		$comma = '';
		foreach ($saved_locations as $saved_location) {
			$escaped_name = addslashes(str_replace(array("\r\n","\r","\n"),'',$saved_location->saved_name));
			$locations_json .= $comma.'"'.$escaped_name.'":{"location_id":"'.$saved_location->id.'","name":"'.$escaped_name.
				'","lat":"'.$saved_location->lat.'","lng":"'.$saved_location->lng.'"}';
			$comma = ',';
		}
	}
	$locations_json .= '}';
	$nonce = wp_create_nonce('geo-mashup-edit-post');
?>
	<div id="geo-mashup-inline-help" class="hidden">
		<p><?php _e('Put a green pin at the location for this post. There are many ways to do it:', 'GeoMashup'); ?></p>
		<ul>
			<li><?php _e('Search for a location name.', 'GeoMashup'); ?></li>
			<li><?php _e('For multiple search results, mouse over pins to see location names, and click a result pin to select that location.', 'GeoMashup'); ?></li>
			<li><?php _e('Search for a decimal latitude and longitude, like <em>40.123,-105.456</em>.', 'GeoMashup'); ?></li> 
			<li><?php _e('Search for a street address, like <em>123 main st, anytown, acity</em>.', 'GeoMashup'); ?></li>
			<li><?php _e('Click on the location. Zoom in if necessary so you can refine the location by dragging it or clicking a new location.', 'GeoMashup'); ?></li>
		</ul>
		<p><?php _e('To execute a search, type search text into the Find Location box and hit the enter key. If you type a name next to "Save As", the location will be saved under that name and added to the Saved Locations dropdown list.', 'GeoMashup'); ?></p>
		<p><?php _e('To remove the location (green pin) for a post, clear the search box and hit the enter key.', 'GeoMashup'); ?></p>
		<p><?php _e('When you are satisfied with the location, save or update the post.', 'GeoMashup'); ?></p>
	</div>
	<input id="geo_mashup_edit_nonce" name="geo_mashup_edit_nonce" type="hidden" value="<?php echo $nonce; ?>" />
	<img id="geo_mashup_status_icon" src="<?php echo GEO_MASHUP_URL_PATH; ?>/images/idle_icon.gif" />
	<div id="geo-mashup-inline-help-link-wrap" class="hide-if-no-js">
		<a href="#geo-mashup-inline-help" id="geo-mashup-inline-help-link"><?php _e('help', 'GeoMashup'); ?></a>
	</div>
	<label for="geo_mashup_search"><?php _e('Find location:', 'GeoMashup'); ?>
	<input	id="geo_mashup_search" 
		name="geo_mashup_search" 
		type="text" 
		size="35" 
		onfocus="this.select(); GeoMashupAdmin.map.checkResize();"
		onkeypress="return GeoMashupAdmin.searchKey(event, this.value)" />
	</label>
	<select id="geo_mashup_select" name="geo_mashup_select" onchange="GeoMashupAdmin.onSelectChange(this);">
		<option><?php _e('[Saved Locations]','GeoMashup'); ?></option>
	</select>
	<div id="geo_mashup_map" style="width:400px;height:300px;">
		<?php _e('Loading Google map. Check Geo Mashup options if the map fails to load.', 'GeoMashup'); ?>
	</div>
	<script type="text/javascript">//<![CDATA[
		GeoMashupAdmin.registerMap(document.getElementById("geo_mashup_map"),
			{"link_url":"<?php echo GEO_MASHUP_URL_PATH; ?>",
			"post_lat":"<?php echo $location->lat; ?>",
			"post_lng":"<?php echo $location->lng; ?>",
			"post_location_name":"<?php echo $post_location_name; ?>",
			"saved_locations":<?php echo $locations_json; ?>,
			"kml_url":"<?php echo $kml_url; ?>",
			"status_icon":document.getElementById("geo_mashup_status_icon")});
	// ]]>
	</script>
	<label for="geo_mashup_location_name"><?php _e('Save As:', 'GeoMashup'); ?>
		<input id="geo_mashup_location_name" name="geo_mashup_location_name" type="text" maxlength="50" size="45" />
	</label>
	<input id="geo_mashup_location" name="geo_mashup_location" type="hidden" value="<?php echo $location->lat.','.$location->lng; ?>" />
	<input id="geo_mashup_location_id" name="geo_mashup_location_id" type="hidden" value="<?php echo $location->id; ?>" />
	<input id="geo_mashup_geoname" name="geo_mashup_geoname" type="hidden" value="<?php echo $location->geoname; ?>" />
	<input id="geo_mashup_address" name="geo_mashup_address" type="hidden" value="<?php echo $location->address; ?>" />
	<input id="geo_mashup_postal_code" name="geo_mashup_postal_code" type="hidden" value="<?php echo $location->postal_code; ?>" />
	<input id="geo_mashup_country_code" name="geo_mashup_country_code" type="hidden" value="<?php echo $location->country_code; ?>" />
	<input id="geo_mashup_admin_code" name="geo_mashup_admin_code" type="hidden" value="<?php echo $location->admin_code; ?>" />
	<input id="geo_mashup_admin_name" name="geo_mashup_admin_name" type="hidden" value="" />
	<input id="geo_mashup_sub_admin_code" name="geo_mashup_sub_admin_code" type="hidden" value="<? echo $location->sub_admin_code; ?>" />
	<input id="geo_mashup_sub_admin_name" name="geo_mashup_sub_admin_name" type="hidden" value="" />
	<input id="geo_mashup_locality_name" name="geo_mashup_locality_name" type="hidden" value="<? echo $location->locality_name; ?>" />
	<input id="geo_mashup_changed" name="geo_mashup_changed" type="hidden" value="" />
<?php
}
?>
