<?php

/**
 * Created by PhpStorm.
 * User: martin
 * Date: 15.3.2017
 * Time: 15:18
 */
class Sequence_List extends WP_List_Table {
	
	/** Class constructor */
	public function __construct() {
		
		parent::__construct( [
			'singular' => __( 'Sekvencia', 'sp' ), //singular name of the listed records
			'plural'   => __( 'Sekvencie', 'sp' ), //plural name of the listed records
			'ajax'     => false //should this table support ajax?
		
		] );
	}
	
	public static function get_sequences($per_page = 5, $page_number = 1 ) {
		
		global $wpdb;

		$sql = "SELECT * FROM {$wpdb->prefix}page_rotation_sequences";
		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
		}

		$sql .= " LIMIT $per_page";

		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;


		$result = $wpdb->get_results( $sql, 'ARRAY_A' );

		require_once ('page-rotation-screen-permissions.php');
        if (!current_user_can('administrator') && !current_user_can('editor')) {
            foreach ($result as $key => $item) {
                if (!page_rotation_check_sequence_user_perm(get_current_user_id(), $item['id'])) {
                    unset($result[$key]);
                }
            }

        }



        return $result;
	}
	
	public static function delete_sequence( $id ) {
		global $wpdb;
		
		$wpdb->delete(
			"{$wpdb->prefix}page_rotation_sequences",
			[ 'id' => $id ],
			[ '%d' ]
		);
	}
	
	public static function record_count() {
		global $wpdb;
		
		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}page_rotation_sequences";
		
		return $wpdb->get_var( $sql );
	}
	
	function column_name( $item ) {
		
		// create a nonce
		$delete_nonce = wp_create_nonce( 'delete_sequence' );
		$edit_nonce = wp_create_nonce( 'edit_sequence' );
		
		$title = '<strong>' . $item['name'] . '</strong>';
		
		$actions = [
			'view' => sprintf( '<a href="%s">Zobraziť</a>', get_permalink( $item['start_page_id'] ) ),
			'edit' => sprintf( '<a href="?page=%s&action=%s&sequence=%s&_wpnonce=%s">Upraviť</a>', esc_attr( $_REQUEST['page'] ), 'edit', absint( $item['id'] ), $edit_nonce ),
			'delete' => sprintf( '<a href="?page=%s&action=%s&sequence=%s&_wpnonce=%s">Zmazať</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['id'] ), $delete_nonce )
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
			'comment' => __('Komentár'),
			'count' => __('Počet stránok'),
			'date_created'    => __( 'Dátum pridania' ),
			'date_modified'    => __( 'Dátum úpravy' ),
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
	
	public function count_pages( $item ) {
		global $wpdb;
		$id = $item['id'];
		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}page_rotation_pages WHERE sequence_id = $id";
		
		return $wpdb->get_var($sql);
	}
	
	function column_default( $item, $column_name ) {
		switch( $column_name ) {
			case 'name':
			case 'date_created':
			case 'date_modified':
			case 'comment':
				return '<i>' . $item[ $column_name ] . '</i>';
			case 'count':
				return $this->count_pages($item);
			default:
				return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
		}
	}
	
	
	public function prepare_items() {
		
		$this->_column_headers = $this->get_column_info();
		
		/** Process bulk action */
		$this->process_bulk_action();
		
		$per_page     = $this->get_items_per_page( 'sequences_per_page', 10 );
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
		$this->items = self::get_sequences( $per_page, $current_page );
	}
	
	
	
	public function process_bulk_action() {
		
		//Detect when a bulk action is being triggered...
		if ( 'delete' === $this->current_action() ) {
			// In our file that handles the request, verify the nonce.
			$nonce = esc_attr( $_REQUEST['_wpnonce'] );
			
			if ( ! wp_verify_nonce( $nonce, 'delete_sequence' ) ) {
				die( 'Go get a life script kiddies' );
			}
			else {
				
				self::delete_sequence( absint( $_GET['sequence'] ) );

			}
			
		}
		
		// If the delete bulk action is triggered
		if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
		     || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
		) {
			
			$delete_ids = esc_sql( $_POST['bulk-delete'] );
			
			// loop over the array of record IDs and delete them
			foreach ( $delete_ids as $id ) {
				self::delete_sequence( $id );
			}
			/*wp_redirect(remove_query_arg(['action'],$_SERVER['HTTP_REFERER']));
			exit;*/
		}
		
	}
}