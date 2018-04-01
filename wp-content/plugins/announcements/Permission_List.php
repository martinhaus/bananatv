<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 24.12.2017
 * Time: 15:34
 */


class Permission_List extends WP_List_Table {
	
	/** Class constructor */
	public function __construct() {
		
		parent::__construct( [
			'singular' => __( 'Práva pre oznamy', 'sp' ), //singular name of the listed records
			'plural'   => __( 'Práva pre oznamy', 'sp' ), //plural name of the listed records
			'ajax'     => false //should this table support ajax?
		
		] );
	}
	
	public static function get_announcements( $per_page = 5, $page_number = 1 ) {
		
		global $wpdb;
		
		$sql = "SELECT {$wpdb->prefix}users.id, user_login, announcement_id FROM {$wpdb->prefix}users
		LEFT JOIN {$wpdb->prefix}announcements_permissions ON {$wpdb->prefix}announcements_permissions.user_id = {$wpdb->prefix}users.ID";
		
		$sql .= " ORDER BY id";
		
		$sql .= " LIMIT $per_page";
		
		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;
		
		
		$results = $wpdb->get_results( $sql, 'ARRAY_A' );
		
		$transformed = [];
		foreach ($results as $result) {
			$transformed[$result['id']]['id'] = $result['id'];
			$transformed[$result['id']]['user_login'] = $result['user_login'];
			$transformed[$result['id']]['categories'][] = $result['announcement_id'];
		}

		return $transformed;
	}
	
	public static function delete_permission( $id ) {
		global $wpdb;
		
		
		
		$wpdb->delete(
			"{$wpdb->prefix}announcements_permissions",
			[ 'id' => $id ],
			[ '%d' ]
		);
	}
	
	public static function record_count() {
		global $wpdb;
		
		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}users";
		
		return $wpdb->get_var( $sql );
	}
	
	
	
	function column_user_login( $item ) {
		return "<strong>{$item['user_login']}</strong>";
	}
	
	private function get_all_categories() {
		global $wpdb;
		$sql = "SELECT * FROM {$wpdb->prefix}announcements_categories;";
		$results = $wpdb->get_results($sql);
		
		$columns = [];
		foreach ($results as $result) {
			$columns['cat-' . $result->id] = $result->name;
		}
		return $columns;
	}
	
	function get_columns() {
		
		$categories = $this->get_all_categories();
		
		$columns = [
			'user_login' => 'Používateľ',
		];
		$columns = array_merge($columns,$categories);
		
		return $columns;
	}
	
	public function get_sortable_columns() {
		$sortable_columns = array(
		
		);
		
		return $sortable_columns;
	}
	

	function column_default( $item, $column_name ) {
		
		switch( $column_name ) {
			case 'user_login':
				return $item[ $column_name ];
			default:
			        if(user_can($item['id'],'administrator')
                        || user_can($item['id'],'editor')) {
                        return sprintf( '<input type="checkbox" name="" value="" checked disabled />');
                    }
					else if (in_array(str_replace("cat-","", $column_name), $item['categories'])) {
						return sprintf( '<input type="checkbox" name="%s" value="%s" checked />', "user[{$item['id']}][]", str_replace("cat-","", $column_name) );
					}
					else {
						return sprintf( '<input type="checkbox" name="%s" value="%s" />', "user[{$item['id']}][]", str_replace("cat-","", $column_name) );
					}
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
				
				
				self::delete_permission( absint( $_GET['announcement'] ) );
				
				
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
				
				self::delete_permission( $id );
				
			}
			//wp_redirect(remove_query_arg(['action'],$_SERVER['HTTP_REFERER']));
			//exit;
		}
	}
	
	
}