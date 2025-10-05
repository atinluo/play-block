<?php
/**
 * The template for displaying all pages
 *
 * @package School_Management_Theme
 */

get_header();
?>

<?php while ( have_posts() ) : the_post(); ?>
	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
		<h1 class="entry-title"><?php the_title(); ?></h1>
		<div class="entry-content">
			<?php the_content(); ?>
		</div>
	</article>
<?php endwhile; ?>

<?php
get_footer();
