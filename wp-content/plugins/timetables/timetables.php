<?php

/*
Plugin Name: Rozvrhy
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: A brief description of the Plugin.
Version: 1.0
Author: Martin Hauskrecht
Author URI: http://URI_Of_The_Plugin_Author
License: A "Slug" license name e.g. GPL2
*/

/**
 * Adds settings page to the left admin menu
 */
function timetables_add_menu_item() {
    require( plugin_dir_path( __FILE__ ) . "/timetables-settings.php");
	add_menu_page("Rozvrhy", "Rozvrhy", "manage_categories",
		"timetables-settings", "timetables_settings_login_page", 'dashicons-welcome-learn-more', 4);
}

/**
 * Creates required tables in the wordpress database
 */
function timetables_create_db() {
	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();
	$table_name = $wpdb->prefix . "timetables_timetables";

	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
  id mediumint(9) NOT NULL AUTO_INCREMENT,
  term varchar(20) NOT NULL,	
  room varchar(55) DEFAULT '' NOT NULL,
  created TIMESTAMP DEFAULT '0000-00-00 00:00:00' NOT NULL,
  UNIQUE KEY id (id)
) $charset_collate;";

	require( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	$table_name = $wpdb->prefix . "timetables_lessons";
	$refer = $wpdb->prefix ."timetables_timetables";
	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
  id mediumint(9) NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  start_time TIMESTAMP DEFAULT '0000-00-00 00:00:00' NOT NULL,
  end_time TIMESTAMP DEFAULT '0000-00-00 00:00:00' NOT NULL,
  day_of_week tinyint DEFAULT '1' NOT NULL,	
  teacher varchar(55) DEFAULT '' NOT NULL,
  timetable_id mediumint(9) NOT NULL,
  FOREIGN KEY (timetable_id) REFERENCES $refer(id) ON DELETE CASCADE ,
  UNIQUE KEY id (id)
) $charset_collate;";

	dbDelta( $sql );
}




/**
 * Hooks
 */
add_action("admin_menu", "timetables_add_menu_item");
register_activation_hook( __FILE__, 'timetables_create_db' );

require_once( plugin_dir_path(__FILE__) . "widget.php");
add_action( 'widgets_init', function(){
	register_widget( 'timetable_widget' );
});

function timetables_enqueue_admin_styles() {
	wp_register_style( 'timetables_admin_css', plugin_dir_url(__FILE__) . 'css/timetables_settings.css', false, '1.0.0' );
	wp_enqueue_style( 'timetables_admin_css' );
}

/**
 * Add WP cron evenet for updating DB
 */
//On plugin activation schedule our daily database backup
register_activation_hook( __FILE__, 'timetables_create_weekly_update_schedule' );

function timetables_create_weekly_update_schedule(){
	//Use wp_next_scheduled to check if the event is already scheduled
	$timestamp = wp_next_scheduled( 'timetables_week_update' );
	
	//If $timestamp == false schedule daily backups since it hasn't been done previously
	if( $timestamp == false ){
		//Schedule the event for right now, then to repeat daily using the hook 'wi_create_daily_backup'
		wp_schedule_event( strtotime(date('Y-m-d', strtotime(' Sunday'))), 'weekly', 'mhd_week_update' );
	}
}

add_action( 'timetables_week_update', 'timetables_update_all_timetables' );
