<?php
/**
 * Default Geo Mashup Search results template.
 *
 * THIS FILE WILL BE OVERWRITTEN BY AUTOMATIC UPGRADES
 * See the geo-mashup-search.php plugin file for license
 *
 * Copy this to a file named geo-mashup-search-results.php in your active theme folder
 * to customize. For bonus points delete this message in the copy!
 *
 * Variables in scope:
 * @var $geo_mashup_search  object   The managing search object
 * @var $result_count       int      The number of objects found
 * @var $search_text        string   The search text entered in the form
 * @var $radius             int      The search radius
 * @var $units              string   'mi' or 'km'
 * @var $object_name        string   'post' or 'user' or 'comment'
 * @var $near_location      array    The location searched, including 'lat' and 'lng'
 * @var $distance_factor    float    The multiplier to convert the radius to kilometers
 * @var $approximate_zoom   int      A guess at a zoom level that will include all results
 *
 * Methods of $geo_mashup_search mimic WordPress Loop functions have_posts()
 * and the_post() (see http://codex.wordpress.org/The_Loop). This makes post
 * template functions like the_title() work as expected. For distance:
 *
 * $geo_mashup_search->the_distance();
 *
 * will echo the distance with units. Its output can be modified:
 *
 * $geo_mashup_search->the_distance( 'decimal_places=1&append_units=0&echo=0' );
 */
?>
<div id="geo-mashup-search-results">

	<h2><?php printf( __( 'Search results near "%s"', 'GeoMashup' ), esc_html( $search_text ) ); ?></h2>
	
	<?php if ( $geo_mashup_search->have_posts() ) : ?>

	<?php echo GeoMashup::map( array(
		'name' => 'search-results-map',
		'search_text' => $search_text,
		'object_ids' => $geo_mashup_search->get_the_ID_list(),
		'center_lat' => $near_location['lat'],
		'center_lng' => $near_location['lng'],
		'search_lat' => $near_location['lat'],
		'search_lng' => $near_location['lng'],
		'map_content' => 'global',
		'object_name'=> $object_name,
		'zoom' => 	$approximate_zoom + 1 // Adjust to taste
		) ); ?>

		<?php if ($object_name == 'post'):?>

			<?php while ( $geo_mashup_search->have_posts() ) : $geo_mashup_search->the_post(); ?>
					<div class="search-result">
						<h3><a href="<?php the_permalink(); ?>" title=""><?php the_title(); ?></a></h3>
						<p><?php the_excerpt(); ?></p>
						<p>
					<?php _e( 'Distance', 'GeoMashup' ); ?>:
					<?php $geo_mashup_search->the_distance(); ?>
				</p>
			</div>
			<?php endwhile; ?>
		<?php elseif ($object_name == 'user'):?>
			
			<?php while ( $geo_mashup_search->have_posts() ) : $user=$geo_mashup_search->get_userdata(); ?>
					<div class="search-result">
						<h3><?php echo $user->first_name.' '.$user->last_name;?> aka <?php echo $user->user_nicename?></h3>
						<p>
					<?php _e( 'Distance', 'GeoMashup' ); ?>:
					<?php $geo_mashup_search->the_distance(); ?>
				</p>
			</div>
			<?php endwhile; ?>		
		<?php endif;?>
	<?php else : ?>

				<p><?php _e( 'No results found.', 'GeoMashup' ); ?></p>

	<?php endif; ?>
</div>