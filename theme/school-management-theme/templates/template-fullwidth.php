<?php
/**
 * Template Name: Full Width (S.M.S Compatible)
 * Description: Full width page template designed for School Management System plugin shortcodes/pages.
 *
 * @package School_Management_Theme
 */

get_header();
?>

<div class="sms-wrapper">
	<?php while ( have_posts() ) : the_post(); ?>
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<h1 class="screen-reader-text"><?php the_title(); ?></h1>
			<div class="entry-content">
				<?php the_content(); ?>
			</div>
		</article>
	<?php endwhile; ?>
</div>

<?php
get_footer();
