<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 2.10.2017
 * Time: 9:39
 */

/*
Plugin Name: Reklamný blok
Plugin URI: https://github.com/martinhaus/bananatv
Description: Displaying partner adds in specific time intervals
Version: 1.0
Author: Martin Hauskrecht
Author URI: hauskrecht.sk
License: MIT
*/

function fiit_ad_add_menu_item() {
	require_once (plugin_dir_path(__FILE__) . "fiit-ad-admin.php");
	//Original icon 'dashicons-editor-table'
	add_menu_page("Reklamy", "Reklamy", "manage_categories",
		"fiit-ad-admin", "fiit_ad_admin_page", 'dashicons-welcome-write-blog', 4);
}

function fiit_ad_create_db_tables() {
	
	global $wpdb;
	
	$sql_companies = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}fiit_ad_companies (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			name varchar(50) NOT NULL,
			even_week BOOLEAN NOT NULL,
			consecutive_number SMALLINT NOT NULL,
			UNIQUE KEY (id)){$wpdb->get_charset_collate()}";
	
	$sql_companies_ads = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}fiit_ad_company_ads(
							id BIGINT(20) NOT NULL AUTO_INCREMENT,
							comp_id BIGINT(20) NOT NULL,
							ad_page_id BIGINT(20)UNSIGNED NOT NULL,
							poster_url TEXT NOT NULL,
							PRIMARY KEY(id),
							  FOREIGN KEY (ad_page_id) REFERENCES {$wpdb->prefix}posts(id) ON DELETE CASCADE,
							FOREIGN KEY (comp_id) REFERENCES {$wpdb->prefix}fiit_ad_companies(id) ON DELETE CASCADE
							){$wpdb->get_charset_collate()}";

	$sql_log = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}fiit_ad_log (
                  id BIGINT(20) AUTO_INCREMENT,
                  screen_id BIGINT(20) NOT NULL REFERENCES {$wpdb->prefix}page_rotation_screens(id),
                  company_id BIGINT(20) NOT NULL REFERENCES {$wpdb->prefix}fiit_ad_companies(id),
                  date_created TIMESTAMP NOT NULL DEFAULT NOW(),
                  UNIQUE KEY (id)){$wpdb->get_charset_collate()}";

	

	require( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta($sql_companies);
	dbDelta($sql_companies_ads);
	dbDelta($sql_log);
}

function fiit_ad_create_ad_category() {
	wp_create_category('Partnerské reklamy');
}

function fiit_ad_create_main_ad_page() {
	global $wpdb;
	
	// Get number of main ad pages already existing
	$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}postmeta WHERE meta_key LIKE 'main_ad_page'";
	
	// Only create a new page when there is none existing
	if ($wpdb->get_var($sql) == 0) {
		$post_id = wp_insert_post(array(
			'post_title' => 'Reklamný blok',
			'post_status' => 'publish',
			'post_type' => 'page',
			'meta_input' => array(
				'main_ad_page' => true
			)
		));
	}
}

function fiit_ad_check_if_ad() {
	global $post;
	$meta = get_post_meta($post->ID,'is_ad',true);
	if($meta) {
		$image_url = get_post_meta( $post->ID, 'ad_url', true );
		?>
		<style type="text/css">
			body {
				background-image: url(<?php echo $image_url ?>);
				background-size:cover;
			}
			.site-footer {
				display: none;
			}
		</style>
		<?php
	}
}

add_action('admin_menu','fiit_ad_add_menu_item');

register_activation_hook( __FILE__, 'fiit_ad_create_db_tables' );
register_activation_hook( __FILE__, 'fiit_ad_create_ad_category' );
register_activation_hook( __FILE__, 'fiit_ad_create_main_ad_page' );

add_action('wp_head','fiit_ad_check_if_ad');