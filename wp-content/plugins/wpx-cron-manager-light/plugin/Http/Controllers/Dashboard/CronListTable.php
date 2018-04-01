<?php
namespace WPXCronManagerLight\Http\Controllers\Dashboard;

if ( ! class_exists( 'WP_List_Table' ) ) {
  require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

use WPXCronManagerLight\WPBones\Html\Html;

final class CronListTable extends \WP_List_Table
{
  // Columns
  const COLUMN_HOOK_NAME  = 'hook_name';
  const COLUMN_SCHEDULE   = 'schedule';
  const COLUMN_SIGNATURE  = 'signature';
  const COLUMN_NEXT_RUN   = 'next_run';
  const COLUMN_COUNT_DOWN = 'count_down';
  const COLUMN_EXECUTE    = 'execute';
  const COLUMN_STATUS     = 'status';

  // Status
  const STATUS_ENABLED  = 'enabled';
  const STATUS_DISABLED = 'disabled';

  protected $_where = [ ];

  public function __construct()
  {
    parent::__construct(
      [
        'singular' => __( 'Cron', 'wpx-cron-manager-light' ),
        'plural'   => __( 'Crons', 'wpx-cron-manager-light' ),
        'ajax'     => false
      ] );
  }

  /**
   *  Associative array of columns
   *
   * @return array
   */
  public function get_columns()
  {
    $columns = [
      'cb'                    => '<input type="checkbox" />',
      self::COLUMN_NEXT_RUN   => __( 'Next Run', 'wpx-cron-manager-light' ),
      self::COLUMN_HOOK_NAME  => __( 'Hook name', 'wpx-cron-manager-light' ),
      self::COLUMN_SCHEDULE   => __( 'Schedule', 'wpx-cron-manager-light' ),
      self::COLUMN_COUNT_DOWN => __( 'Count Down', 'wpx-cron-manager-light' ),
      self::COLUMN_EXECUTE    => __( 'Execute', 'wpx-cron-manager-light' ),
      self::COLUMN_STATUS     => __( 'Status', 'wpx-cron-manager-light' ),
    ];

    return $columns;
  }

  /**
   * Columns to make sortable.
   *
   * @return array
   */
  public function get_sortable_columns()
  {
    return [ ];
  }

  /**
   * Render the bulk edit checkbox
   *
   * @param array $item
   *
   * @return string
   */
  public function column_cb( $item )
  {
    return sprintf(
      '<input type="checkbox" name="cron[]" value="%s" />', $item[ 'hook_name' ]
    );
  }

  /**
   * Display a cel content for a column.
   *
   * @param array  $item        The single item
   * @param string $column_name Column name
   *
   * @return mixed
   */
  public function column_default( $item, $column_name )
  {
    switch ( $column_name ) {
      default:
        return print_r( $item, true );
    }
  }

  public function column_hook_name( $item )
  {
    return sprintf( '<code class="wpxcm-code">%s</code>', $item[ self::COLUMN_HOOK_NAME ] );

//    $actions = [
//      'disable' => __( 'Disable', 'wpx-cron-manager-light' )
//    ];
//
//    $links = [ ];
//    foreach ( $actions as $key => $value ) {
//      $args          = [
//        'action'   => $key,
//        '_wpnonce' => wp_create_nonce( 'cron-' . $item[ 'hook_name' ] ),
//      ];
//      $links[ $key ] = Html::a( $value )
//                           ->href( add_query_arg( $args ) )
//                           ->html();
//    }
//
//
//    return sprintf( '<strong>%s</strong> %s', $item[ 'hook_name' ], $this->row_actions( $links ) );
  }

  public function column_next_run( $item )
  {
    // @todo preferences
    $date_format = 'M j, Y @ G:i';

    return date_i18n( $date_format, $item[ self::COLUMN_NEXT_RUN ] );
  }

  public function column_schedule( $item )
  {
    return $item[ self::COLUMN_SCHEDULE ];
  }

  public function column_count_down( $item )
  {
    $output = '';

    if ( 'on' == $item[ self::COLUMN_STATUS ] ) {

      $time = $item[ self::COLUMN_NEXT_RUN ];

      if ( ! empty( $time ) ) {
        $in  = ( $time > time() ) ? __( 'in' ) . ' ' : '';
        $ago = ( $time < time() ) ? ' ' . __( 'ago' ) : '';

        // Javascript real-time count down
        if ( empty( $in ) ) {
          return $this->elapsedString( $item[ self::COLUMN_NEXT_RUN ] );
        }
        else {
          $wait = __( 'wait...' );

          return sprintf( '%s <span data-cron="%s" class="wpxcm-ui-countdown" data-time="%s">%s</span>', $in, $item[ self::COLUMN_HOOK_NAME ], ( $time - time() ) * 1000, $wait );
        }
      }
    }

    return $output;
  }

  public function column_execute( $item )
  {

    $hook_name = $item[ self::COLUMN_HOOK_NAME ];
    $signature = isset( $item[ self::COLUMN_SIGNATURE ] ) ? $item[ self::COLUMN_SIGNATURE ] : '';

    $button = Html::button( __( 'Now!', 'wpx-cron-manager-light' ) )
                  ->data( 'hook_name', $hook_name, 'signature', $signature, 'label', __( 'Now!' ), 'label_execute', __( 'Executing...' ) )
                  ->class( 'wpxcm-button-execute button button-primary button-small' );

    return $button->html();
  }

  public function column_status( $item )
  {
    $hook_name = $item[ self::COLUMN_HOOK_NAME ];

    if ( 'on' == $item[ self::COLUMN_STATUS ] ) {
      $button = Html::checkbox()
                    ->data( 'hook_name', $hook_name )
                    ->checked( 'checked' )
                    ->class( 'wpxcm-button-disable' );
    }
    else {
      $button = Html::checkbox()
                    ->data( 'hook_name', $hook_name )
                    ->class( 'wpxcm-button-enable' );
    }

    return $button->html();
  }

  /**
   * Returns an associative array containing the bulk action
   *
   * @return array
   */
  public function get_bulk_actions()
  {
    $actions = [ ];

    return $actions;
  }

  /**
   * Handles data query and filter, sorting, and pagination.
   */
  public function prepare_items()
  {
    $this->_column_headers = $this->get_column_info();

    // Process bulk action
    $this->process_bulk_action();

    $per_page = $this->get_items_per_page( 'crons_per_page', 5 );

    /**
     * REQUIRED for pagination. Let's figure out what page the user is currently
     * looking at. We'll need this later, so you should always include it in
     * your own package classes.
     */
    $current_page = $this->get_pagenum();

    // get items
    $items = $this->getItems();

    /**
     * REQUIRED for pagination. Let's check how many items are in our data array.
     * In real-world use, this would be the total number of items in your database,
     * without filtering. We'll need this later, so you should always include it
     * in your own package classes.
     */
    $total_items = count( $items );

    /**
     * The WP_List_Table class does not handle pagination for us, so we need
     * to ensure that the data is trimmed to only the current page. We can use
     * array_slice() to
     */
    $slice_items = array_slice( $items, ( ( $current_page - 1 ) * $per_page ), $per_page );

    /**
     * REQUIRED. Now we can add our *sorted* data to the items property, where
     * it can be used by the rest of the class.
     */

    $this->items = $slice_items;

    /**
     * REQUIRED. We also have to register our pagination options & calculations.
     */
    $this->set_pagination_args(
      [
        'total_items' => $total_items,
        'per_page'    => $per_page,
        'total_pages' => ceil( $total_items / $per_page )
      ]
    );

  }

  public function process_bulk_action()
  {

//    //Detect when a bulk action is being triggered...
//    if ( 'delete' === $this->current_action() ) {
//
//      // In our file that handles the request, verify the nonce.
//      $nonce = esc_attr( $_REQUEST['_wpnonce'] );
//
//      if ( ! wp_verify_nonce( $nonce, 'sp_delete_customer' ) ) {
//        die( 'Go get a life script kiddies' );
//      }
//      else {
//        self::delete_customer( absint( $_GET['customer'] ) );
//
//        wp_redirect( esc_url( add_query_arg() ) );
//        exit;
//      }
//
//    }
//
//    // If the delete bulk action is triggered
//    if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
//         || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
//    ) {
//
//      $delete_ids = esc_sql( $_POST['bulk-delete'] );
//
//      // loop over the array of record IDs and delete them
//      foreach ( $delete_ids as $id ) {
//        self::delete_customer( $id );
//
//      }
//
//      wp_redirect( esc_url( add_query_arg() ) );
//      exit;
//    }
  }

  /**
   * The itens can be not found for two main reason: the query search has param tha t doesn't match with items, or the
   * items list (or the database query) return an empty list.
   *
   */
  public function no_items()
  {
    // Default message
    printf( __( 'No %s found.', 'wpx-cron-manager-light' ), 'Crons' );

    // If in search mode
    // @todo Find a way to determine if we are in 'search' mode or not
    echo '<br/>';

    _e( 'Please, check again your search parameters.', 'wpx-cron-manager-light' );
  }

  protected function getItems( $args = [ ] )
  {
    $items     = array();
    $crons     = _get_cron_array();
    $schedules = wp_get_schedules();
    $disabled  = get_site_option( 'wpxcm_disabled_cron', [ ] );

    // Defaults args
    $defaults = array(
      self::COLUMN_STATUS    => '',
      self::COLUMN_HOOK_NAME => [ ]
    );

    // Merging
    $args = wp_parse_args( $args, $defaults );

    // Where for status/schedule
    $status = $args[ self::COLUMN_STATUS ];

    foreach ( $crons as $timestamp => $cronhooks ) {
      foreach ( (array) $cronhooks as $hook => $events ) {
        foreach ( (array) $events as $sig => $event ) {

          // Filter $schedule
          if ( ! empty( $status ) &&
               ! in_array( $status, [
                 'all',
                 self::STATUS_DISABLED,
                 self::STATUS_ENABLED
               ] )
          ) {
            if ( $status != $event[ 'schedule' ] ) {
              continue;
            }
          }
          // Disable
          elseif ( ! empty( $status ) && $status == self::STATUS_DISABLED && ! [ $hook, $disabled ] ) {
            continue;
          }

          $item = array(
            self::COLUMN_HOOK_NAME => $hook,
            self::COLUMN_SIGNATURE => $sig,
            self::COLUMN_NEXT_RUN  => wp_next_scheduled( $hook ),
            self::COLUMN_STATUS    => in_array( $hook, $disabled ) ? 'off' : 'on'
          );

          if ( $event[ 'schedule' ] ) {
            $item[ self::COLUMN_SCHEDULE ] = $schedules[ $event[ 'schedule' ] ][ 'display' ];
          }
          // Single event
          else {
            $item[ self::COLUMN_SCHEDULE ] = __( 'One-time', 'wpx-cron-manager-light' );
          }
          $items[ $hook ] = $item;
        }
      }
    }

    // Check in disable list
    if ( ! empty( $this->disabled ) && ! empty( $status ) && in_array( $status, [ 'all', self::STATUS_DISABLED ] ) ) {
      foreach ( $this->disabled as $hook ) {
        if ( ! isset( $items[ $hook ] ) ) {
          $items[ $hook ] = array(
            self::COLUMN_HOOK_NAME => $hook,
            self::COLUMN_NEXT_RUN  => false,
            self::COLUMN_SCHEDULE  => false,
            self::COLUMN_STATUS    => 'off'
          );
        }
      }
    }

    // Where for hook
    if ( ! empty( $args[ self::COLUMN_HOOK_NAME ] ) ) {
      foreach ( $items as $key => $value ) {
        if ( ! in_array( $key, (array) $args[ self::COLUMN_HOOK_NAME ] ) ) {
          unset( $items[ $key ] );
        }
      }
    }

    return $items;
  }

  public function where()
  {
    if ( func_num_args() == 2 ) {
      $this->_where[] = [
        'key'        => func_get_arg( 0 ),
        'condiction' => '=',
        'value'      => func_get_arg( 1 )
      ];
    }

    return $this;
  }

  public function getSingleRow()
  {
    $args = [
      $this->_where[ 0 ][ 'key' ] => $this->_where[ 0 ][ 'value' ]
    ];

    $item = $this->getItems( $args );

    if ( $item ) {
      $singleItem = array_shift( $item );

      ob_start();
      $this->single_row( $singleItem );
      $content = ob_get_contents();
      ob_end_clean();

      return $content;
    }
  }

  /**
   * This method is similar to WordPress human_time_diff(), with the different that every amount is display.
   * For example if WordPress human_time_diff() display '10 hours', this method display '9 Hours 47 Minutes 56 Seconds'.
   *
   * @brief More readable time elapsed
   *
   * @param int    $timestamp  Date from elapsed
   * @param bool   $hide_empty Optional. If TRUE '0 Year' will not return. Default TRUE.
   * @param int    $to         Optional. Date to elapsed. If empty time() is used
   * @param string $separator  Optional. Separator, default ', '.
   *
   * @return string
   */
  private function elapsedString( $timestamp, $hide_empty = true, $to = 0, $separator = ', ' )
  {
    // If no $to then now
    if ( empty( $to ) ) {
      $to = time();
    }

    // Key and string output
    $useful = array(
      'y' => array( __( 'Year' ), __( 'Years' ) ),
      'm' => array( __( 'Month' ), __( 'Months' ) ),
      'd' => array( __( 'Day' ), __( 'Days' ) ),
      'h' => array( __( 'Hour' ), __( 'Hours' ) ),
      'i' => array( __( 'Minute' ), __( 'Minutes' ) ),
      's' => array( __( 'Second' ), __( 'Seconds' ) ),
    );

    $matrix = array(
      'y' => array( 12 * 30 * 24 * 60 * 60, 12 ),
      'm' => array( 30 * 24 * 60 * 60, 30 ),
      'd' => array( 24 * 60 * 60, 24 ),
      'h' => array( 60 * 60, 60 ),
      'i' => array( 60, 60 ),
      's' => array( 1, 60 ),
    );

    $diff = $timestamp - $to;

    $stack = array();
    foreach ( $useful as $w => $strings ) {

      $value = floor( $diff / $matrix[ $w ][ 0 ] ) % $matrix[ $w ][ 1 ];

      if ( empty( $value ) || $value < 0 ) {
        if ( $hide_empty ) {
          continue;
        }
        $value = 0;
      }

      $stack[] = sprintf( '%s %s', $value, _n( $strings[ 0 ], $strings[ 1 ], $value ) );
    }

    return implode( $separator, $stack );

  }

}
