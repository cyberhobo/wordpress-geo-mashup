<?php
/**
 * Default Geo Mashup Search nearby posts template.
 *
 * THIS FILE WILL BE OVERWRITTEN BY AUTOMATIC UPGRADES
 * See the geo-mashup-search.php plugin file for license
 *
 * Copy this to a file named geo-mashup-nearby-list.php in your active theme folder
 * to customize. For bonus points delete this message in the copy!
 *
 * Variables in scope:
 * $geo_mashup_search  object   The managing search object
 * $search_text        string   The search text entered in the form
 * $radius             int      The search radius
 * $units              string   'mi' or 'km'
 * $object_name        string   'post' or 'user' or 'comment'
 * $near_location      array    The location searched, including 'lat' and 'lng'
 * $distance_factor    float    The multiplier to convert the radius to kilometers
 * $approximate_zoom   int      A guess at a zoom level that will include all results
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
<?php if ( $geo_mashup_search->have_posts() ) : ?>

	<aside>
	<?php if ($object_name == 'post'): ?>
		<h1><?php _e( 'Posts Nearby', 'GeoMashup' ); ?></h1>
	<?php elseif ($object_name == 'user'):?>
		<h1><?php _e( 'Users Nearby', 'GeoMashup' ); ?></h1>
	<?php endif; ?>
	<ul class="geo-mashup-nearby-posts">

		<?php if ($object_name == 'post'):?>

			<?php while ( $geo_mashup_search->have_posts() ) : $geo_mashup_search->the_post(); ?>
				<li>
					<a href="<?php the_permalink(); ?>" title=""><?php the_title(); ?></a> 
					<span class="distance"><?php $geo_mashup_search->the_distance(); ?></span>
				</li>
			<?php endwhile; ?>

		<?php elseif ($object_name == 'user'):?>

			<?php while ( $geo_mashup_search->have_posts() ) : $user=$geo_mashup_search->get_userdata(); ?>
				<li>
					<?php echo $user->first_name.' '.$user->last_name;?> aka <?php echo $user->user_nicename?>
					<span class="distance"><?php $geo_mashup_search->the_distance(); ?></span>
				</li>
			<?php endwhile; ?>		

		<?php endif; ?>		

	</ul>
	</aside>

<?php endif; ?>
