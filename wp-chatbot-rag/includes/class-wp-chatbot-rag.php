<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_Chatbot_RAG {
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', [ $this, 'init' ] );
		add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_post_wpcr_crawl', [ $this, 'handle_admin_crawl' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_public_assets' ] );
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	public static function activate() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$chunks_table = $wpdb->prefix . 'wpcr_chunks';

		$sql = "CREATE TABLE {$chunks_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			url TEXT NOT NULL,
			title TEXT NULL,
			content LONGTEXT NOT NULL,
			content_hash VARCHAR(64) NOT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY content_hash (content_hash(10))
		) {$charset_collate};";

		dbDelta( $sql );
	}

	public static function deactivate() {
		// Nothing for now; keep data unless uninstalled.
	}

	public function init() {
		// Localization
		load_plugin_textdomain( 'wp-chatbot-rag', false, dirname( plugin_basename( WP_CHATBOT_RAG_PATH . 'wp-chatbot-rag.php' ) ) . '/languages' );
	}

	public function enqueue_public_assets() {
		wp_register_style( 'wpcr-chatbot', WP_CHATBOT_RAG_URL . 'assets/css/chatbot.css', [], WP_CHATBOT_RAG_VERSION );
		wp_register_script( 'wpcr-chatbot', WP_CHATBOT_RAG_URL . 'assets/js/chatbot.js', [ 'wp-api-fetch' ], WP_CHATBOT_RAG_VERSION, true );

		wp_enqueue_style( 'wpcr-chatbot' );
		wp_enqueue_script( 'wpcr-chatbot' );

		wp_localize_script( 'wpcr-chatbot', 'WPCR', [
			'root'   => esc_url_raw( rest_url( 'wp-chatbot-rag/v1' ) ),
			'nonce'  => wp_create_nonce( 'wp_rest' ),
			'site'   => get_bloginfo( 'name' ),
			'avatar' => get_avatar_url( get_current_user_id() ),
		] );
	}

	public function register_admin_menu() {
		add_menu_page(
			__( 'Chatbot RAG', 'wp-chatbot-rag' ),
			__( 'Chatbot RAG', 'wp-chatbot-rag' ),
			'manage_options',
			'wpcr-settings',
			[ $this, 'render_settings_page' ],
			'dashicons-format-chat'
		);
	}

	public function register_settings() {
		register_setting( 'wpcr_settings', 'wpcr_openai_api_key', [ 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'wpcr_settings', 'wpcr_sitemap_url', [ 'type' => 'string', 'sanitize_callback' => 'esc_url_raw' ] );
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$api_key = get_option( 'wpcr_openai_api_key', '' );
		$sitemap = get_option( 'wpcr_sitemap_url', home_url( '/sitemap.xml' ) );
		$action_url = admin_url( 'admin-post.php' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Chatbot RAG Assistant', 'wp-chatbot-rag' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'wpcr_settings' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="wpcr_openai_api_key"><?php esc_html_e( 'OpenAI API Key (optional)', 'wp-chatbot-rag' ); ?></label></th>
						<td><input type="password" id="wpcr_openai_api_key" name="wpcr_openai_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="wpcr_sitemap_url"><?php esc_html_e( 'Sitemap URL', 'wp-chatbot-rag' ); ?></label></th>
						<td><input type="url" id="wpcr_sitemap_url" name="wpcr_sitemap_url" value="<?php echo esc_attr( $sitemap ); ?>" class="regular-text" required /></td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>

			<hr />

			<form method="post" action="<?php echo esc_url( $action_url ); ?>">
				<?php wp_nonce_field( 'wpcr_crawl' ); ?>
				<input type="hidden" name="action" value="wpcr_crawl" />
				<?php submit_button( __( 'Crawl Sitemap Now', 'wp-chatbot-rag' ), 'primary' ); ?>
			</form>
		</div>
		<?php
	}

	public function handle_admin_crawl() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Unauthorized', 'wp-chatbot-rag' ) );
		}
		check_admin_referer( 'wpcr_crawl' );

		$this->crawl_sitemap();

		wp_safe_redirect( add_query_arg( [ 'wpcr_crawled' => '1' ], admin_url( 'admin.php?page=wpcr-settings' ) ) );
		exit;
	}

	private function crawl_sitemap() {
		$sitemap_url = get_option( 'wpcr_sitemap_url', home_url( '/sitemap.xml' ) );
		$response = wp_remote_get( $sitemap_url, [ 'timeout' => 20 ] );
		if ( is_wp_error( $response ) ) {
			return;
		}
		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			return;
		}

		$urls = $this->extract_urls_from_sitemap( $body );
		if ( empty( $urls ) ) {
			return;
		}
		foreach ( $urls as $url ) {
			$this->index_url( $url );
		}
	}

	private function extract_urls_from_sitemap( $xml_string ) {
		$urls = [];
		libxml_use_internal_errors( true );
		$xml = simplexml_load_string( $xml_string );
		if ( ! $xml ) {
			return $urls;
		}
		$xml->registerXPathNamespace( 'sm', 'http://www.sitemaps.org/schemas/sitemap/0.9' );

		// If it's a sitemap index
		$index_entries = $xml->xpath( '//sm:sitemap/sm:loc' );
		if ( ! empty( $index_entries ) ) {
			foreach ( $index_entries as $loc ) {
				$child_resp = wp_remote_get( (string) $loc );
				if ( ! is_wp_error( $child_resp ) ) {
					$child_body = wp_remote_retrieve_body( $child_resp );
					$urls = array_merge( $urls, $this->extract_urls_from_sitemap( $child_body ) );
				}
			}
			return array_values( array_unique( $urls ) );
		}

		// Regular urlset
		$url_entries = $xml->xpath( '//sm:url/sm:loc' );
		foreach ( $url_entries as $loc ) {
			$urls[] = (string) $loc;
		}
		return array_values( array_unique( $urls ) );
	}

	private function index_url( $url ) {
		$response = wp_remote_get( $url, [ 'timeout' => 20 ] );
		if ( is_wp_error( $response ) ) {
			return;
		}
		$html = wp_remote_retrieve_body( $response );
		if ( empty( $html ) ) {
			return;
		}

		$title = $this->extract_title( $html );
		$text  = $this->extract_text_content( $html );
		if ( empty( $text ) ) {
			return;
		}

		$chunks = $this->split_into_chunks( $text, 1200, 200 );
		foreach ( $chunks as $chunk ) {
			$this->upsert_chunk( $url, $title, $chunk );
		}
	}

	private function extract_title( $html ) {
		if ( preg_match( '/<title>(.*?)<\\/title>/is', $html, $m ) ) {
			return wp_strip_all_tags( html_entity_decode( $m[1], ENT_QUOTES | ENT_HTML5 ) );
		}
		return '';
	}

	private function extract_text_content( $html ) {
		$dom = new DOMDocument();
		libxml_use_internal_errors( true );
		$dom->loadHTML( $html );
		$xpath = new DOMXPath( $dom );

		// Remove scripts, styles, nav, footer
		foreach ( [ 'script', 'style', 'nav', 'footer', 'noscript' ] as $tag ) {
			foreach ( $dom->getElementsByTagName( $tag ) as $node ) {
				$node->parentNode->removeChild( $node );
			}
		}
		$body_nodes = $xpath->query( '//body' );
		$content = '';
		if ( $body_nodes->length > 0 ) {
			$content = $body_nodes->item(0)->textContent;
		}
		$content = preg_replace( '/\s+/', ' ', $content );
		return trim( $content );
	}

	private function split_into_chunks( $text, $max_len, $overlap ) {
		$chunks = [];
		$length = strlen( $text );
		$start  = 0;
		while ( $start < $length ) {
			$end = min( $start + $max_len, $length );
			$chunks[] = substr( $text, $start, $end - $start );
			if ( $end === $length ) {
				break;
			}
			$start = $end - $overlap;
			if ( $start < 0 ) {
				$start = 0;
			}
		}
		return $chunks;
	}

	private function upsert_chunk( $url, $title, $content ) {
		global $wpdb;
		$table = $wpdb->prefix . 'wpcr_chunks';
		$hash  = md5( $url . '|' . $content );
		$now   = current_time( 'mysql' );

		$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE content_hash = %s", $hash ) );
		if ( $existing ) {
			$wpdb->update( $table, [
				'url'         => $url,
				'title'       => $title,
				'content'     => $content,
				'updated_at'  => $now,
			], [ 'id' => $existing ] );
			return;
		}

		$wpdb->insert( $table, [
			'url'        => $url,
			'title'      => $title,
			'content'    => $content,
			'content_hash' => $hash,
			'created_at' => $now,
			'updated_at' => $now,
		] );
	}

	public function register_rest_routes() {
		register_rest_route( 'wp-chatbot-rag/v1', '/chat', [
			'methods'  => 'POST',
			'permission_callback' => '__return_true',
			'args' => [
				'message' => [ 'type' => 'string', 'required' => true ],
			],
			'callback' => [ $this, 'rest_chat' ],
		] );
	}

	public function rest_chat( WP_REST_Request $request ) {
		$message = sanitize_text_field( $request->get_param( 'message' ) );
		if ( strlen( $message ) < 3 ) {
			return new WP_REST_Response( [ 'reply' => __( 'Please provide more detail.', 'wp-chatbot-rag' ) ], 200 );
		}

		$context = $this->search_chunks_by_keyword( $message, 5 );
		$reply = $this->compose_answer_from_context( $message, $context );

		return new WP_REST_Response( [ 'reply' => $reply, 'sources' => $context ], 200 );
	}

	private function search_chunks_by_keyword( $query, $limit = 5 ) {
		global $wpdb;
		$table = $wpdb->prefix . 'wpcr_chunks';
		$like = '%' . $wpdb->esc_like( $query ) . '%';
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT url, title, content FROM {$table} WHERE content LIKE %s OR title LIKE %s ORDER BY updated_at DESC LIMIT %d",
			$like, $like, $limit
		), ARRAY_A );
		return is_array( $rows ) ? $rows : [];
	}

	private function compose_answer_from_context( $question, $context_rows ) {
		if ( empty( $context_rows ) ) {
			return __( 'I could not find information on that yet. Try rephrasing or check back after the site has been indexed.', 'wp-chatbot-rag' );
		}
		$snippets = array_map( function( $row ) {
			$excerpt = mb_substr( $row['content'], 0, 320 );
			return sprintf( "%s — %s", $excerpt, $row['url'] );
		}, $context_rows );

		$answer = __( 'Here is what I found related to your question:', 'wp-chatbot-rag' ) . "\n\n" . implode( "\n\n", $snippets );
		return $answer;
	}
}

