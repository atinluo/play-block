<?php

defined( 'ABSPATH' ) || exit;

class Play_Extension {

    protected static $_instance = null;
    private $api_url = 'https://avtheme.com/wp-json/plugin/extension';

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Initialize the actions.
     */
    public function __construct() {
        $this->init();
    }

    public function init() {
        add_action( 'admin_menu', array( $this, 'menu' ) );
        do_action( 'play_block_import_init', $this );
    }

    public function menu() {
        add_submenu_page( 'play-block', ' ', ' ', 'manage_options', 'play-separate');
        add_submenu_page( 'play-block', esc_html__( 'Extensions', 'play-block' ), esc_html__( 'Extensions', 'play-block' ), 'manage_options', 'play-extension', [$this, 'play_extension_page']);
    }

    public function play_extension_page() {
        $request = wp_remote_get($this->api_url);
        $responseBody = '';
        if ( ( !is_wp_error($request)) && (200 === wp_remote_retrieve_response_code( $request ) ) ) {
            $responseBody = wp_remote_retrieve_body($request);
        }else{
            $responseBody = esc_html__( 'Something went wrong, Please try again later.', 'play-block' );
        }
        ?>
        <div class="wrap">
            <?php echo $responseBody; ?>
        </div>
        <?php
    }

}

Play_Extension::instance();
