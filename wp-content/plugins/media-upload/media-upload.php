<?php

/*
Plugin Name: Media Upload
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: A brief description of the Plugin.
Version: 1.0
Author: martin
Author URI: http://URI_Of_The_Plugin_Author
License: A "Slug" license name e.g. GPL2
*/


/**s
 * Adds admin page to the left admin menu
 */
function media_upload_add_menu_item() {
	require( plugin_dir_path( __FILE__ ) . "media-upload-admin.php");
	add_menu_page("Plagáty", "Plagáty", "manage_categories",
		"media-admin", "media_admin_page", 'dashicons-format-gallery', 3);
	
	//Submenu for iframe creatiion
	add_submenu_page("media-admin","Pridanie iframe","Pridanie iframe",
        "manage_categories","media-admin-iframe","media_upload_iframe_admin_page");
}


/**
 * Create category for posters and iframes
 */
function media_upload_create_category() {
	$name = "Plagáty";
	wp_create_category( $name );
	
	$name = "Externý obsah";
    wp_create_category( $name );
}

/**
 * Checks if page is a poster and outputs CSS property to set background
 */
function media_upload_check_if_poster() {
	
	global $post;
	$meta = get_post_meta($post->ID,'is_poster',true);
	if($meta) {
		$image_url = get_post_meta( $post->ID, 'poster_url', true );
		
		$date_start = get_post_meta( $post->ID, 'poster_start_date', true );
		
        // Check if poster is before start time, if true don't display the poster (only in sequence)
		if (time() - strtotime( $date_start ) < 0 && $date_start > 0 && isset($_GET['seq'])) {
			$image_url = "";
		}
		
		?>
			<style type="text/css">
				body {
					background-image: url(<?php echo $image_url ?>)!important;
                    background-size:cover;
				}
				.site-footer {
					display: none;
				}
			</style>
		<?php
	}
}

function media_upload_check_if_iframe() {
    
    global $post;
    $meta = get_post_meta($post->ID,'is_iframe',true);
    if($meta) {
        $url = get_post_meta($post->ID,'iframe_url',true);
        $refresh_rate = get_post_meta($post->ID,'iframe_refresh_rate',true);
        
        //Output refresh tag
	    echo "<meta http-equiv=\"refresh\" content=\"$refresh_rate; \">";
	    
        ?>

        <iframe src="<?php echo $url ?>" style="border: 0; width: 100%; height: 100%; position:absolute;">Your browser doesn't support iFrames.</iframe>
        <style type="text/css">
            body {
                margin: 0 !important;
                background: none;
            }
            .site {
                background: none !important;
            }
        </style>
    
        <?php
    }
    
}

//Loads jQuery
function setup_jquery_scripts() {
	wp_enqueue_script('jquery');
	wp_enqueue_script('jquery-ui-sortable');
	wp_enqueue_script('jquery-ui-dialog');
	
	wp_enqueue_script( 'jquery-ui-datepicker' );
	
	// You need styling for the datepicker. For simplicity I've linked to Google's hosted jQuery UI CSS.
	wp_register_style( 'jquery-ui', 'http://code.jquery.com/ui/1.11.2/themes/smoothness/jquery-ui.css' );
	wp_enqueue_style( 'jquery-ui' );
}

/**
 * Hooks
 */
add_action("admin_menu","media_upload_add_menu_item");
register_activation_hook( __FILE__, 'media_upload_create_category' );

add_action('wp_head','media_upload_check_if_poster');
add_action('wp_head','media_upload_check_if_iframe');
add_action('admin_enqueue_scripts', 'setup_jquery_scripts');


function media_upload_check_num_of_pages() {
	$pdf_url = $_GET['pdf'];

    $path = wp_upload_dir()['path'] . '/';
    $filename = pathinfo($pdf_url);
    $file = $path .  $filename['basename'];

    if (! is_readable($file)) {
        echo 'file not readable';
        exit();
    }


	$img = new imagick();
	$img->readImage($file);
	$num_of_pages = $img->getNumberImages();
	echo json_encode($num_of_pages);
	die();
	
}
add_action('wp_ajax_media_upload_check_num_of_pages', 'media_upload_check_num_of_pages');
add_action('wp_ajax_nopriv_media_upload_check_num_of_pages', 'media_upload_check_num_of_pages');