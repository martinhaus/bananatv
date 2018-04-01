<?php

/*
Plugin Name: Wpcloudy  Box
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: A brief description of the Plugin.
Version: 1.0
Author: martin
Author URI: http://URI_Of_The_Plugin_Author
License: A "Slug" license name e.g. GPL2
*/

class WP_cloudy_box extends WP_Widget {
	
	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {
		$widget_ops = array(
			'classname' => 'WP Cloudy',
			'description' => 'Place to display weather',
		);
		parent::__construct( 'wp_clpudy_box', 'WP Cloudy', $widget_ops );
	}
	
	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		// outputs the content of the widget
		echo do_shortcode('[wpc-weather id="13"]');
	}
	
	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form( $instance ) {
		// outputs the options form on admin
	}
	
	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 */
	public function update( $new_instance, $old_instance ) {
		// processes widget options to be saved
	}
}

add_action( 'widgets_init', function(){
	register_widget( 'WP_cloudy_box' );
});