<?php
/**
 * Plugin Name: Chatbot RAG Assistant
 * Description: Floating chatbot that indexes your sitemap and answers questions using your site content.
 * Version: 0.1.0
 * Author: You
 * License: GPLv2 or later
 * Text Domain: wp-chatbot-rag
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WP_CHATBOT_RAG_VERSION' ) ) {
	define( 'WP_CHATBOT_RAG_VERSION', '0.1.0' );
}

if ( ! defined( 'WP_CHATBOT_RAG_PATH' ) ) {
	define( 'WP_CHATBOT_RAG_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'WP_CHATBOT_RAG_URL' ) ) {
	define( 'WP_CHATBOT_RAG_URL', plugin_dir_url( __FILE__ ) );
}

require_once WP_CHATBOT_RAG_PATH . 'includes/class-wp-chatbot-rag.php';

function wp_chatbot_rag() {
	return WP_Chatbot_RAG::instance();
}

register_activation_hook( __FILE__, [ 'WP_Chatbot_RAG', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'WP_Chatbot_RAG', 'deactivate' ] );

wp_chatbot_rag();
