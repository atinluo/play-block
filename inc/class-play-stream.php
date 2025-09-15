<?php

defined( 'ABSPATH' ) || exit;

class Play_Stream {

    protected static $_instance = null;
    private $path = "";
    private $stream = "";
    private $type = "";
    private $buffer = 10240; // 10k
    private $stream_chunk_size = 10240;
    private $start  = -1;
    private $end    = -1;
    private $size   = 0;
    private $preview = false;
    private $endpoint = 'stream';

    public static function instance() {

        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function __construct() {
        if(play_get_option('php_stream')){
            add_filter('play_stream_url', array( $this, 'stream_url' ), 10, 2);
        }
        add_action( 'template_redirect', array( $this, 'play_stream' ) );
        add_action( 'init', array( $this, 'add_rewrite' ) );
        add_action( 'query_vars', array( $this, 'add_query_vars' ) );
        add_action( 'parse_request', array( $this, 'embed' ) );

        add_action( 'play_play', array( $this, 'play' ) );
        add_action( 'play_search', array( $this, 'search' ) );
        add_action( 'play_get_items', array( $this, 'get_items' ) );
        add_action( 'play_metadata', array( $this, 'stream_metadata' ) );
        add_action( 'play_proxy_stream', array( $this, 'stream_proxy' ) );
        
        add_filter( 'posts_search', array( $this, 'filter_search' ), 10, 2 );

        $types = play_get_option( 'play_types' );
        if ( ! empty( $types ) ) {
            foreach ( $types as $type ) {
                add_filter( 'rest_prepare_' . $type, array( $this, 'rest_prepare_post' ), 10, 3 );
            }
        }
        
        function play_get_data($id){
            return Play_Stream::instance()->get_data($id);
        }

        function play_get_preview($id){
            return Play_Stream::instance()->get_preview($id);
        }
    }

    public function add_rewrite() {
        add_rewrite_rule('^embed/([a-z0-9]+)/?', 'index.php?embed=$matches[1]', 'top');
        add_rewrite_endpoint( $this->endpoint, EP_PERMALINK );
    }

    public function add_query_vars( $vars ) {
        $vars[] = 'embed';
        return $vars;
    }

    public function embed() {
        global $wp;
        if ( !empty( $wp->query_vars['embed'] ) ) {
            play_get_template( 'blocks/embed.php' );
            exit();
        }
    }

    public function search( $request ) {
        $search = sanitize_text_field( $request[ 'search' ] );
        $data = [];
        $posts = [];
        $users = [];

        // post search
        $search_post_type = apply_filters( 'search_post', array_merge(play_get_option( 'play_types' ), array('post')));
        $_posts = get_posts( apply_filters( 'play_block_post_search_args', array(
            'post_type'        => $search_post_type,
            's'                => $search,
            'numberposts'      => 10,
            'suppress_filters' => false,
            'orderby'          => 'relevance'
        )) );

        if ( ! empty( $_posts ) ) {
            foreach ( $_posts as $post ) {
                $p            = array(
                    'title'     => $post->post_title,
                    'thumbnail' => '',
                    'author'    => get_the_author_meta( 'display_name', $post->post_author ),
                    'url'       => get_permalink( $post->ID ),
                    'type'      => 'post'
                );
                if (taxonomy_exists('artist') && get_the_term_list( $post->ID, 'artist' ) ) {
                    $artist_sep = ', ';
                    $artist_link = ' & ';
                    $terms = get_the_terms( $post->ID, 'artist' );
                    $str = join($artist_sep, wp_list_pluck($terms, 'name'));
                    if( strpos($str, $artist_sep) ){
                        $str = substr_replace($str, $artist_link, strrpos($str, $artist_sep), strlen($artist_sep) );
                    }
                    $p['author'] = $str;
                }
                $thumbnail_id = get_post_thumbnail_id( $post->ID );
                if ( $thumbnail_id ) {
                    $img              = wp_get_attachment_image( $thumbnail_id );
                    $p[ 'thumbnail' ] = $img;
                }
                $posts[] = apply_filters( 'play_block_get_post_search_item', $p, $post, $request );
            }
        }

        $posts = apply_filters( 'play_block_post_search', $posts, $search, $request );

        // user search
        $_users = get_users( apply_filters( 'play_block_user_search_args', array(
            'search' => '*' . apply_filters( 'search_user', $search ) . '*'
        ) ) );

        if ( ! empty( $_users ) ) {
            foreach ( $_users as $user ) {
                $p   = array(
                    'title'     => $user->display_name,
                    'thumbnail' => '',
                    'author'    => '',
                    'url'       => get_author_posts_url( $user->ID ),
                    'type'      => 'user'
                );
                $img = get_avatar( $user->ID );
                if ( $img ) {
                    $p[ 'thumbnail' ] = $img;
                }
                $users[] = apply_filters( 'play_block_get_user_search_item', $p, $user, $request );
            }
        }

        $users = apply_filters( 'play_block_user_search', $users, $search, $request );

        $data = array_merge($posts, $users);

        return Play_Utils::instance()->response( $data );
    }

    public function play( $request ) {
        $data  = [];
        $id    = (int) $request[ 'id' ];
        $_type = isset($request[ 'type' ]) ? sanitize_text_field( $request[ 'type' ] ) : 'post';
        if ( $_type == 'played' ){
            if ( Play_Utils::instance()->validate_nonce( $request['nonce'] ) ) {
                do_action( 'save_play_count', $id );
            }
            return Play_Utils::instance()->response( ['msg' => 'played'] );
        }
        if ( $_type == 'user' ) {
            $args  = array(
                'post_type'  => play_get_option( 'post_type' ),
                'author'     => (int) $id,
                'fields'     => 'ids',
                'meta_query' => array(
                    array(
                        'key'     => 'type',
                        'value'   => array( 'album', 'playlist', 'series' ),
                        'compare' => 'NOT IN'
                    )
                )
            );
            $posts = get_posts( apply_filters('play_player_user_args', $args ) );
            foreach ( $posts as $key => $post_id ) {
                $data[] = $this->get_play_item( $post_id );
            }
            return Play_Utils::instance()->response( $data );
        }
        if ( $_type == 'next' && isset( $request[ 'ids' ] ) ) {
            if( !apply_filters('play_player_auto_next', true) ){
                return;
            }
            $ids = array_filter( $request[ 'ids' ], 'intval' );

            $args = array(
                'post_type'      => play_get_option( 'play_types' ),
                'posts_per_page' => 1,
                'orderby'        => 'date',
                'order'          => 'DESC',
                'exclude'        => $ids,
                'post_status'    => 'publish',
                'fields'         => 'ids',
                'meta_key'       => 'type',
                'meta_value'     => 'single'
            );

            $tag  = apply_filters( 'play_next_tag', 'genre' );
            $tags = wp_get_post_terms( $id, $tag );

            if (!is_wp_error($tags) && !empty($tags) ) {
                // get data from same artist
                $args[ 'tax_query' ] = array();
                foreach ( $tags as $k => $v ) {
                    $term                  = $tags[ $k ];
                    $args[ 'tax_query' ][] = array(
                        'taxonomy' => $tag,
                        'field'    => 'slug',
                        'terms'    => $term->slug
                    );
                }
                if ( ! empty( $args[ 'tax_query' ] ) ) {
                    $args[ 'tax_query' ][ 'relation' ] = 'OR';
                }
            } else {
                // get data from same author
                $post             = get_post( $id );
                $args[ 'author' ] = $post->post_author;
            }

            $_id = get_posts( $args );
            if ( $_id ) {
                $data = $this->get_play_item( $_id[ 0 ] );
            }else{
                // get a song anyway
                unset($args[ 'tax_query' ]);
                unset($args[ 'author' ]);
                $_id = get_posts( $args );
                $data = $this->get_play_item( $_id[ 0 ] );
            }
            return Play_Utils::instance()->response( apply_filters('play_next', $data, $id, $ids) );
        }

        $type = get_post_meta( $id, 'type', true );

        $from = 0;
        if(!empty($request[ 'from' ])){
            $from = (int)$request[ 'from' ];
        }
        
        if ( in_array($type, ['album', 'playlist', 'series']) || $from ) {
            if($from){
                $id = $from;
            }
            if(isset($request[ 'ids' ])){
                $posts = $request[ 'ids' ];
            }else{
                $posts  = get_post_meta( $id, 'post', true );
                $posts = explode( ',', $posts );
            }
            foreach ( $posts as $key => $post_id ) {
                $item = $this->get_play_item( $post_id, $id );
                if ( $item ) {
                    $data[] = $item;
                }
            }
            if(empty($data)){
                $data = $this->get_play_item( $id, $id );
            }
            do_action( 'save_play_count', $id );
        } else {
            $data = $this->get_play_item( $id, $from );
        }

        Play_Utils::instance()->response( $data );
    }

    public function get_items( $request ) {
        $search    = sanitize_text_field( $request[ 'search' ] );
        $post_type = play_get_option( 'play_types' );
        //$post_type[] = 'attachment';
        $query = array(
            'post_type'      => $post_type,
            'post_status'    => 'any',
            'posts_per_page' => -1,
            's'              => $search
        );
        $type  = sanitize_text_field( $request[ 'type' ] );
        if ( $type == 'playlist' || $type == 'album' ) {
            $query[ 'meta_key' ]   = 'type';
            $query[ 'meta_value' ] = 'single';
        }
        $id = (int) $request[ 'id' ];
        if ( ! empty( $id ) ) {
            $query[ 'post__in' ] = explode( ',', $id );
            $query[ 'orderby' ]  = 'post__in';
        }
        $items = get_posts( $query );
        $data  = [];
        $i     = 0;
        foreach ( $items as $key => $item ) {
            $data[] = $item->ID . ':' . $item->post_title;
        }

        $data = apply_filters( 'play_block_get_items', $data, $search, $request );

        return Play_Utils::instance()->response( $data );
    }

    public function get_play_item( $post_id, $from = 0 ) {
        $post = get_post( $post_id );
        if ( ! $post ) {
            return false;
        }

        $artists     = array();
        $artist      = array();
        $artist_url  = array();

        if( taxonomy_exists('artist') ){
            $artists     = wp_get_post_terms( $post_id, 'artist' );
            $artist      = array_map( function ( $value ) {
                return $value->name;
            }, $artists );
            $artist_url  = array_map( function ( $value ) {
                return get_term_link( $value->term_id );
            }, $artists );
        }

        $artwork_url = wp_get_attachment_image_url( get_post_thumbnail_id( $post_id ),'large' );
        $user        = get_userdata( $post->post_author );
        $src         = get_post_meta( $post_id, 'stream', true );
        $src_url     = get_post_meta( $post_id, 'stream_url', true );
        $embed_url   = apply_filters('get_endpoint_url', 'embed', $post_id, home_url() );
        if ( ! empty( $src_url ) ) {
            $src = $src_url;
        }
        // fix youtube shorts
        $src = str_replace('/shorts/', '/embed/', $src);

        $provider = 'html5';
        $type     = 'audio';

        $provider_id = false;
        $providers = array('youtube.com', 'youtu.be', 'vimeo.com', 'spotify.com', 'soundcloud.com', 'mixcloud.com');
        $providers = apply_filters('play_stream_providers', $providers);
        foreach ( $providers as $key => $val ) {
            if ( strpos( $src, $val ) !== false ) {
                $provider = str_replace(array('.','com'), '', $val);
                $provider_id = Play_Utils::instance()->parsePlayURL($src);
                $type     = 'video';
            }
        }
        
        $video_types = array('avi','m3u8','mp4','mov','mpg','mpeg','m4v','mkv','ogv','rm','rmvb','webm','wmv','3gp','3g2');
        $video_types = apply_filters( 'play_block_video_types', $video_types );

        $ext = pathinfo($src, PATHINFO_EXTENSION);
        if ( in_array( $ext, $video_types ) ) {
            $type = 'video';
        }
        if ( strpos( $src, 'audio' ) !== false ) {
            $type = 'audio';
        }
        if ( strpos( $src, 'video' ) !== false ) {
            $type = 'video';
        }

        // get captions
        $captions = get_post_meta( $post_id, 'captions', true );
        $tracks = [];
        if($captions){
            foreach ( $captions as $key => $caption ) {
                $arr = explode(':', $caption);
                if(count($arr) > 0){
                    $label = $arr[0];
                    $caption = str_replace($label.':', '', $caption);
                    if($label !=='' && $caption !==''){
                        $tracks[] = array(
                            'kind' => 'captions',
                            'label' => $label,
                            'src'   => $caption,
                            'srclang' => strtolower(substr($label, -2))
                        );
                    }
                }
            }
        }

        // get sources
        $srcs = get_post_meta( $post_id, 'sources', true );
        $sources = [];
        if($srcs){
            foreach ( $srcs as $key => $val ) {
                $arr = explode(':', $val);
                if(count($arr) > 0){
                    $size = $arr[0];
                    $source = str_replace($size.':', '', $val);
                    if($size !=='' && $source !==''){
                        $sources[] = array(
                            'size' => $size,
                            'src'   => $source
                        );
                    }
                }
            }
        }

        $purchase_link = play_purchase_btn($post_id);
        $post_type     = get_post_type( $post_id );
        
        $data = array(
            'id'             => $post_id,
            'uri'            => get_permalink( $post_id ),
            'title'          => $post->post_title,
            'embed_url'      => $embed_url,
            'artwork_url'    => $artwork_url == false ? '' : $artwork_url,
            'stream_url'     => apply_filters( 'play_stream_url', $src, $post_id ),
            'provider'       => $provider,
            'type'           => $type,
            'release'        => $post->post_date,
            'duration'       => get_post_meta( $post_id, 'duration', true ),
            'start'          => get_post_meta( $post_id, 'start', true ),
            'end'            => get_post_meta( $post_id, 'end', true ),
            'caption'        => get_post_meta( $post_id, 'caption', true ),
            'user'           => $user->display_name,
            'user_url'       => get_author_posts_url( $post->post_author ),
            'artist'         => implode( ',', $artist ),
            'artist_url'     => implode( ',', $artist_url ),
            'downloadable'   => Play_Download::instance()->allow_download( $post_id ),
            'download_url'   => Play_Download::instance()->get_download_url( $post_id ),
            'purchase_title' => get_post_meta( $post_id, 'purchase_title', true ),
            'purchase_url'   => get_post_meta( $post_id, 'purchase_url', true ),
            'purchase_link'  => $purchase_link,
            'like'           => Play_Like::instance()->has_user_liked( get_current_user_id(), $post_id )
        );
        
        if(count($tracks) > 0){
            $tracks[0]['default'] = true;
            $data['tracks'] = $tracks;
        }
        if(count($sources) > 0){
            $data['sources'] = $sources;
        }
        if($provider_id){
            $data['provider_id'] = $provider_id;
        }
        // waveform
        $waveform = get_post_meta($post_id, 'waveform_data', true);
        if(apply_filters('play_player_waveform', false) && $waveform){
            $data['waveform'] = $waveform;
        }

        // simple membership plugin
        if (class_exists('SimpleWpMembership')) {
            $acl = SwpmAccessControl::get_instance();
            if(!$acl->can_i_read_post($post)){
                unset($data['sources']);
                $data['stream_url'] = '';
                $data['msg'] = $acl->why();
            }
        }
        
        if($from){
            $from_item = array(
                'id'    => $from,
                'title' => get_the_title($from),
                'link'  => get_the_permalink($from),
                'type'  => get_post_meta( $from, 'type', true )
            );
            $data['from'] = $from_item;

            // allow override the track link from album link
            if( get_post_meta( $from, 'type', true ) == 'album' && apply_filters('play_from_album', false) ){
                $data['uri'] = $from_item['link'];
            }
        }

        $data = apply_filters( 'play_single_data', $data );

        return $data;
    }

    public function rest_prepare_post( $data, $post, $context ) {
        $_data = $data->data;
        if ( isset( $_data[ 'meta' ][ 'post' ] ) && ! empty( $_data[ 'meta' ][ 'post' ] ) ) {
            $ids = explode( ',', $_data[ 'meta' ][ 'post' ] );
            if ( is_array( $ids ) && ! empty( $ids ) ) {
                $items = get_posts( array(
                    'post_type'   => 'any',
                    'post_status' => 'any',
                    'post__in'    => $ids,
                    'orderby'     => 'post__in',
                    'numberposts' => - 1
                ) );
                $arr   = [];
                foreach ( $items as $key => $item ) {
                    $arr[] = $item->ID . ':' . $item->post_title;
                }
                $_data[ 'meta' ][ 'items' ] = $arr;
            }
        }
        $data->data = $_data;

        return $data;
    }

    public function filter_search( $posts_search, $q ) {
        if ( empty( $posts_search ) ) {
            return $posts_search;
        }
        $search = esc_sql($q->query[ 's' ]);

        global $wpdb;

        // search user
        add_filter( 'pre_user_query', array( $this, 'filter_user_query' ) );
        $args  = array(
            'count_total'   => false,
            'search'        => sprintf( '*%s*', $search ),
            'search_fields' => array(
                'display_name',
                'user_login',
            ),
            'fields'        => 'ID',
        );
        $users = get_users( $args );
        remove_filter( 'pre_user_query', array( $this, 'filter_user_query' ) );

        if ( ! empty( $users ) ) {
            $posts_search = str_replace( ')))', ")) OR ( {$wpdb->posts}.post_author IN (" . implode( ',', array_map( 'absint', $users ) ) . ")))", $posts_search );
        }

        // search taxonomy
        $posts = [];
        $ss = array_filter(explode( ' ', $search ));
        foreach ( $ss as $s ){
            $post_query = $wpdb->prepare("SELECT tr.object_id 
                            FROM {$wpdb->term_relationships} tr 
                            INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id 
                            INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
                            WHERE t.name LIKE %s OR t.slug LIKE %s", '%'.$wpdb->esc_like( $s ).'%', '%'.$s.'%');
            $ids = $wpdb->get_results( $post_query );
            foreach($ids as $id){
                $posts[] = $id->object_id;
            }
        }

        if ( ! empty( $posts ) ) {
            $posts_search = str_replace( ')))', ")) OR ( {$wpdb->posts}.ID IN (" . implode( ',', array_map( 'absint', $posts ) ) . ")))", $posts_search );
        }

        return apply_filters('play_posts_search', $posts_search);
    }

    public function filter_user_query( &$user_query ) {
        if ( is_object( $user_query ) ) {
            $user_query->query_where = str_replace( "user_nicename LIKE", "display_name LIKE", $user_query->query_where );
        }

        return $user_query;
    }

    public function get_preview($id){
        $url  = get_post_meta( $id, 'stream', true );
        $_url = get_post_meta( $id, 'stream_url', true );
        if ( ! empty( $_url ) ) {
            $url = $_url;
        }
        $uri = Play_Utils::instance()->fixURL( $url );
        $preview_length = play_get_option('preview_length');
        
        if($uri && $preview_length){
            $role = play_get_option( 'preview_free_role' );
            $role = is_array($role) ? array_filter( $role ) : $role;
            if( is_user_logged_in() && $role ){
                $user = wp_get_current_user();
                if( count( array_intersect($role, $user->roles) ) > 0 ){
                    $preview_length = false;
                }
            }
        }else{
            $preview_length = false;
        }
        return apply_filters('play_preview_length', $preview_length, $id);
    }

    public function get_data($id){
        $type = get_post_meta( $id, 'type', true );
        $data = [];

        if ( in_array($type, ['album', 'playlist']) ) {
            $posts  = get_post_meta( $id, 'post', true );
            $posts = explode( ',', $posts );
            foreach ( $posts as $key => $post_id ) {
                $item = $this->get_play_item( $post_id, $id );
                if ( $item ) {
                    $data[] = $item;
                }
            }
        } else {
            $data[] = $this->get_play_item( $id );
        }
        return $data;
    }

    public function stream_url($url, $id) {
        if ( strpos( $url, 'icecast' ) !== false || strpos( $url, 'shoutcast' ) !== false || strpos( $url, 'azuracast' ) !== false || strpos( $url, 'livestream') !== false ) {
            return $url;
        }
        $uri = Play_Utils::instance()->fixURL( $url );
        if($uri){
            $permalink = get_permalink($id);
            if ( get_option( 'permalink_structure' ) ) {
                $url = trailingslashit( $permalink ) . $this->endpoint;
            } else {
                $url = add_query_arg( $this->endpoint, '', $permalink );
            }
            
            // add preview label
            $preview = $this->get_preview($id);
            if($preview){
                $url = add_query_arg( 'preview', true, $url );
            }
        }
        return $url;
    }

    public function play_stream(){
        global $wp_query;
        if ( ! isset( $wp_query->query_vars[ $this->endpoint ] ) || isset( $_REQUEST[$this->endpoint] ) || ! is_singular() ) {
            return;
        }
        $id   = get_the_ID();
        $url  = get_post_meta( $id, 'stream', true );
        $_url = get_post_meta( $id, 'stream_url', true );
        if ( ! empty( $_url ) ) {
            $url = $_url;
        }

        $path = Play_Utils::instance()->getPath( $url );
        $path = apply_filters('play_block_stream_file_path', $path);

        if(!file_exists($path)){
            header('location: '.$url);
            exit;
        }

        $preview_length = $this->get_preview($id);

        $this->start($path, $preview_length );
    }

    public function stream_metadata($request){
        if(empty($request[ 'url' ])){
            return;
        }
        $url = $request[ 'url' ];
        $ua = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.110 Safari/537.36';
        $icy_metaint = -1;
        $needle = 'StreamTitle=';
        $songTitle = '';
        $opts = array(
            'http' => array(
                'method'     => 'GET',
                'header'     => 'Icy-MetaData: 1',
                'user_agent' => $ua,
            ),
            "ssl"  => array(
                'allow_self_signed' => true,
                "verify_peer"       => false,
                "verify_peer_name"  => false,
            ),
        );
        $default = stream_context_set_default( $opts );
        if ( ($stream = @fopen( $url, 'r' )) ) {
            if ( $stream && ($meta_data = stream_get_meta_data( $stream )) && isset( $meta_data['wrapper_data'] ) ) {
                foreach ( $meta_data['wrapper_data'] as $header ) {
                    if ( strpos( strtolower( $header ), 'icy-metaint' ) !== false ) {
                        $tmp = explode( ":", $header );
                        $icy_metaint = trim( $tmp[1] );
                        break;
                    }
                }
                if ( $icy_metaint != -1 ) {
                    $buffer = stream_get_contents( $stream, 300, $icy_metaint );
                    if ( strpos( $buffer, $needle ) !== false ) {
                        $title = explode( $needle, $buffer );
                        $title = trim( $title[1] );
                        if ( $title !== '' ) {
                            $songTitle = substr( $title, 1, strpos( $title, ';' ) - 2 );
                        }
                    }
                }
                if ( $stream ) {
                    fclose( $stream );
                }
            }
        }
        return Play_Utils::instance()->response( array('title' => $songTitle) );
    }

    public function stream_proxy($request){
        if(empty($request[ 'url' ])){
            return;
        }
        set_time_limit(0);
        $url = $request[ 'url' ];
        $context = NULL;
        $handle = fopen($url, 'rb', false, $context);
        if ($handle === false)
            return false;
            header('Content-Type: audio/mpeg');
        while (!feof($handle)) {
            echo fread($handle, $this->stream_chunk_size);
            ob_flush();
            flush();
        }
        return fclose($handle); 
    }

    // open connect
    private function open() {
        if (!($this->stream = fopen($this->path, 'rb', false, stream_context_create()))) {
            die('Could not open stream for reading');
        }
    }
    
    private function setHeader() {
        ob_get_clean();
        header("Content-Type: ". $this->type);
        header("Cache-Control: max-age=2592000, public");
        header("Expires: ".gmdate('D, d M Y H:i:s', time()+2592000) . ' GMT');
        header("Last-Modified: ".gmdate('D, d M Y H:i:s', @filemtime($this->path)) . ' GMT' );
        $this->start = 0;
        $this->size  = filesize($this->path);
        $this->end   = $this->size - 1;

        // Stream preview
        if($this->preview){
            require_once( ABSPATH . 'wp-admin/includes/media.php' );
            $data = wp_read_audio_metadata($this->path);
            if(isset($data['length']) && $this->preview < $data['length'] ){
                $this->size = intval( intval($this->preview) / $data['length'] * $this->size );
                $this->end = $this->size - 1;
                header("Content-Length: " . $this->size);
                //return;
            }
        }

        header("Accept-Ranges: 0-" . $this->end);
        
        if (isset($_SERVER['HTTP_RANGE'])) {
  
            $c_start = $this->start;
            $c_end = $this->end;
 
            list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
            if (strpos($range, ',') !== false) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $this->start-$this->end/$this->size");
                exit;
            }
            if ($range == '-') {
                $c_start = $this->size - substr($range, 1);
            }else{
                $range = explode('-', $range);
                $c_start = $range[0];
                 
                $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $c_end;
            }
            $c_end = ($c_end > $this->end) ? $this->end : $c_end;
            if ($c_start > $c_end || $c_start > $this->size - 1 || $c_end >= $this->size) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $this->start-$this->end/$this->size");
                exit;
            }
            $this->start = $c_start;
            $this->end = $c_end;
            $length = $this->end - $this->start + 1;
            fseek($this->stream, $this->start);
            header('HTTP/1.1 206 Partial Content');
            header("Content-Length: ".$length);
            header("Content-Range: bytes $this->start-$this->end/".$this->size);
        }
        else
        {
            header("Content-Length: ".$this->size);
        }
    }
     
    private function stream() {
        $i = $this->start;
        set_time_limit(0);
        while(!feof($this->stream) && $i <= $this->end && connection_aborted() == 0) {
            $bytesToRead = $this->buffer;
            if(($i+$bytesToRead) > $this->end) {
                $bytesToRead = $this->end - $i + 1;
            }
            $data = stream_get_contents($this->stream, $bytesToRead);
            echo $data;
            flush();
            $i += $bytesToRead;
        }
    }

    private function end() {
        fclose($this->stream);
        exit;
    }
    
    public function start($filePath, $preview = false) {
        $filetype = wp_check_filetype($filePath);
        $this->type = $filetype['type'];
        $this->preview = $preview;
        $this->path = $filePath;

        session_write_close();
        $this->open();
        $this->setHeader();
        $this->stream();
        $this->end();
    }
}

Play_Stream::instance();
