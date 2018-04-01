<?php

namespace WPXCronManagerLight\Ajax;

use WPXCronManagerLight\WPBones\Foundation\WordPressAjaxServiceProvider;
use WPXCronManagerLight\Http\Controllers\Dashboard\CronListTable;

final class WPXCronManagerLightAjax extends WordPressAjaxServiceProvider
{

  /**
   * List of the ajax actions executed only by logged in users.
   * Here you will used a methods list.
   *
   * @var array
   */
  protected $logged = [
    'wpxcm_action_execute',
    'wpxcm_action_disable',
    'wpxcm_action_enable',
  ];


  public function wpxcm_action_execute()
  {
    $notFound = true;

    $hookname  = $this->request->get( 'hook_name' );
    $signature = $this->request->get( 'signature' );

    if ( is_null( $hookname ) || is_null( $signature ) ) {
      $response = [
        'status'      => 'error',
        'description' => __( "Error!!\n\nParams mismatch!" )
      ];
      wp_send_json_error( $response );
    }

    // Loop in cron
    foreach ( _get_cron_array() as $timestamp => $cronhooks ) {

      if ( isset( $cronhooks[ $hookname ][ $signature ] ) ) {
        $notFound = false;
        $args     = $cronhooks[ $hookname ][ $signature ][ 'args' ];

        wp_clear_scheduled_hook( $hookname, $args );

        delete_transient( 'doing_cron' );
        wp_schedule_single_event( time() - 1, $hookname, $args );
        spawn_cron();
      }
    }

    if ( $notFound ) {
      $response = [
        'status'      => 'error',
        'description' => __( "Warning!!\n\nAn error occour when try to get the cron! Cron not found!" )
      ];
      wp_send_json_error( $response );
    }

    $cronListTable = new CronListTable();
    $response      = [
      'row' => $cronListTable->where( 'hook_name', $hookname )->getSingleRow()
    ];

    wp_send_json_success( $response );
  }

  public function wpxcm_action_disable()
  {
    $disabled = get_site_option( 'wpxcm_disabled_cron', [ ] );

    $hookname = $this->request->get( 'hook_name' );

    if ( is_null( $hookname ) ) {
      $response = [
        'status'      => 'error',
        'description' => __( "Warning!!\n\nCron is empty!" )
      ];
      wp_send_json_error( $response );
    }

    $disabled = array_unique( array_merge( (array) $disabled, (array) $hookname ) );

    update_site_option( 'wpxcm_disabled_cron', $disabled );

    $cronListTable = new CronListTable();
    $response      = [
      'row' => $cronListTable->where( 'hook_name', $hookname )->getSingleRow()
    ];

    wp_send_json_success( $response );

  }

  public function wpxcm_action_enable()
  {
    $disabled = get_site_option( 'wpxcm_disabled_cron', [ ] );

    $hookname = $this->request->get( 'hook_name' );

    if ( is_null( $hookname ) ) {
      $response = [
        'status'      => 'error',
        'description' => __( "Warning!!\n\nCron is empty!" )
      ];
      wp_send_json_error( $response );
    }

    $disabled = array_unique( array_diff( (array) $disabled, (array) $hookname ) );

    update_site_option( 'wpxcm_disabled_cron', $disabled );

    $cronListTable = new CronListTable();
    $response      = [
      'row' => $cronListTable->where( 'hook_name', $hookname )->getSingleRow()
    ];

    wp_send_json_success( $response );

  }

}