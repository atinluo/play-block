<?php
/**
 * Plugin compatibility helpers.
 *
 * @package School_Management_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render primary navigation with fallback.
 */
function school_mgmt_primary_nav() : void {
	if ( has_nav_menu( 'primary' ) ) {
		wp_nav_menu( [
			'theme_location' => 'primary',
			'menu_class'    => 'nav',
			'container'     => false,
			'depth'         => 2,
		] );
	} else {
		echo '<nav class="nav">';
		wp_list_pages( [ 'title_li' => '' ] );
		echo '</nav>';
	}
}

/**
 * Output site header branding.
 */
function school_mgmt_site_branding() : void {
	echo '<div class="site-branding">';
	if ( has_custom_logo() ) {
		echo get_custom_logo();
	}
	echo '<div class="site-title"><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html( get_bloginfo( 'name' ) ) . '</a></div>';
	$description = get_bloginfo( 'description', 'display' );
	if ( $description ) {
		echo '<div class="site-description">' . esc_html( $description ) . '</div>';
	}
	echo '</div>';
}

/**
 * Determine if current request should be full width (useful for plugin pages)
 */
function school_mgmt_is_fullwidth() : bool {
	return function_exists( 'wlsm_is_plugin_page' ) && wlsm_is_plugin_page();
}
