<?php
/**
 * This is the default template for the info window in Geo Mashup maps. 
 *
 * Don't modify this file! It will be overwritten by upgrades.
 *
 * Instead, copy this file to "info-window.php" in this directory,
 * or "geo-mashup-info-window.php" in your theme directory. Those files will
 * take precedence over this one.
 *
 * For styling of the info window, see map-style-default.css.
 */
?>
<div class="locationinfo">
<?php if (have_posts()) : ?>

	<?php while (have_posts()) : the_post(); ?>

		<h2><a href="<?php the_permalink() ?>" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
		<?php if ($wp_query->post_count == 1) : ?>
			<p class="meta"><span class="blogdate"><?php the_time('F jS, Y') ?></span> <?php the_category( ', ' ) ?></p>

			<div class="storycontent">
				<?php the_excerpt(); ?>
			</div>
		<?php endif; ?>

	<?php endwhile; ?>

<?php else : ?>

	<h2 class="center">Not Found</h2>
	<p class="center">Sorry, but you are looking for something that isn't here.</p>

<?php endif; ?>

</div>
