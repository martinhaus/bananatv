<?php

/**
 * Plugin Name: WPX Cron Manager Light
 * Plugin URI: http://undolog.com
 * Description: Cron Manager displays and manages any kind WordPress registered cron job
 * Version: 2.1.0
 * Author: Giovambattista Fazioli
 * Author URI: http://undolog.com
 * Text Domain: wpx-cron-manager-light
 * Domain Path: localization
 *
 */

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| our application. We just need to utilize it! We'll simply require it
| into the script here so that we don't have to worry about manual
| loading any of our classes later on. It feels nice to relax.
|
*/
use WPXCronManagerLight\WPBones\Foundation\Plugin;

require_once __DIR__ . '/bootstrap/autoload.php';

/*
|--------------------------------------------------------------------------
| Bootstrap the plugin
|--------------------------------------------------------------------------
|
| We need to bootstrap the plugin.
|
*/

$GLOBALS[ 'WPXCronManagerLight' ] = require_once __DIR__ . '/bootstrap/plugin.php';

if ( ! function_exists( 'WPXCronManagerLight' ) ) {

  /**
   * Return the instance of plugin.
   *
   * @return Plugin
   */
  function WPXCronManagerLight()
  {
    return $GLOBALS[ 'WPXCronManagerLight' ];
  }
}