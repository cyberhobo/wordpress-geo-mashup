<?php
function geo_mashup_edit_form() {
	global $post_ID;

	list($post_lat,$post_lng) = split(',',get_post_meta($post_ID,'_geo_location',true));
	$post_location_name = '';
	$kml_url = '';
	$kml_urls = GeoMashup::get_kml_attachment_urls($post_ID);
	if (count($kml_urls)>0) {
		$kml_url = array_pop($kml_urls);
	}
	$geo_locations = get_settings('geo_locations');
	$locations_json = '{';
	if (is_array($geo_locations)) {
		$comma = '';
		foreach ($geo_locations as $name => $latlng) {
			list($lat,$lng) = split(',',$latlng);
			$escaped_name = addslashes(str_replace(array("\r\n","\r","\n"),'',$name));
			if ($lat==$post_lat && $lng==$post_lng) {
				$post_location_name = $escaped_name;
			}
			$locations_json .= $comma.'"'.addslashes($name).'":{"name":"'.$escaped_name.'","lat":"'.$lat.'","lng":"'.$lng.'"}';
			$comma = ',';
		}
	}
	$locations_json .= '}';
	$nonce = wp_create_nonce('geo-mashup-edit-post');
?>
	<input id="geo_mashup_edit_nonce" name="geo_mashup_edit_nonce" type="hidden" value="<?php echo $nonce; ?>" />
	<img id="geo_mashup_status_icon" src="<?php echo GEO_MASHUP_URL_PATH; ?>/images/idle_icon.gif" style="float:right" />
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
	<a href="#" onclick="document.getElementById(\'geo_mashup_inline_help\').style.display=\'block\'; return false;"><?php __('help', 'GeoMashup'); ?></a>
	<div id="geo_mashup_inline_help" style="padding:5px; border:2px solid blue; background-color:#ffc; display:none;">
		<p><?php _e('Put a green pin at the location for this post. There are many ways to do it:', 'GeoMashup'); ?>
		<ul>
			<li><?php _e('Search for a location name.', 'GeoMashup'); ?></li>
			<li><?php _e('For multiple search results, mouse over pins to see location names, and click a result pin to select that location.', 'GeoMashup'); ?></li>
			<li><?php _e('Search for a decimal latitude and longitude, like <em>40.123,-105.456</em>.', 'GeoMashup'); ?></li> 
			<li><?php _e('Search for a street address, like <em>123 main st, anytown, acity</em>.', 'GeoMashup'); ?></li>
			<li><?php _e('Click on the location. Zoom in if necessary so you can refine the location by dragging it or clicking a new location.', 'GeoMashup'); ?></li>
		</ul>
		<?php _e('To execute a search, type search text into the Find Location box and hit the enter key. If you type a name next to "Save As", the location will be saved under that name so you can find it again with a quick search. Saved names are searched before doing a GeoNames search for location names.', 'GeoMashup'); ?></p>
		<p><?php _e('To remove the location (green pin) for a post, clear the search box and hit the enter key.', 'GeoMashup'); ?></p>
		<p><a href="#" onclick="document.getElementById(\'geo_mashup_inline_help\').style.display=\'none\'; return false;"><?php _e('close', 'GeoMashup'); ?></a>
	</div>
	<div id="geo_mashup_map" style="width:400px;height:300px;">
		<?php _e('Loading Google map. Check Geo Mashup options if the map fails to load.', 'GeoMashup'); ?>
	</div>
	<script type="text/javascript">//<![CDATA[
		GeoMashupAdmin.registerMap(document.getElementById("geo_mashup_map"),
			{"link_url":"<?php echo GEO_MASHUP_URL_PATH; ?>",
			"post_lat":"<?php echo $post_lat; ?>",
			"post_lng":"<?php echo $post_lng; ?>",
			"post_location_name":"<?php echo $post_location_name; ?>",
			"saved_locations":<?php echo $locations_json; ?>,
			"kml_url":"<?php echo $kml_url; ?>",
			"status_icon":document.getElementById("geo_mashup_status_icon")});
	// ]]>
	</script>
	<label for="geo_mashup_location_name"><?php _e('Save As:', 'GeoMashup'); ?>
		<input id="geo_mashup_location_name" name="geo_mashup_location_name" type="text" size="45" />
	</label>
	<input id="geo_mashup_location" name="geo_mashup_location" type="hidden" value="<?php echo $post_lat.','.$post_lng; ?>" />
<?php
}
?>
