<?php

/**
 * Created by PhpStorm.
 * User: martin
 * Date: 3.6.2017
 * Time: 19:16
 */
class Task_List extends WP_List_Table {
	/** Class constructor */
	public function __construct() {
		
		parent::__construct( [
			'singular' => __( 'Úloha', 'sp' ), //singular name of the listed records
			'plural'   => __( 'Úlohy', 'sp' ), //plural name of the listed records
			'ajax'     => false //should this table support ajax?
		
		] );
	}
	
	public static function get_tasks($per_page = 25, $page_number = 1 ) {
		
		global $wpdb;
		
		$screen_id = $_GET['screen'];
		$sql = "SELECT * FROM {$wpdb->prefix}page_rotation_tasks WHERE screen_id = $screen_id";
		
		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
		}
		
		$sql .= " LIMIT $per_page";
		
		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;
		
		
		$result = $wpdb->get_results( $sql, 'ARRAY_A' );
		
		return $result;
	}
	
	public static function delete_task( $id ) {
		global $wpdb;
		
		$wpdb->delete(
			"{$wpdb->prefix}page_rotation_tasks",
			[ 'id' => $id ],
			[ '%d' ]
		);
	}
	
	public static function record_count() {
		global $wpdb;
		
		$screen_id = $_GET['screen'];
		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}page_rotation_tasks WHERE screen_id = $screen_id";
		
		return $wpdb->get_var( $sql );
	}
	
	function column_type( $item ) {
		
		// create a nonce
		$delete_nonce = wp_create_nonce( 'delete_task' );
		$edit_nonce = wp_create_nonce( 'edit_task' );
		
		$title = '<strong>' . $item['type'] . '</strong>';
		
		$actions = [
			//'edit' => sprintf( '<a href="?page=%s&action=%s&task=%s&_wpnonce=%s">Upraviť</a>', esc_attr( $_REQUEST['page'] ), 'edit', absint( $item['id'] ), $edit_nonce ),
			'delete' => sprintf( '<a href="?page=%s&screen=%s&action=%s&task=%s&_wpnonce=%s">Zmazať</a>', esc_attr( $_REQUEST['page'] ),esc_attr( $_REQUEST['screen'] ), 'delete', absint( $item['id'] ), $delete_nonce )
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
			'type'    => __( 'Typ' ),
			'exec_time' => __('Čas vykonania'),
			'rep' => __('Opakovanie'),
			'executed' => __('Vykonané'),
			'date_created'    => __( 'Dátum pridania' ),
			
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
			
			case 'date_created':
				return $item[ $column_name ];
			case 'type':
				return $item[ $column_name ];
			case 'exec_time':
				return $this->transform_exec_time($item);
			case 'rep':
				return $this->transform_rep($item[ $column_name ]);
			case 'executed':
				return $this->transform_executed($item);
			default:
				return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
		}
	}
	
	function transform_executed($item) {
		if ($item['rep'] != 0) {
			return '--';
		}
		
		else if ($item['executed'] == 1) {
			return 'áno';
		}
		else {
			return 'nie';
		}
	}
	
	function transform_rep($rep) {
		//week
		if ($rep == 604800) {
			return 'každý týždeň';
		}
		else {
			return '--';
		}
			
	}
	
	function transform_exec_time($item) {
		$days = array('Nedeľa', 'Pondelok', 'Utorok', 'Streda','Štvrtok','Piatok', 'Sobota');
		
		if ($item['rep'] > 0) {
			
			//Date portion
			$datetime = $item['exec_time'];
			$day_no = date('w',strtotime($datetime));
			$day_name = $days[$day_no];
			
			//Time portion
			$time = date('H:i:s', strtotime($datetime));
			
			return $day_name . ' - ' . $time;
		}
		
		return $item['exec_time'];
	}
	
	
	public function prepare_items() {
		
		$this->_column_headers = $this->get_column_info();
		
		/** Process bulk action */
		$this->process_bulk_action();
		
		$per_page     = $this->get_items_per_page( 'tasks_per_page', 10 );
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
		$this->items = self::get_tasks( $per_page, $current_page );
	}
	
	
	
	public function process_bulk_action() {
		
		//Detect when a bulk action is being triggered...
		if ( 'delete' === $this->current_action() ) {
			// In our file that handles the request, verify the nonce.
			$nonce = esc_attr( $_REQUEST['_wpnonce'] );
			
			if ( ! wp_verify_nonce( $nonce, 'delete_task' ) ) {
				die( 'Go get a life script kiddies' );
			}
			else {
				
				
				self::delete_task( absint( $_GET['task'] ) );
				
				
				//	wp_die(esc_url( add_query_arg() ) );
				
				//wp_die(remove_query_arg(['action'],$_SERVER['HTTP_REFERER']));
				//wp_redirect(remove_query_arg(['action'],$_SERVER['HTTP_REFERER']));
				//wp_redirect( esc_url( add_query_arg() ) );
			//	exit;
			}
			
		}
		
		// If the delete bulk action is triggered
		if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
		     || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
		) {
			$delete_ids = esc_sql( $_POST['bulk-delete'] );
			
			// loop over the array of record IDs and delete them
			foreach ( $delete_ids as $id ) {
				
				self::delete_task( $id );
				
			}
			//wp_redirect($_SERVER['HTTP_REFERER']);
			//exit;
		}
	}
}