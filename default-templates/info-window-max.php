<?php
/**
 * This is the default template for the maximized info window display of a clicked marker
 * in a Geo Mashup map. 
 *
 * Don't modify this file! It will be overwritten by upgrades.
 *
 * Instead, copy this file to "info-window-max.php" in your geo-mashup-custom directory,
 * or "geo-mashup-info-window-max.php" in your theme directory. Those files will
 * take precedence over this one.
 *
 * @package GeoMashup
 */

// Avoid nested maps
add_filter( 'the_content', array( 'GeoMashupQuery', 'strip_map_shortcodes' ), 1, 9 );
?>
<div class="info-window-max">
<?php if (have_posts()) : ?>

	<?php while (have_posts()) : the_post(); ?>

		<h2><a href="<?php the_permalink() ?>" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
		<p class="meta"><span class="blogdate"><?php the_time('F jS, Y') ?></span> <?php the_category( ', ' ) ?></p>
		<?php if ( function_exists( 'has_post_thumbnail') and has_post_thumbnail() ) : ?>
		<?php the_post_thumbnail(); ?>
		<?php endif; ?>

		<div class="storycontent">
			<?php the_content(); ?>
		</div>

	<?php endwhile; ?>

<?php else : ?>

	<h2 class="center">Not Found</h2>
	<p class="center">Sorry, but you are looking for something that isn't here.</p>

<?php endif; ?>
</div>
