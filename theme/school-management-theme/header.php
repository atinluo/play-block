<?php
/**
 * The header for our theme
 *
 * @package School_Management_Theme
 */
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="site-header">
	<div class="container">
		<?php school_mgmt_site_branding(); ?>
		<?php school_mgmt_primary_nav(); ?>
	</div>
</header>
<main id="primary" class="site-main container<?php echo school_mgmt_is_fullwidth() ? ' alignwide' : ''; ?>">
