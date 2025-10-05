<?php
/**
 * Theme functions and definitions
 *
 * @package School_Management_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Theme version.
define( 'SCHOOL_MGMT_THEME_VERSION', '0.1.0' );

define( 'SCHOOL_MGMT_THEME_DIR', get_stylesheet_directory() );

define( 'SCHOOL_MGMT_THEME_URI', get_stylesheet_directory_uri() );

// Setup theme supports and menus.
add_action( 'after_setup_theme', function () {
	// Make theme available for translation.
	load_theme_textdomain( 'school-management-theme', SCHOOL_MGMT_THEME_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'custom-logo', [
		'height'      => 64,
		'width'       => 64,
		'flex-height' => true,
		'flex-width'  => true,
	] );
	add_theme_support( 'align-wide' );
	add_theme_support( 'html5', [ 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ] );

	register_nav_menus( [
		'primary' => __( 'Primary Menu', 'school-management-theme' ),
		'footer'  => __( 'Footer Menu', 'school-management-theme' ),
	] );
} );

// Enqueue styles and scripts.
add_action( 'wp_enqueue_scripts', function () {
	$version = wp_get_environment_type() === 'production' ? SCHOOL_MGMT_THEME_VERSION : time();

	wp_enqueue_style( 'school-mgmt-theme', SCHOOL_MGMT_THEME_URI . '/style.css', [], $version );

	// Optional: place for plugin-specific CSS tweaks to avoid conflicts.
	wp_enqueue_style( 'school-mgmt-plugin-tweaks', SCHOOL_MGMT_THEME_URI . '/assets/css/plugin-tweaks.css', [ 'school-mgmt-theme' ], $version );

	wp_enqueue_script( 'school-mgmt-theme', SCHOOL_MGMT_THEME_URI . '/assets/js/theme.js', [ 'jquery' ], $version, true );
} );

// Editor styles
add_action( 'after_setup_theme', function() {
	add_editor_style( [ 'style.css', 'assets/css/plugin-tweaks.css' ] );
} );

// Widgets
add_action( 'widgets_init', function () {
	register_sidebar( [
		'name'          => __( 'Footer 1', 'school-management-theme' ),
		'id'            => 'footer-1',
		'description'   => __( 'Add widgets here to appear in your footer.', 'school-management-theme' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	] );
} );

// Content width
if ( ! isset( $content_width ) ) {
	$content_width = 1100;
}

// Add body class for plugin pages to allow targeted styling.
add_filter( 'body_class', function ( array $classes ) : array {
	if ( function_exists( 'wlsm_is_plugin_page' ) && wlsm_is_plugin_page() ) {
		$classes[] = 'is-sms-page';
	}
	return $classes;
} );

// Fallback helper if plugin function not available.
if ( ! function_exists( 'wlsm_is_plugin_page' ) ) {
	function wlsm_is_plugin_page() : bool {
		// Basic heuristic based on known plugin slugs.
		return is_page( [ 'admissions', 'students', 'teachers', 'classes', 'exams', 'fees', 'attendance', 'transport', 'hostel', 'library', 'noticeboard', 'homework', 'study-material', 'accounts', 'activities', 'routes', 'subjects', 'sections' ] );
	}
}

// Include template tags or compatibility functions.
require_once SCHOOL_MGMT_THEME_DIR . '/inc/compat.php';
