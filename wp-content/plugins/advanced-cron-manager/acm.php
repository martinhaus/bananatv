<?php
/**
 * Plugin Name: Advanced Cron Manager
 * Description: Plugin that allow to view, remove, edit and add WP Cron tasks. 
 * Version: 1.4.3
 * Author: Kuba Mikita
 * Author URI: http://www.wpart.pl/
 * License: GPL2
 * Text Domain: acm
 */



/* Define constants */

define('ACM_PATH', plugin_dir_path(__FILE__));
define('ACM_URL', plugin_dir_url(__FILE__));


/* Require needed files */

require_once(ACM_PATH.'functions.php');
require_once(ACM_PATH.'inc/ajax.php');
require_once(ACM_PATH.'inc/main.php');


/* Create ACMmain instance */

$acm = new ACMmain();


/* Load textdomain */

add_action('plugins_loaded', 'acm_load_textdomain');
function acm_load_textdomain() {
	load_plugin_textdomain('acm', false, dirname(plugin_basename( __FILE__ )).'/lang/'); 
}