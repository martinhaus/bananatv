<?php

/*
|--------------------------------------------------------------------------
| Create The Plugin
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Bones plugin instance
| which serves as the "glue" for all the components.
|
*/

$plugin = new \WPXCronManagerLight\WPBones\Foundation\Plugin(
  realpath( __DIR__ . '/../' )
);

/*
|--------------------------------------------------------------------------
| Actions and filters
|--------------------------------------------------------------------------
|
| Feel free to insert your actions and filters.
|
*/

// Remove an event
add_filter( 'schedule_event', function( $event ) {

  // Disable list
  $disabled = get_site_option( 'wpxcm_disabled_cron', [ ] );

  // Nothing
  if ( empty( $disabled ) ) {
    return $event;
  }

  // Remove from database options
  if ( wp_next_scheduled( $event->hook, $event->args ) ) {
    wp_unschedule_event( $event->timestamp, $event->hook, $event->args );
  }

  // If this hook is disable
  if ( in_array( $event->hook, $disabled ) ) {

    // Disable
    return false;
  }

  return $event;
} );

/*
|--------------------------------------------------------------------------
| Return The Plugin
|--------------------------------------------------------------------------
|
| This script returns the plugin instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

/**
 * Fire when the plugin is loaded
 */
do_action( 'wpx-cron-manager-light_loaded' );

return $plugin;