<?php

/*
Plugin Name: Oznamy
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: A brief description of the Plugin.
Version: 1.0
Author: martin
Author URI: http://URI_Of_The_Plugin_Author
License: A "Slug" license name e.g. GPL2
*/


function announcements_add_menu_item() {
	require_once (plugin_dir_path(__FILE__) . "announcements-settings.php");
	require_once (plugin_dir_path(__FILE__) . "announcements-permissions.php");
	//Original icon 'dashicons-editor-table'
	add_menu_page("Oznamy", "Oznamy", "upload_files",
		"announcements-settings", "announcements_settings_page", 'dashicons-welcome-write-blog', 4);
	
	// Setings submenu
	add_submenu_page("announcements-settings", "Nastavenia zobrazenia", "Nastavenia zobrazenia", "manage_categories",
		"annoncement-subsettings", "announcements_subsettings_page");
	
	// Permissions submenu
	add_submenu_page("announcements-settings", "Nastavenia práv", "Nastavenia práv", "manage_categories",
		"annoncement-permissions", "announcements_permissions_page");
}

add_action('admin_menu','announcements_add_menu_item');


function announcements_create_db_tables() {
	
	global $wpdb;
	
	
	$table_name = $wpdb->prefix . 'announcements_categories';
	$charset_collate = $wpdb->get_charset_collate();
	$sql_cat = "CREATE TABLE IF NOT EXISTS $table_name (
	id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
	name VARCHAR(255) NOT NULL,
	UNIQUE KEY(id))$charset_collate";
	
	
	$table_name = $wpdb->prefix . 'announcements';
	$charset_collate = $wpdb->get_charset_collate();
	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
	id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
	announcement_text VARCHAR(1000) NOT NULL,
	date_created DATETIME NOT NULL,
	date_updated DATETIME,
	start_date DATETIME,
	end_date DATETIME,
	category_id MEDIUMINT(9) NOT NULL REFERENCES {$wpdb->prefix}announcements_categories(id),
	UNIQUE KEY(id))$charset_collate";
	
	$table_name = $wpdb->prefix . 'announcements_permissions';
	$charset_collate = $wpdb->get_charset_collate();
	$sql_perm = "CREATE TABLE IF NOT EXISTS $table_name (
	id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
	user_id BIGINT(20) UNSIGNED NOT NULL REFERENCES {$wpdb->prefix}users(ID),
	announcement_id MEDIUMINT(9) NOT NULL REFERENCES {$wpdb->prefix}announcements(id),
	UNIQUE KEY(id))$charset_collate";
	
	require( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta($sql_cat);
	dbDelta($sql);
	dbDelta($sql_perm);
	
}

register_activation_hook( __FILE__, 'announcements_create_db_tables' );


function announcements_enqueue_admin_styles() {
	wp_register_style( 'announcements_admin_css', plugin_dir_url(__FILE__) . 'styles/announcements-admin.css', false, '1.0.0' );
	wp_enqueue_style( 'announcements_admin_css' );
	wp_enqueue_script( 'jquery-ui-datepicker' );
	wp_enqueue_script('announcement-js', plugin_dir_url(__FILE__) . 'js/datepicker.js',array(),true);
}


require_once( plugin_dir_path(__FILE__) . "Announcement_Widget.php");
add_action( 'widgets_init', function(){
	register_widget( 'Announcement_Widget' );
});
