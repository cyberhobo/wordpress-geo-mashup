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
 * $categories array       Terms objects to include in the category menu, if any.
 * $radii      array       Radius distances to include in the radius menu, if any.
 */
?>
<form class="geo-mashup-search-form" method="post" action="<?php echo $action_url; ?>">
	<input name="results_page_id" type="hidden" value="<?php echo esc_attr( $instance['results_page_id'] ); ?>" />
	<input name="units" type="hidden" value="<?php echo esc_attr( $instance['units'] ); ?>" />
	<div class="Location clear">
		<label for="<?php echo $widget_id; ?>-input"><?php _e('Location','GeoMashup' ) ?>:</label>
		<input id="<?php echo $widget_id; ?>-input" class="geo-mashup-search-input" name="location_text" type="search" 
			placeholder="<?php if ( !empty( $_POST['location_text'] ) ) { echo esc_attr( $_POST['location_text'] ); } else if ( !empty( $instance['default_search_text'] ) ) {echo esc_attr( $instance['default_search_text'] );} ?>"
			value=""/>
	</div>
	<div class="object clear">
	<?php 
	if($instance['object_name'] === 'user' ||  $instance['object_name'] === 'comment') : ?>
		<input name="object_name" type="hidden" value="<?php echo esc_attr( $instance['object_name'] ); ?>" />
	</div><!-- .object -->
	<?php else : ?>
		<input name="object_name" type="hidden" value="post" />
		<input name="map_post_type" type="hidden" value="<?php echo esc_attr( $instance['object_name'] ); ?>" />
		</div><!-- .object -->
		
		<?php if ( !empty( $categories ) ) : ?>
			<?php if ( $categories === 'all' ) : ?>
			<div class="taxonomy clear">	
				<input name="taxonomy" type="hidden" value="<?php echo $widget->get_default_value($instance, 'taxonomy', 'category');	?>" />
				<input name="map_terms" type="hidden" value="<?php echo $categories; ?>" />
			</div><!-- .taxonomy -->
			<?php else: // $taxonomy_terms ?>
			<div class="taxonomy clear">
				<input name="taxonomy" type="hidden" value="<?php echo $widget->get_default_value($instance, 'taxonomy', 'category');	?>" />
				<label for="<?php echo $widget_id; ?>-categories"><?php _e( 'Category', 'GeoMashup' ); ?>:</label>
					<select id="<?php echo $widget_id; ?>-categories" name="map_terms">
					<?php foreach ( $categories as $term ) : ?>
						<option value="<?php echo $term->term_id; ?>"<?php
							if ( $widget->get_default_value( $_POST, 'map_terms' ) == $term->term_id ) echo ' selected="selected"';	?>>
							<?php echo $term->name; ?>
						</option>
					<?php endforeach; ?>
					</select>
			</div><!-- .taxonomy -->		
			<?php endif; ?>
		<?php endif; // $taxonomy_terms ?>
		
	<?php endif; ?>
	<?php if ( !empty( $radii ) ) : ?>
	<div class="radius clear">	
		<label for="<?php echo $widget_id; ?>-radius"><?php echo ucfirst(__( 'within', 'GeoMashup' )); ?>:</label>
		<select id="<?php echo $widget_id; ?>-radius" name="radius">
		<?php foreach ( $radii as $radius ) : ?>
			<option value="<?php echo $radius; ?>"<?php
				if ( $widget->get_default_value( $_POST, 'radius' ) == $radius )
						echo ' selected="selected"';
			?>><?php echo $radius; ?> <?php echo esc_html( $instance['units'] ); ?></option>
		<?php endforeach; ?>
		</select>		
	</div>
	<?php endif; // Radius ?>
	<div class="submit">
		<input id="<?php echo $widget_id; ?>-submit" name="geo_mashup_search_submit" type="submit" value="<?php _e( 'Search', 'GeoMashup' ); ?>" />
		<?php if ( !empty( $instance['find_me_button'] ) ) : ?>
			<input name="geolocation" type="hidden" value="" />
			<button id="<?php echo $widget_id; ?>-find-me" class="geo-mashup-search-find-me" style="display:none;"><?php echo $instance['find_me_button']; ?></button>
		<?php endif; ?>
	</div>
</form>