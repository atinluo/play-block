<?php
/**
 * Plugin Name: Play Native API
 * Description: Standalone REST API for Play Block functionalities to support native apps. Safe from Play Block updates.
 * Author: Your Team
 * Version: 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Play_Native_API' ) ) {

    class Play_Native_API {

        protected static $_instance = null;
        private $namespace = 'play-native';

        public static function instance() {
            if ( is_null( self::$_instance ) ) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        public function __construct() {
            add_action( 'rest_api_init', array( $this, 'register_routes' ) );
        }

        public function register_routes() {
            // Users
            register_rest_route( $this->namespace, '/users/(?P<id>\\d+)/likes', array(
                'methods'  => 'GET',
                'callback' => array( $this, 'user_likes' ),
                'permission_callback' => '__return_true',
            ) );

            register_rest_route( $this->namespace, '/users/(?P<id>\\d+)/followers', array(
                'methods'  => 'GET',
                'callback' => array( $this, 'user_followers' ),
                'permission_callback' => '__return_true',
            ) );

            register_rest_route( $this->namespace, '/users/(?P<id>\\d+)/following', array(
                'methods'  => 'GET',
                'callback' => array( $this, 'user_following' ),
                'permission_callback' => '__return_true',
            ) );

            register_rest_route( $this->namespace, '/users/(?P<id>\\d+)/downloads', array(
                'methods'  => 'GET',
                'callback' => array( $this, 'user_downloads' ),
                'permission_callback' => '__return_true',
            ) );

            register_rest_route( $this->namespace, '/users/(?P<id>\\d+)/played', array(
                'methods'  => 'GET',
                'callback' => array( $this, 'user_played' ),
                'permission_callback' => '__return_true',
            ) );

            register_rest_route( $this->namespace, '/users/(?P<id>\\d+)/stats', array(
                'methods'  => 'GET',
                'callback' => array( $this, 'user_stats' ),
                'permission_callback' => '__return_true',
            ) );

            register_rest_route( $this->namespace, '/users/(?P<id>\\d+)/profile', array(
                'methods'  => 'GET',
                'callback' => array( $this, 'user_profile' ),
                'permission_callback' => '__return_true',
            ) );

            register_rest_route( $this->namespace, '/user/avatar', array(
                'methods'  => 'POST',
                'callback' => array( $this, 'avatar_upload' ),
                'permission_callback' => function () { return is_user_logged_in(); },
            ) );

            // Posts
            register_rest_route( $this->namespace, '/posts/(?P<id>\\d+)/data', array(
                'methods'  => 'GET',
                'callback' => array( $this, 'post_data' ),
                'permission_callback' => '__return_true',
            ) );

            register_rest_route( $this->namespace, '/posts/(?P<id>\\d+)/playable', array(
                'methods'  => 'GET',
                'callback' => array( $this, 'post_playable' ),
                'permission_callback' => '__return_true',
            ) );

            register_rest_route( $this->namespace, '/posts/(?P<id>\\d+)/stats', array(
                'methods'  => 'GET',
                'callback' => array( $this, 'post_stats' ),
                'permission_callback' => '__return_true',
            ) );

            register_rest_route( $this->namespace, '/download/(?P<id>\\d+)', array(
                'methods'  => 'GET',
                'callback' => array( $this, 'download_info' ),
                'permission_callback' => '__return_true',
            ) );

            // Social actions (delegated)
            register_rest_route( $this->namespace, '/likes', array(
                'methods'  => 'POST',
                'callback' => array( $this, 'likes_post' ),
                'permission_callback' => function () { return is_user_logged_in(); },
            ) );

            register_rest_route( $this->namespace, '/dislikes', array(
                'methods'  => 'POST',
                'callback' => array( $this, 'dislikes_post' ),
                'permission_callback' => function () { return is_user_logged_in(); },
            ) );

            register_rest_route( $this->namespace, '/follows', array(
                'methods'  => 'POST',
                'callback' => array( $this, 'follow_post' ),
                'permission_callback' => function () { return is_user_logged_in(); },
            ) );

            // Playlists
            register_rest_route( $this->namespace, '/playlists', array(
                array(
                    'methods'  => 'GET',
                    'callback' => array( $this, 'playlists_get' ),
                    'permission_callback' => '__return_true',
                ),
                array(
                    'methods'  => 'POST',
                    'callback' => array( $this, 'playlists_post' ),
                    'permission_callback' => function () { return is_user_logged_in(); },
                ),
            ) );

            // Notifications
            register_rest_route( $this->namespace, '/notifications', array(
                'methods'  => 'GET',
                'callback' => array( $this, 'notifications_get' ),
                'permission_callback' => function () { return is_user_logged_in(); },
            ) );

            // Discovery/config
            register_rest_route( $this->namespace, '/options', array(
                'methods'  => 'GET',
                'callback' => array( $this, 'options_get' ),
                'permission_callback' => '__return_true',
            ) );

            register_rest_route( $this->namespace, '/taxonomies', array(
                'methods'  => 'GET',
                'callback' => array( $this, 'taxonomies_get' ),
                'permission_callback' => '__return_true',
            ) );
        }

        // Helpers
        private function ensure( $class ) {
            if ( ! class_exists( $class ) ) {
                return new WP_Error( 'dependency_missing', $class . ' not available', array( 'status' => 501 ) );
            }
            return true;
        }

        // Users
        public function user_likes( $request ) {
            $user_id = absint( $request['id'] );
            if ( $user_id === 0 && is_user_logged_in() ) { $user_id = get_current_user_id(); }
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
            if ( $user_id === 0 && is_user_logged_in() ) { $user_id = get_current_user_id(); }
            $downloads = apply_filters( 'user_download', $user_id );
            return new WP_REST_Response( array( 'user_id' => $user_id, 'downloads' => $downloads ) );
        }

        public function user_played( $request ) {
            $user_id = absint( $request['id'] );
            if ( $user_id === 0 && is_user_logged_in() ) { $user_id = get_current_user_id(); }
            $played = apply_filters( 'user_played', $user_id );
            return new WP_REST_Response( array( 'user_id' => $user_id, 'played' => $played ) );
        }

        public function user_stats( $request ) {
            $ok = $this->ensure( 'Play_Count' ); if ( is_wp_error( $ok ) ) { return $ok; }
            $user_id = absint( $request['id'] );
            if ( $user_id === 0 && is_user_logged_in() ) { $user_id = get_current_user_id(); }
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
            if ( ! $user ) { return new WP_Error( 'not_found', 'User not found', array( 'status' => 404 ) ); }
            $avatar = get_avatar_url( $user_id );
            $links  = apply_filters( 'play_user_links', array() );
            $social = array();
            foreach ( $links as $key => $value ) {
                $val = get_user_meta( $user_id, $key, true );
                if ( ! empty( $val ) ) { $social[ $key ] = $val; }
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

        public function avatar_upload( $request ) {
            $ok = $this->ensure( 'Play_User_Avatar' ); if ( is_wp_error( $ok ) ) { return $ok; }
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

        // Posts
        public function post_data( $request ) {
            $ok = $this->ensure( 'Play_Stream' ); if ( is_wp_error( $ok ) ) { return $ok; }
            $post_id = absint( $request['id'] );
            $data = Play_Stream::instance()->get_play_item( $post_id );
            if ( ! $data ) { return new WP_Error( 'not_found', 'Post not found or not playable', array( 'status' => 404 ) ); }
            return new WP_REST_Response( $data );
        }

        public function post_playable( $request ) {
            $ok = $this->ensure( 'Play_Count' ); if ( is_wp_error( $ok ) ) { return $ok; }
            $post_id = absint( $request['id'] );
            $playable = Play_Count::instance()->is_playable( $post_id );
            return new WP_REST_Response( array( 'post_id' => $post_id, 'playable' => (bool) $playable ) );
        }

        public function post_stats( $request ) {
            $ok = $this->ensure( 'Play_Count' ); if ( is_wp_error( $ok ) ) { return $ok; }
            $post_id = absint( $request['id'] );
            if ( ! get_post( $post_id ) ) { return new WP_Error( 'not_found', 'Post not found', array( 'status' => 404 ) ); }
            $times = array( 'all', 'year', 'month', 'week', 'day' );
            $stats = array();
            foreach ( $times as $t ) {
                $key        = Play_Count::instance()->get_time( $t );
                $stats[$t]  = (int) get_post_meta( $post_id, $key, true );
            }
            return new WP_REST_Response( array( 'post_id' => $post_id, 'stats' => $stats ) );
        }

        public function download_info( $request ) {
            $ok = $this->ensure( 'Play_Download' ); if ( is_wp_error( $ok ) ) { return $ok; }
            $id = absint( $request['id'] );
            $can = Play_Download::instance()->user_can_download( $id );
            $url = Play_Download::instance()->get_download_url( $id );
            $count = (int) get_post_meta( $id, 'download_count', true );
            return new WP_REST_Response( array( 'post_id' => $id, 'can_download' => (bool) $can, 'download_url' => $url, 'download_count' => $count ) );
        }

        // Social actions (delegate to existing handlers)
        public function likes_post( $request ) { do_action( 'play_like', $request ); return new WP_REST_Response( array( 'status' => 'ok' ) ); }
        public function dislikes_post( $request ) { do_action( 'play_dislike', $request ); return new WP_REST_Response( array( 'status' => 'ok' ) ); }
        public function follow_post( $request ) { do_action( 'play_follow', $request ); return new WP_REST_Response( array( 'status' => 'ok' ) ); }

        // Playlists
        public function playlists_get( $request ) {
            $ok = $this->ensure( 'Play_Playlist' ); if ( is_wp_error( $ok ) ) { return $ok; }
            $user_id = isset( $request['user_id'] ) ? absint( $request['user_id'] ) : ( is_user_logged_in() ? get_current_user_id() : 0 );
            if ( $user_id === 0 ) { return new WP_REST_Response( array() ); }
            $items = Play_Playlist::instance()->get( $user_id );
            return new WP_REST_Response( array( 'user_id' => $user_id, 'playlists' => $items ) );
        }

        public function playlists_post( $request ) { do_action( 'play_playlist', $request ); return new WP_REST_Response( array( 'status' => 'ok' ) ); }

        // Notifications
        public function notifications_get( $request ) {
            $ok = $this->ensure( 'Play_Notification' ); if ( is_wp_error( $ok ) ) { return $ok; }
            if ( ! is_user_logged_in() ) { return new WP_Error( 'forbidden', 'Authentication required', array( 'status' => 401 ) ); }
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

        // Discovery/config
        public function options_get( $request ) {
            $play_get_option_exists = function_exists( 'play_get_option' );
            $settings = array(
                'play_types'        => $play_get_option_exists ? play_get_option( 'play_types' ) : array( 'station' ),
                'register_types'    => $play_get_option_exists ? play_get_option( 'register_types' ) : array( 'station' ),
                'upload'            => array(
                    'post_type'      => $play_get_option_exists ? play_get_option( 'post_type' ) : 'station',
                    'upload_cat'     => $play_get_option_exists ? play_get_option( 'upload_cat' ) : 'genre',
                    'upload_tag'     => $play_get_option_exists ? play_get_option( 'upload_tag' ) : 'station_tag',
                    'upload_tax'     => $play_get_option_exists ? play_get_option( 'upload_tax' ) : 'artist',
                    'purchaseable'   => $play_get_option_exists ? (bool) play_get_option( 'purchaseable' ) : false,
                ),
                'player'            => array(
                    'login_to_play'  => $play_get_option_exists ? (bool) play_get_option( 'login_to_play' ) : false,
                    'preview_length' => $play_get_option_exists ? (int) play_get_option( 'preview_length' ) : 0,
                    'player_history' => $play_get_option_exists ? (bool) play_get_option( 'player_history' ) : false,
                ),
                'block_types'       => class_exists( 'Play_Block' ) ? Play_Block::instance()->get_play_block_types() : array( 'Single' => 'single', 'Playlist' => 'playlist' ),
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

    Play_Native_API::instance();
}

