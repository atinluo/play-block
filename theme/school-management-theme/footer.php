<?php
/**
 * The template for displaying the footer
 *
 * @package School_Management_Theme
 */
?>
</main>
<footer class="site-footer">
	<div class="container">
		<div class="footer-widgets">
			<?php if ( is_active_sidebar( 'footer-1' ) ) : ?>
				<?php dynamic_sidebar( 'footer-1' ); ?>
			<?php endif; ?>
		</div>
		<nav class="nav footer-nav">
			<?php
			wp_nav_menu( [
				'theme_location' => 'footer',
				'menu_class'    => 'nav',
				'container'     => false,
			] );
			?>
		</nav>
		<p>&copy; <?php echo date_i18n( 'Y' ); ?> <?php bloginfo( 'name' ); ?></p>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
