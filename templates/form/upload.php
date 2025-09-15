<?php
/**
 * Upload Form
 *
 * This template can be overridden by copying it to yourtheme/templates/form/upload.php.
 *
 * HOWEVER, on occasion we will need to update template files and
 * you will need to copy the new files to your theme to maintain compatibility.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
do_action( 'before_upload_form' );
?>
<form class="form form-validate" name="upload" id="upload" action method="post" enctype="multipart/form-data">
	<div class="form-message"><?php do_action('template_notices'); ?></div>
	<div class="flex-row">
		<!-- Thumbnail -->
	    <div class="form-upload-thumbnail">
	    	<label><?php play_get_text('poster', true); ?> <span class="required">*</span></label>
	        <div class="file-upload" style="width: 15rem">
	            <input type="file" name="image" accept="image/*" <?php echo esc_attr( $post->ID ? '' : 'required') ?> />
	            <div class="post-thumbnail rounded"><img width="240" height="240" src="<?php echo esc_attr($post->ID > 0 ? get_the_post_thumbnail_url($post->ID) : '') ?>"></div>
	        </div>
	    </div>
	    <div class="sep"></div>
	    <div class="form-upload-info">
	        <?php do_action( 'upload_form_top', $post ); ?>

	        <!-- Title -->
	        <p class="form-upload-title">
	            <label><?php play_get_text('title', true); ?> <span class="required">*</span></label>
	            <input type="text" name="title" class="input" size="20" value="<?php echo esc_attr($post->post_title); ?>" required />
	        </p>

	        <!-- File -->
	        <?php if( $post->type == 'single' ){ ?>
	        <div class="form-upload-media">
	            <label><?php play_get_text('stream', true); ?> <span class="required">*</span></label>
	            <div class="file-upload-wrap">
		            <input type="text" name="stream" class="input" placeholder="http://" required value="<?php echo esc_attr( get_post_meta($post->ID, 'stream', true) ); ?>" />
		            <?php if( $user_can_upload_stream ){ ?>
		            <div class="file-upload">
		            	<input type="file" name="upload_file" data-type="single" accept="video/mp4,video/x-m4v,video/*,audio/mp3,audio/x-m4a,audio/*," />
		            	<span class="progress"><span class="progress-bar"></span></span>
		            	<button class="input"><?php play_get_text('upload', true); ?></button>
		            </div>
		        	<?php }?>
	            </div>
	        </div>
	        <?php }?>

	        <div class="flex-row">
	        	<!-- Cat -->
	        	<?php if(!empty(trim(play_get_option('upload_cat')))){
	            	$cat = get_taxonomy( play_get_option('upload_cat') );
	            	$cats = wp_get_post_terms($post->ID, $cat->name, array("fields" => "ids"));
	            	if(is_wp_error($cats)){
			            $cats = [];
			        }
		            ?>
		            <p class="form-upload-genre">
		                <label><?php echo esc_html($cat->labels->singular_name);?></label>
		                <?php
							$arg = array(
		                        'taxonomy' => $cat->name,
		                        'hide_empty' => false,
		                        'hierarchical' => true,
		                        'name' => 'cat',
		                        'class' => 'input',
		                        'selected' => join( ',', $cats )
		                    );
							$arg = apply_filters('play_block_form_upload_categories', $arg);
		                	wp_dropdown_categories( $arg );
		                ?>
		            </p>
	            <?php } ?>
	            <div class="sep"></div>

	            <!-- Type -->
            	<?php if( in_array($post->type, array('playlist', 'album', 'series')) ){ ?>
            		<p class="form-upload-type">
                	<label><?php play_get_text('type', true); ?></label>
                	<select name="type" class="input">
				        <?php
				        $types = array( 'playlist' => play_get_text('playlist') );
				        // upload to album post type
				        if($user_can_upload && in_array('album', play_get_block_types())){
					       $types['album'] = play_get_text('album');
				        }
				        // upload to podcast post type
				        if($user_can_upload && in_array('series', play_get_block_types())){
					       $types['series'] = play_get_text('series');
				        }
				        $types = apply_filters('play_upload_form_types', $types, $post);
				        foreach ( $types  as $k => $v ) {
				           echo '<option value="'.$k .'" '.selected( $k, $post->type, false ).'>'.$v.'</option>';
				        }
				        ?>
				    </select>
				    </p>
                <?php } else { ?>

                <!-- Duration -->
                	<p class="form-upload-duration">
                	<label><?php play_get_text('duration', true); ?></label>
                	<input type="text" name="duration" class="input" value="<?php echo esc_attr( Play_Utils::instance()->duration( (int)get_post_meta($post->ID, 'duration', true)/1000, '', true ) ); ?>" />
               		</p>
                <?php } ?>
	            <div class="sep"></div>

	            <!-- Date -->
	            <p class="form-upload-date">
	                <label><?php play_get_text('release-date', true); ?></label>
	                <input type="date" name="post_date" class="input" value="<?php echo esc_attr( date("Y-m-d", strtotime($post->post_date)) ); ?>" />
	            </p>
            </div>

            <!-- Tags -->
            <?php if(!empty(trim(play_get_option('upload_tag')))){
            	$tag = get_taxonomy( play_get_option('upload_tag') );
            	$tags = wp_get_post_terms($post->ID, $tag->name, array("fields" => "names"));
            	if(is_wp_error($tags)){
		            $tags = [];
		        }
	            ?>
	            <p class="form-upload-tag">
	                <label><?php echo esc_html($tag->labels->singular_name);?> <?php play_get_text('comma', true); ?></label>
	                <input type="text" name="tag" class="input" value="<?php echo esc_attr( join(',', $tags) ); ?>">
	            </p>
            <?php } ?>

            <!-- Taxonomy (artist) -->
            <?php if(!empty(trim(play_get_option('upload_tax')))){
            	$taxonomy = get_taxonomy( play_get_option('upload_tax') );
            	$taxonomies = wp_get_post_terms($post->ID, $taxonomy->name, array("fields" => "names"));
            	if(is_wp_error($taxonomies)){
		            $taxonomies = [];
		        }
	            ?>
		        <p class="form-upload-tax">
	                <label><?php echo esc_html($taxonomy->labels->singular_name);?> <?php play_get_text('comma', true); ?></label>
	                <input type="text" name="tax" class="input" value="<?php echo esc_attr( join(',', $taxonomies) ); ?>">
	            </p>
        	<?php } ?>

        	<!-- Content -->
	        <p class="form-upload-content">
	            <label><?php play_get_text('content', true); ?></label>
	            <textarea name="content" class="input" rows="4" /><?php echo wp_kses_post( $post->post_content ); ?></textarea>
	        </p>

	        <!-- Price for WOO -->
	        <?php if( $post->post_type == 'product' && class_exists( 'WooCommerce' ) && $user_can_upload ){ ?>
	        	<div class="flex-row form-upload-price">
		        	<p>
			            <label><?php play_get_text('regular-price', true); ?> (<?php esc_html_e( get_woocommerce_currency_symbol() ); ?>)</label>
			            <input type="text" name="_regular_price" class="input" size="20" value="<?php echo esc_attr(get_post_meta($post->ID, '_regular_price', true)); ?>" />
			        </p>
			        <div class="sep"></div>
		            <p>
		                <label><?php play_get_text('sale-price', true); ?> (<?php esc_html_e( get_woocommerce_currency_symbol() ); ?>)</label>
			            <input type="text" name="_sale_price" class="input" size="20" value="<?php echo esc_attr(get_post_meta($post->ID, '_sale_price', true)); ?>" />
		            </p>
		        </div>
	        <?php }?>

	        <!-- Price for EDD -->
	        <?php if( $post->post_type == 'download' && class_exists( 'Easy_Digital_Downloads' ) && $user_can_upload ){ ?>
				<div class="flex-row form-upload-download">
					<p>
						<label><?php play_get_text('regular-price', true); ?> (<?php esc_html_e( edd_currency_filter( '' ) ); ?>)</label>
						<input type="text" name="_regular_price" class="input" size="20" value="<?php echo esc_attr(get_post_meta($post->ID, 'edd_price', true)); ?>" />
					</p>
					<div class="sep"></div>
					<p>
						<label><?php play_get_text('sale-price', true); ?> (<?php esc_html_e( edd_currency_filter( '' ) ); ?>)</label>
						<input type="text" name="_sale_price" class="input" size="20" value="<?php echo esc_attr(get_post_meta($post->ID, 'edd_sale_price', true)); ?>" />
					</p>
				</div>
			<?php }?>

			<!-- Purchase -->
	        <?php if( play_get_option('purchaseable') && $user_can_upload ){ ?>
	        <div class="flex-row form-upload-purchase">
		        <p>
		            <label><?php play_get_text('purchase-title', true); ?></label>
		            <input type="text" name="purchase_title" class="input" value="<?php echo esc_attr( get_post_meta($post->ID, 'purchase_title', true) ); ?>" />
		        </p>
		        <div class="sep"></div>
		        <p>
		            <label><?php play_get_text('purchase-url', true); ?></label>
		            <input type="text" name="purchase_url" class="input" placeholder="http://" value="<?php echo esc_url( get_post_meta($post->ID, 'purchase_url', true) ); ?>" />
		        </p>
		    </div>
	        <?php } ?>

	        <!-- Copyright -->
	        <p class="form-upload-copyright">
	            <label><?php play_get_text('copyright', true); ?></label>
	            <input type="text" name="copyright" class="input" value="<?php echo esc_attr( get_post_meta($post->ID, 'copyright', true) ); ?>" />
	        </p>

	        <!-- Download -->
	        <?php if( apply_filters('play_block_form_upload_downloadable', true) && $user_can_upload ){ ?>
	        <div class="checkable form-upload-download">
	        	<?php 
	        		$downloadable = apply_filters('play_upload_form_downloadable', false, $post);
	        		if(get_post_meta($post->ID, 'downloadable', true)){
	        			$downloadable = true;
	        		}
	        	?>
	            <input type="checkbox" name="downloadable" value="1" id="downloadable" <?php echo ( $downloadable ? 'checked="checked"' : ''); ?> /> 
	            <div class="flex">
	            	<label for="downloadable"><?php play_get_text('downloadable', true); ?></label>
	            	<div class="hide" style="display: none;">
		            	<div class="file-upload-wrap">
			            	<input type="text" name="download_url" class="input" placeholder="http://" value="<?php echo esc_attr( get_post_meta($post->ID, 'download_url', true) ); ?>" />
			            	<?php if( $user_can_upload_stream ){ ?>
			            	<div class="file-upload">
				            	<input type="file" name="upload_file" />
				            	<span class="progress"><span class="progress-bar"></span></span>
				            	<button class="input"><span class="progress"></span> <?php play_get_text('upload', true); ?></button>
				            </div>
				        	<?php } ?>
			            </div>
		            </div>
	            </div>
	        </div>
	    	<?php } ?>
	        
	        <!-- Public -->
	        <?php if($user_can_post_public){ ?>
	        <div class="checkable form-upload-publish">
	            <input type="radio" name="post_status" id="public" value="publish" <?php echo ($post->post_status == 'publish' ? 'checked="checked"' : ''); ?> />
	            <div>
	            	<label for="public"><?php play_get_text('public', true); ?></label>
	            	<span class="hide" style="display:none"><?php play_get_text('public-tip', true); ?></span>
	            </div>
	        </div>
	        <?php } ?>
	        <!-- Private -->
	        <div class="checkable form-upload-private">
	            <input type="radio" name="post_status" id="private" value="private" <?php echo ($post->post_status == 'private' ? 'checked="checked"' : ''); ?> />
	            <div>
	            	<label for="private"><?php play_get_text('private', true); ?></label>
	            	<span class="hide" style="display:none"><?php play_get_text('private-tip', true); ?></span>
	            </div>
	        </div>

	        <?php do_action( 'upload_form_middle', $post ); ?>
	    </div>
	</div>

	<!-- Tracks -->
	<div class="tracks">
        <?php if( in_array($post->type, ['album','playlist','series']) ){ ?>
        	<?php $posts = get_post_meta($post->ID, 'post', true); 
        	$query = array(
	          'post_type' => 'any',
	          'post_status' => 'any',
	          'posts_per_page' => -1,
	          'post__in' => explode(',', $posts),
	          'orderby' => 'post__in'
	        );
	        $items = get_posts( $query );
	        $list = '';
	        foreach ($items as $key => $item) {
	            $list .= sprintf( '<li id="%d" class="input"><span class="handle"></span><span class="track-list-title">%s</span><span class="remove">&times;</span></li>', esc_attr($item->ID), esc_html($item->post_title) );
	        }
        	?>
        	<label><?php play_get_text('tracks', true); ?></label>
        	<ul class="track-list"><?php echo $list; ?></ul>
        	<input type="hidden" name="post" value="<?php echo esc_attr($posts); ?>">
        <?php }?>
    </div>

    <p class="form-action">
    	<span class="file-uploading"><?php play_get_text('uploading-files', true); ?></span>
    	<span class="file-uploaded"><?php play_get_text('files-uploaded', true); ?></span>
    	<span class="sep-1"></span>
    	<button class="button-link" data-dismiss="modal"><?php play_get_text('cancel', true); ?></button>
        <input type="submit" name="wp-submit" class="button button-primary" value="<?php play_get_text('save', true); ?>" />
        <input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect ); ?>" />
        <input type="hidden" name="post_id" value="<?php echo esc_attr($post->ID); ?>" />
        <input type="hidden" name="post_type" value="<?php echo esc_attr($post->post_type); ?>" />
        <input type="hidden" name="action" value="frontend-upload" />
    </p>
    <?php do_action( 'upload_form_bottom', $post ); ?>
</form>
<?php do_action( 'after_upload_form' ); ?>
