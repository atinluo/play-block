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

        // Additional endpoints for native apps
        register_rest_route( $this->namespace, '/users/(?P<id>\\d+)/likes', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'user_likes' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/users/(?P<id>\\d+)/followers', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'user_followers' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/users/(?P<id>\\d+)/following', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'user_following' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/users/(?P<id>\\d+)/downloads', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'user_downloads' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/users/(?P<id>\\d+)/played', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'user_played' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/users/(?P<id>\\d+)/stats', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'user_stats' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/users/(?P<id>\\d+)/profile', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'user_profile' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/posts/(?P<id>\\d+)/stats', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'post_stats' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/posts/(?P<id>\\d+)/playable', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'post_playable' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/posts/(?P<id>\\d+)/data', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'post_data' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/playlists', array(
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'playlists_get' ),
                'permission_callback' => '__return_true',
            ),
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'playlists_post' ),
                'permission_callback' => function () { return is_user_logged_in(); },
            ),
        ) );

        register_rest_route( $this->namespace, '/follows', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'follow_post' ),
            'permission_callback' => function () { return is_user_logged_in(); },
        ) );

        register_rest_route( $this->namespace, '/likes', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'likes_post' ),
            'permission_callback' => function () { return is_user_logged_in(); },
        ) );

        register_rest_route( $this->namespace, '/dislikes', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'dislikes_post' ),
            'permission_callback' => function () { return is_user_logged_in(); },
        ) );

        register_rest_route( $this->namespace, '/notifications', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'notifications_get' ),
            'permission_callback' => function () { return is_user_logged_in(); },
        ) );

        register_rest_route( $this->namespace, '/download/(?P<id>\\d+)', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'download_info' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/user/avatar', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'avatar_upload' ),
            'permission_callback' => function () { return is_user_logged_in(); },
        ) );

        register_rest_route( $this->namespace, '/options', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'options_get' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/taxonomies', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'taxonomies_get' ),
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

    // New callbacks for additional REST endpoints
    public function user_likes( $request ) {
        $user_id = absint( $request['id'] );
        if ( $user_id === 0 && is_user_logged_in() ) {
            $user_id = get_current_user_id();
        }
        $likes = apply_filters( 'user_likes', $user_id );
        return new WP_REST_Response( array( 'user_id' => $user_id, 'likes' => $likes ) );
    }

    public function user_followers( $request ) {
        $user_id = absint( $request['id'] );
        $followers = apply_filters( 'user_follow', $user_id );
        return new WP_REST_Response( array( 'user_id' => $user_id, 'followers' => $followers ) );
    }

    public function user_following( $request ) {
        $user_id = absint( $request['id'] );
        $following = apply_filters( 'user_following', $user_id );
        return new WP_REST_Response( array( 'user_id' => $user_id, 'following' => $following ) );
    }

    public function user_downloads( $request ) {
        $user_id = absint( $request['id'] );
        if ( $user_id === 0 && is_user_logged_in() ) {
            $user_id = get_current_user_id();
        }
        $downloads = apply_filters( 'user_download', $user_id );
        return new WP_REST_Response( array( 'user_id' => $user_id, 'downloads' => $downloads ) );
    }

    public function user_played( $request ) {
        $user_id = absint( $request['id'] );
        if ( $user_id === 0 && is_user_logged_in() ) {
            $user_id = get_current_user_id();
        }
        $played = apply_filters( 'user_played', $user_id );
        return new WP_REST_Response( array( 'user_id' => $user_id, 'played' => $played ) );
    }

    public function user_stats( $request ) {
        $user_id = absint( $request['id'] );
        if ( $user_id === 0 && is_user_logged_in() ) {
            $user_id = get_current_user_id();
        }
        $stats = array(
            'played'     => Play_Count::instance()->user_total_played( $user_id, 'all' ),
            'liked'      => Play_Count::instance()->user_total_liked( $user_id ),
            'downloaded' => Play_Count::instance()->user_total_downloaded( $user_id ),
        );
        return new WP_REST_Response( array( 'user_id' => $user_id, 'stats' => $stats ) );
    }

    public function user_profile( $request ) {
        $user_id = absint( $request['id'] );
        $user    = get_userdata( $user_id );
        if ( ! $user ) {
            return new WP_Error( 'not_found', 'User not found', array( 'status' => 404 ) );
        }
        $avatar = get_avatar_url( $user_id );
        $links  = apply_filters( 'play_user_links', array() );
        $social = array();
        foreach ( $links as $key => $value ) {
            $val = get_user_meta( $user_id, $key, true );
            if ( ! empty( $val ) ) {
                $social[ $key ] = $val;
            }
        }
        $data = array(
            'id'            => $user->ID,
            'display_name'  => $user->display_name,
            'description'   => get_user_meta( $user_id, 'description', true ),
            'user_url'      => $user->user_url,
            'avatar_url'    => $avatar,
            'social'        => $social,
        );
        return new WP_REST_Response( $data );
    }

    public function post_stats( $request ) {
        $post_id = absint( $request['id'] );
        if ( ! get_post( $post_id ) ) {
            return new WP_Error( 'not_found', 'Post not found', array( 'status' => 404 ) );
        }
        $times = array( 'all', 'year', 'month', 'week', 'day' );
        $stats = array();
        foreach ( $times as $t ) {
            $key   = Play_Count::instance()->get_time( $t );
            $stats[ $t ] = (int) get_post_meta( $post_id, $key, true );
        }
        return new WP_REST_Response( array( 'post_id' => $post_id, 'stats' => $stats ) );
    }

    public function post_playable( $request ) {
        $post_id = absint( $request['id'] );
        $playable = Play_Count::instance()->is_playable( $post_id );
        return new WP_REST_Response( array( 'post_id' => $post_id, 'playable' => (bool) $playable ) );
    }

    public function post_data( $request ) {
        $post_id = absint( $request['id'] );
        $data = Play_Stream::instance()->get_play_item( $post_id );
        if ( ! $data ) {
            return new WP_Error( 'not_found', 'Post not found or not playable', array( 'status' => 404 ) );
        }
        return new WP_REST_Response( $data );
    }

    public function playlists_get( $request ) {
        $user_id = isset( $request['user_id'] ) ? absint( $request['user_id'] ) : ( is_user_logged_in() ? get_current_user_id() : 0 );
        if ( $user_id === 0 ) {
            return new WP_REST_Response( array() );
        }
        $items = Play_Playlist::instance()->get( $user_id );
        return new WP_REST_Response( array( 'user_id' => $user_id, 'playlists' => $items ) );
    }

    public function playlists_post( $request ) {
        // Delegate to existing action handler which performs nonce and capability checks
        do_action( 'play_playlist', $request );
        // Response is echoed by handler; return a generic OK to satisfy REST flow
        return new WP_REST_Response( array( 'status' => 'ok' ) );
    }

    public function follow_post( $request ) {
        do_action( 'play_follow', $request );
        return new WP_REST_Response( array( 'status' => 'ok' ) );
    }

    public function likes_post( $request ) {
        do_action( 'play_like', $request );
        return new WP_REST_Response( array( 'status' => 'ok' ) );
    }

    public function dislikes_post( $request ) {
        do_action( 'play_dislike', $request );
        return new WP_REST_Response( array( 'status' => 'ok' ) );
    }

    public function notifications_get( $request ) {
        if ( ! is_user_logged_in() ) {
            return new WP_Error( 'forbidden', 'Authentication required', array( 'status' => 401 ) );
        }
        $pages = isset( $request['pages'] ) ? absint( $request['pages'] ) : 10;
        $paged = isset( $request['paged'] ) ? absint( $request['paged'] ) : 1;
        $args  = array(
            'type'      => 'custom_type:notification',
            'pages'     => $pages,
            'paged'     => $paged,
            'user_id'   => get_current_user_id(),
            'order'     => 'DESC',
            'orderby'   => 'date_notified',
        );
        $query = Play_Notification::instance()->get( $args );
        return new WP_REST_Response( array( 'max_pages' => $query->max_num_pages, 'items' => $query->items ) );
    }

    public function download_info( $request ) {
        $id = absint( $request['id'] );
        $can = Play_Download::instance()->user_can_download( $id );
        $url = Play_Download::instance()->get_download_url( $id );
        $count = (int) get_post_meta( $id, 'download_count', true );
        return new WP_REST_Response( array( 'post_id' => $id, 'can_download' => (bool) $can, 'download_url' => $url, 'download_count' => $count ) );
    }

    public function avatar_upload( $request ) {
        if ( ! is_user_logged_in() ) {
            return new WP_Error( 'forbidden', 'Authentication required', array( 'status' => 401 ) );
        }
        $files = $request->get_file_params();
        if ( empty( $files['avatar'] ) ) {
            return new WP_Error( 'bad_request', 'No avatar file uploaded', array( 'status' => 400 ) );
        }
        $user_id = get_current_user_id();
        Play_User_Avatar::instance()->save_avatar( $user_id, $files );
        return new WP_REST_Response( array( 'status' => 'success', 'avatar_url' => get_avatar_url( $user_id ) ) );
    }

    public function options_get( $request ) {
        $settings = array(
            'play_types'        => play_get_option( 'play_types' ),
            'register_types'    => play_get_option( 'register_types' ),
            'upload'            => array(
                'post_type'      => play_get_option( 'post_type' ),
                'upload_cat'     => play_get_option( 'upload_cat' ),
                'upload_tag'     => play_get_option( 'upload_tag' ),
                'upload_tax'     => play_get_option( 'upload_tax' ),
                'purchaseable'   => (bool) play_get_option( 'purchaseable' ),
            ),
            'player'            => array(
                'login_to_play'  => (bool) play_get_option( 'login_to_play' ),
                'preview_length' => (int) play_get_option( 'preview_length' ),
                'player_history' => (bool) play_get_option( 'player_history' ),
            ),
            'block_types'       => Play_Block::instance()->get_play_block_types(),
        );
        return new WP_REST_Response( $settings );
    }

    public function taxonomies_get( $request ) {
        $results = array();
        $taxes = get_taxonomies( array( 'public' => true ), 'objects' );
        foreach ( $taxes as $name => $tax ) {
            $terms = get_terms( array( 'taxonomy' => $name, 'hide_empty' => false ) );
            $results[] = array(
                'name'          => $name,
                'label'         => $tax->label,
                'hierarchical'  => (bool) $tax->hierarchical,
                'show_in_rest'  => (bool) $tax->show_in_rest,
                'terms'         => is_wp_error( $terms ) ? array() : array_map( function( $t ) { return array( 'id' => $t->term_id, 'name' => $t->name, 'slug' => $t->slug ); }, $terms ),
            );
        }
        return new WP_REST_Response( $results );
    }
}

Play_API::instance();
