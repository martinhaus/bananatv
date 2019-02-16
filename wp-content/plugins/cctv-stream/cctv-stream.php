<?php
/*
Plugin Name: Záznam z kamery
Plugin URI: https://github.com/martinhaus/bananatv
Description: Displaying static CCTV images
Version: 1.0
Author: Martin Hauskrecht
Author URI: hauskrecht.sk
License: MIT
*/


/**
 * Created by PhpStorm.
 * User: martin
 * Date: 12.3.2017
 * Time: 1:04
 */

/**
 * Add widget
 */


require_once( plugin_dir_path(__FILE__) . "CCTVWidget.php");
add_action( 'widgets_init', function(){
	register_widget( 'CCTVWidget' );
});

function cctv_create_upload_folder() {
	$path = wp_upload_dir()['basedir'] . "/camera";
	wp_mkdir_p( $path );
            
}
register_activation_hook( __FILE__, 'cctv_create_upload_folder' );