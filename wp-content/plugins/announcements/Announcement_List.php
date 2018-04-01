<?php

/**
 * Created by PhpStorm.
 * User: martin
 * Date: 24.1.2017
 * Time: 21:41
 */
class Announcement_List extends WP_List_Table {
	
	/** Class constructor */
	public function __construct() {
		
		parent::__construct( [
			'singular' => __( 'Oznam', 'sp' ), //singular name of the listed records
			'plural'   => __( 'Oznamy', 'sp' ), //plural name of the listed records
			'ajax'     => false //should this table support ajax?
		
		] );
	}
	
	public static function get_announcements( $per_page = 5, $page_number = 1 ) {
		
		global $wpdb;
		
		$sql = "SELECT {$wpdb->prefix}announcements.id as id,
        announcement_text,
        date_created,
        date_updated,
        start_date,
        end_date,
        category_id,
        name
 
        FROM {$wpdb->prefix}announcements
		JOIN {$wpdb->prefix}announcements_categories ON category_id = {$wpdb->prefix}announcements_categories.id ";
		
		$userid = get_current_user_id();
		
		// If user is not admin or super admin, display announcements accordingly
		if ( !current_user_can('administrator') && !current_user_can('editor') ) {
			$sql .= "RIGHT JOIN {$wpdb->prefix}announcements_permissions ON announcement_id = {$wpdb->prefix}announcements_categories.id
			WHERE user_id = {$userid}
			AND {$wpdb->prefix}announcements.id > 0";
		}
		
		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
		}
		
		$sql .= " LIMIT $per_page";
		
		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;
		
		
		$result = $wpdb->get_results( $sql, 'ARRAY_A' );
		
		return $result;
	}
	
	public static function delete_announcement( $id ) {
		global $wpdb;
		
		
		
		$wpdb->delete(
			"{$wpdb->prefix}announcements",
			[ 'id' => $id ],
			[ '%d' ]
		);
	}
	
	public static function record_count() {
		global $wpdb;
		
		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}announcements";
		
		return $wpdb->get_var( $sql );
	}
	
	function column_announcement_text( $item ) {
		
		// create a nonce
		$delete_nonce = wp_create_nonce( 'delete_announcement' );
		$edit_nonce = wp_create_nonce( 'edit_announcement' );
		
		$style = '';
		
		$start_date = strtotime($item['start_date']);
		$end_date = strtotime($item['end_date']);
		$now = strtotime(date('Y-m-d H:i:s'));
		
		if ( $start_date > $now ) {
			$style = 'style="color: orange;"';
		}
		else if ( $start_date <= $now && $end_date >= $now) {
			$style = 'style="color: green;"';
		}
		else if ( $end_date < $now ) {
			$style = 'style="color: red;"';
		}
		
		
		$title = "<strong {$style}>" . $item['announcement_text'] . '</strong>';
		$actions = [
			'edit' => sprintf( '<a href="?page=%s&action=%s&announcement=%s&_wpnonce=%s">Upraviť</a>', esc_attr( $_REQUEST['page'] ), 'edit', absint( $item['id'] ), $edit_nonce ),
			'delete' => sprintf( '<a href="?page=%s&action=%s&announcement=%s&_wpnonce=%s">Zmazať</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['id'] ), $delete_nonce )
		];
		
		return $title . $this->row_actions( $actions );
	}
	
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['id']
		);
	}
	
	
	function get_columns() {
		$columns = [
			'cb'      => '<input type="checkbox" />',
			'announcement_text'    => __( 'Text oznamu' ),
			'start_date'    => __( 'Začiatok zobrazovania' ),
			'end_date'    => __( 'Koniec zobrazovania' ),
			'category' => __('Kategória'),
			'date_created'    => __( 'Dátum vytvorenia' ),
			'date_updated'    => __( 'Dátum aktualizácie' ),
		];
		
		return $columns;
	}
	
	public function get_sortable_columns() {
		$sortable_columns = array(
			
		);
		
		return $sortable_columns;
	}
	
	public function get_bulk_actions() {
		$actions = [
			'bulk-delete' => 'Zmazať'
		];
		
		return $actions;
	}
	
	function column_default( $item, $column_name ) {
		switch( $column_name ) {
			case 'announcement_text':
			case 'start_date':
				if($item[ $column_name ] == '1970-01-01 00:00:00') {
					return "neobmedzene";
				}
				else {
					return date('d.m.Y',strtotime($item[ $column_name ]));
				}
				break;
			case 'end_date':
				if($item[ $column_name ] == '9999-12-31 00:00:00') {
					return "neobmedzene";
				}
				else {
					return date('d.m.Y',strtotime($item[ $column_name ]));
				}
				break;
			case 'category':
				return $item['name'];
			case 'date_created':
			case 'date_updated':
				return $item[ $column_name ];
			default:
				return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
		}
	}
	
	
	public function prepare_items() {
		
		$this->_column_headers = $this->get_column_info();
		
		/** Process bulk action */
		$this->process_bulk_action();
		
		$per_page     = $this->get_items_per_page( 'announcements_per_page', 5 );
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();
		
		$this->set_pagination_args( [
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page //WE have to determine how many items to show on a page
		] );
		
		
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = array();
		$this->_column_headers = array($columns, $hidden, $sortable);
		$this->items = self::get_announcements( $per_page, $current_page );
	}
	
	
	
	public function process_bulk_action() {
		
		//Detect when a bulk action is being triggered...
		if ( 'delete' === $this->current_action() ) {
			
			// In our file that handles the request, verify the nonce.
			$nonce = esc_attr( $_REQUEST['_wpnonce'] );
			
			if ( ! wp_verify_nonce( $nonce, 'delete_announcement' ) ) {
				die( 'Go get a life script kiddies' );
			}
			else {
				
				
				self::delete_announcement( absint( $_GET['announcement'] ) );
				
				
			//	wp_die(esc_url( add_query_arg() ) );
				
				//wp_die(remove_query_arg(['action'],$_SERVER['HTTP_REFERER']));
				//wp_redirect(remove_query_arg(['action'],$_SERVER['HTTP_REFERER']));
				//wp_redirect( esc_url( add_query_arg() ) );
				//exit;
			}
			
		}
		
		// If the delete bulk action is triggered
		if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
		     || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
		) {
			
			$delete_ids = esc_sql( $_POST['bulk-delete'] );
			
			// loop over the array of record IDs and delete them
			foreach ( $delete_ids as $id ) {
				
				self::delete_announcement( $id );
				
			}
			//wp_redirect(remove_query_arg(['action'],$_SERVER['HTTP_REFERER']));
			//exit;
		}
	}
	
}