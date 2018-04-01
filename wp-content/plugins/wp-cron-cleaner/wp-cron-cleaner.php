<?php
if (!defined('ABSPATH')) return;
if (!is_main_site()) return;
/*
Plugin Name: WP Cron Cleaner
Plugin URI: http://sigmaplugin.com/downloads/wp-cron-cleaner
Description: View the list of all your cron scheduled tasks, then clean what you want.
Version: 1.0.0
Author: Younes JFR.
Author URI: http://www.sigmaplugin.com
Contributors: symptote
Text Domain: wp-cron-cleaner
Domain Path: /languages/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

/********************************************************************
* Require WordPress List Table Administration API
* xxx: Test validity of WP_List_Table class after each release of WP.
* Notice from Wordpress.org:
* Since this class is marked as private, developers should use this only at their own risk as this class is
* subject to change in future WordPress releases. Any developers using this class are strongly encouraged to
* test their plugins with all WordPress beta/RC releases to maintain compatibility. 
********************************************************************/
if(!class_exists('WP_List_Table')){
	if(file_exists(ABSPATH . 'wp-admin/includes/class-wp-list-table.php')){
		require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
	}else{
		return;
	}
}

/********************************************************************
*
* Define common constants
*
********************************************************************/
if (!defined("WPCC_PLUGIN_VERSION")) 				define("WPCC_PLUGIN_VERSION", "1.0.0");
if (!defined("WPCC_PLUGIN_DIR_PATH")) 				define("WPCC_PLUGIN_DIR_PATH", plugins_url('' , __FILE__));

/********************************************************************
*
* load language
*
********************************************************************/
add_action('plugins_loaded', 'WPCC_load_textdomain');
function WPCC_load_textdomain() {
	load_plugin_textdomain('wp-cron-cleaner', false, plugin_basename(dirname(__FILE__)) . '/languages');
}

/********************************************************************
*
* Add menu
*
********************************************************************/
add_action('admin_menu', 'WPCC_add_admin_menu');
function WPCC_add_admin_menu() {
	global $WPCC_tool_submenu;
	$WPCC_tool_submenu = add_submenu_page('tools.php', 'WP Cron Cleaner', 'WP Cron Cleaner', 'manage_options', 'wp_cron_cleaner', 'WPCC_main_page_callback');
}

/********************************************************************
*
* Load CSS and JS
*
********************************************************************/
add_action('admin_enqueue_scripts', 'WPCC_load_styles_and_scripts');
function WPCC_load_styles_and_scripts($hook) {
	// Enqueue our js and css in the plugin pages only
	global $WPCC_tool_submenu;
	if($hook != $WPCC_tool_submenu){
		return;
	}
	wp_enqueue_style('WPCC_css', WPCC_PLUGIN_DIR_PATH . '/css/admin.css');
	wp_enqueue_script('WPCC_js', WPCC_PLUGIN_DIR_PATH . '/js/admin.js');
    //wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-dialog');
	wp_enqueue_style('wp-jquery-ui-dialog');
}

/********************************************************************
*
* Plugin activation
*
********************************************************************/
register_activation_hook(__FILE__, 'WPCC_activate_plugin');
function WPCC_activate_plugin(){
	// Any action on activation? Maybe later...
}

/********************************************************************
*
* Plugin deactivation
*
********************************************************************/
register_deactivation_hook(__FILE__, 'WPCC_deactivate_plugin');
function WPCC_deactivate_plugin(){
	// Any action on deactivation? Maybe later...
}

/********************************************************************
*
* Plugin UNINSTALL
*
********************************************************************/
register_uninstall_hook(__FILE__, 'WPCC_uninstall');
function WPCC_uninstall(){
	// Any action on UNINSTALL? Maybe later...
}

/********************************************************************
*
* The admin page of the plugin
*
********************************************************************/
function WPCC_main_page_callback(){ ?>
	<div class="wrap">
		<h2>WP Cron Cleaner</h2>
		<div class="WPCC-margin-r-300">
			<div class="WPCC-tab-box">
				<?php
				$WPCC_tabs = array('cron'  	  => __('Scheduled tasks', 'wp-cron-cleaner'),
								   'premium'  => __('Premium', 'wp-cron-cleaner')
							);

				// If $_GET['WPCC_cat'] is not set, initiate it to default 'all'
				if(!isset($_GET['WPCC_cat'])){
					$_GET['WPCC_cat'] = "all";
				}

				$current_tab = isset($_GET['WPCC_tab']) ? $_GET['WPCC_tab'] : 'cron';

				echo '<h2 class="nav-tab-wrapper">';
				foreach($WPCC_tabs as $tab => $name){
					$class = ($tab == $current_tab) ? ' nav-tab-active' : '';
					$link = "?page=wp_cron_cleaner&WPCC_tab=$tab";
					if($tab == "cron"){
						$link .= '&WPCC_cat=all';
					}
					echo "<a class='nav-tab$class' href='$link'>$name</a>";
				}
				echo '</h2>';

				echo '<div class="WPCC-tab-box-div">';
				switch ($current_tab){
					case 'cron' :
						include_once 'includes/class_clean_cron.php';
						break;
					case 'premium' :
						include_once 'includes/premium_page.php';
						break;						
				}
				echo '</div>';
				?>
			</div>
			<div class="WPCC-sidebar"><?php include_once 'includes/sidebar.php'; ?></div>
		</div>
	</div>
<?php 
}

/***************************************************************
*
* Get functions
*
***************************************************************/
include_once 'includes/functions.php';

?>