<?php

defined( 'ABSPATH' ) || exit;

class Play_API {

    protected static $_instance = null;
    public $namespace = 'play';

    public static function instance() {

        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function __construct() {
        
        add_action( 'rest_api_init', array( $this, 'set_rest' ) );
        do_action( 'play_block_api_init', $this );

    }
    
    public function get_play_api_url($path = ''){
        $path = '/' . ltrim( $path, '/' );
        $url = get_rest_url( null, $this->namespace.$path );
        return apply_filters( 'play_rest_url', $url, $path );
    }

    public function set_rest() {
        register_rest_route( $this->namespace, '/play/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array( $this, 'play' ),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric( $param );
                    }
                ),
            ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/play/items', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_items' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/proxy', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'proxy' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/search', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'search' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/follow', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'follow' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/like', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'like' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/dislike', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'dislike' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/notification', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'notification' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/comments', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'comments' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/modal', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'modal' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/playlist', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'playlist' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/auth', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'auth' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/profile', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'profile' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/upload', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'upload' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/upload/stream', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'upload_stream' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/upload/featuredimg', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'upload_featuredimg' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/adtag', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'adtag' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/generatepwd', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'generatepwd' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/cart', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'cart' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/review', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'review' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/metadata', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'metadata' ),
            'permission_callback' => '__return_true',
        ) );
    }

    // api actions
    public function play( $request ) {
        do_action('play_play', $request);
    }

    public function search( $request ) {
        do_action('play_search', $request);
    }

    public function get_items( $request ) {
        do_action('play_get_items', $request);
    }

    public function follow( $request ) {
        do_action('play_follow', $request);
    }

    public function like( $request ) {
        do_action('play_like', $request);
    }

    public function dislike( $request ) {
        do_action('play_dislike', $request);
    }

    public function notification( $request ) {
        do_action('play_notification', $request);
    }

    public function playlist( $request ) {
        do_action('play_playlist', $request);
    }

    public function comments( $request ) {
        do_action('play_comments', $request);
    }

    public function upload( $request ) {
        do_action('play_upload', $request);
    }

    public function upload_stream( $request ) {
        do_action('play_upload_stream', $request);
    }

    public function upload_featuredimg( $request ) {
        do_action('play_upload_featuredimg', $request);
    }

    public function auth( $request ) {
        do_action('play_auth', $request);
    }

    public function profile( $request ) {
        do_action('play_update_profile', $request);
    }

    public function metadata( $request ){
        do_action('play_metadata', $request);
    }

    public function adtag($request){
        $url = play_get_option( 'ad_tagurl' );
        $response = wp_remote_get($url);
        if (!is_wp_error($response) ) {
            $response_body = wp_remote_retrieve_body($response);
            $response_type = wp_remote_retrieve_header($response, 'content-type');
            
            if(strpos($response_type, 'xml') !== false){
                $data = new WP_REST_Response();
                $data->header( 'Access-Control-Allow-Origin', '*' );
                $data->header( 'Access-Control-Allow-Origin', 'https://imasdk.googleapis.com' );
                $data->header( 'Access-Control-Allow-Credentials', 'true' );
                $data->header( 'Content-Type', $response_type );
                $data->set_data($response_body);
                add_filter( 'rest_pre_serve_request', array( $this, 'server_data' ), 11, 2 );
                return $data;
            }else{
                return new WP_REST_Response($response_body);
            }
        } else {
            return new WP_Error('error');
        }
    }

    public function generatepwd($request){
        wp_send_json_success( wp_generate_password( 24 ) );
    }

    public function cart($request){
        do_action('play_cart', $request);
    }

    public function review($request){
        do_action('play_review', $request);
    }

    public function proxy( $request ) {
        if(isset($request[ 'url' ])){
            if(!isset($request[ 'radio' ])){
                do_action('play_proxy_stream', $request);
                return;
            }
            $response = wp_remote_get($request[ 'url' ]);
            if (!is_wp_error($response) ) {
                $response_body = wp_remote_retrieve_body($response);
                $response_type = wp_remote_retrieve_header($response, 'content-type');
                
                if(strpos($response_type, 'image') !== false){
                    $data = new WP_REST_Response();
                    $data->header( 'Accept-Ranges', 'bytes' );
                    $data->header( 'Content-Type', $response_type );
                    $data->header( 'Content-Length', wp_remote_retrieve_header($response, 'content-length') );
                    $data->set_data($response_body);
                    add_filter( 'rest_pre_serve_request', array( $this, 'server_data' ), 11, 2 );
                    return $data;
                }else{
                    return new WP_REST_Response($response_body);
                }
            } else {
                return new WP_Error('error');
            }
        }else{
            return new WP_REST_Response('No request');
        }
    }

    public function server_data( $served, $result ) {
        $data = $result->get_data();
        echo $data;
        return true;
    }

    public function modal( $request ) {
        if(!$this->isAjax()){
            wp_redirect(home_url());
            exit;
        }
        $modal = sanitize_text_field( $request[ 'name' ] );
        $content = '';
        switch ( $modal ) {
            case 'playlist':
                $content = play_get_template_html( 'blocks/playlist.php' );
                break;
            case 'share':
                $content = play_get_template_html( 'blocks/share.php' );
                break;
            case 'remove':
                $content = play_get_template_html( 'blocks/remove.php' );
                break;
            case 'delete-account':
                $content = play_get_template_html( 'blocks/delete-account.php' );
                break;
            case 'comment':
                $post_id = $request[ 'post_id' ];
                $content = play_get_template_html( 'blocks/comment.php', array('post_id' => $post_id) );
                break;
            default:
                $content = apply_filters('play_modal_'.$modal, $request);
                break;
        }
        return Play_Utils::instance()->response(
            array( 'content' => $content )
        );
    }

    public function isAjax() {
      return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
}

Play_API::instance();
