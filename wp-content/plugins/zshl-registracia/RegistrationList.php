<?php

/**
 * Created by PhpStorm.
 * User: martin
 * Date: 31.1.2017
 * Time: 14:02
 */
class RegistrationList extends WP_List_Table {
	
	/** Class constructor */
	public function __construct() {
		
		parent::__construct( [
			'singular' => __( 'Registrácia', 'zshl' ), //singular name of the listed records
			'plural'   => __( 'Registrácie', 'zshl' ), //plural name of the listed records
			'ajax'     => false //should this table support ajax?
		
		] );
	}
	
	public static function get_registration( $per_page = 5, $page_number = 1 ) {
		
		global $wpdb;
		
		$sql = "SELECT * FROM {$wpdb->prefix}registrations";
		
		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
		}
		
		$sql .= " LIMIT $per_page";
		
		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;
		
		
		$result = $wpdb->get_results( $sql, 'ARRAY_A' );
		
		return $result;
	}
	
	public static function delete_registration( $id ) {
		global $wpdb;
		
		
		$wpdb->delete(
			"{$wpdb->prefix}registrations",
			[ 'id' => $id ],
			[ '%d' ]
		);
	}
	
	public static function record_count() {
		global $wpdb;
		
		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}registrations";
		
		return $wpdb->get_var( $sql );
	}
	
	function column_name( $item ) {
		
		// create a nonce
		$delete_nonce = wp_create_nonce( 'delete_registration' );
		$edit_nonce = wp_create_nonce( 'edit_registration' );
		
		$title = '<strong>' . $item['name'] . '</strong>';
		
		$actions = [
			'view' => sprintf( '<a href="?page=%s&ac=%s&registration=%s">Zobraziť</a>', esc_attr( $_REQUEST['page'] ), 'view', absint( $item['id'] ) ),
			'edit' => sprintf( '<a href="?page=%s&ac=%s&registration=%s&_wpnonce=%s">Upraviť</a>', esc_attr( $_REQUEST['page'] ), 'edit_reg', absint( $item['id'] ), $edit_nonce ),
			'delete' => sprintf( '<a href="?page=%s&action=%s&registration=%s&_wpnonce=%s">Zmazať</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['id'] ), $delete_nonce ),
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
			'name'    => __( 'Názov' ),
			'start_date'    => __( 'Začiatok zobrazovania' ),
			'end_date'    => __( 'Koniec zobrazovania' ),
			'no_of_teams'    => __( 'Počet tímov' ),
			'date_created'    => __( 'Dátum vytvorenia' ),
			'date_updated'    => __( 'Dátum aktualizácie' ),
			'shortcode' => 'Shortcode',
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
			case 'name':
			case 'start_date':
			case 'end_date':
			case 'date_created':
			case 'date_updated':
			case 'no_of_teams':
				return $item[ $column_name ];
			case 'shortcode':
				return '[zshl id="'  . $item['id'] . '"]';
			default:
				return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
		}
	}
	
	
	public function prepare_items() {
		
		$this->_column_headers = $this->get_column_info();
		
		/** Process bulk action */
		$this->process_bulk_action();
		
		$per_page     = $this->get_items_per_page( 'registrations_per_page', 5 );
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
		$this->items = self::get_registration( $per_page, $current_page );
	}
	
	
	
	public function process_bulk_action() {
		
		//Detect when a bulk action is being triggered...
		if ( 'delete' === $this->current_action() ) {
			
			// In our file that handles the request, verify the nonce.
			$nonce = esc_attr( $_REQUEST['_wpnonce'] );
			
			if ( ! wp_verify_nonce( $nonce, 'delete_registration' ) ) {
				die( 'Go get a life script kiddies' );
			}
			else {
				
				self::delete_registration( absint( $_GET['registration'] ) );
			}
			
		}
		
		// If the delete bulk action is triggered
		if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
		     || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
		) {
			
			$delete_ids = esc_sql( $_POST['bulk-delete'] );
			
			// loop over the array of record IDs and delete them
			foreach ( $delete_ids as $id ) {
				
				self::delete_registration( $id );
				
			}
			wp_redirect(remove_query_arg(['action'],$_SERVER['HTTP_REFERER']));
			exit;
		}
	}
	
	
}