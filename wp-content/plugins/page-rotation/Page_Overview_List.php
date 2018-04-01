<?php

/**
 * Created by PhpStorm.
 * User: martin
 * Date: 23.7.2017
 * Time: 14:24
 */
class Page_Overview_List extends WP_List_Table {
	
	/** Class constructor */
	public function __construct() {
		
		parent::__construct( [
			'singular' => __( 'Stránka', 'sp' ), //singular name of the listed records
			'plural'   => __( 'Stránky', 'sp' ), //plural name of the listed records
			'ajax'     => false //should this table support ajax?
		
		] );
	}
	
	
	public static function get_pages($per_page = 20, $page_number = 1 ) {
		
		global $wpdb;
		
		$sql = "SELECT DISTINCT ID, post_title, post_date, post_modified FROM {$wpdb->prefix}posts
				JOIN {$wpdb->prefix}postmeta ON ID = post_id";
		
		if ( isset($_GET['type']) && $_GET['type'] != 'all')  {
			
			if ($_GET['type'] == 'posters') {
				$sql .= " WHERE meta_key = 'is_poster'";
			}
			
			else if ($_GET['type'] == 'iframes') {
				$sql .= " WHERE meta_key = 'is_iframe'";
			}
		}
		else {
			$sql .= " WHERE meta_key != 'sequence_start'
			 and  meta_key != 'screen'
			 and meta_key != 'screen_task'
			 and meta_key !='sequence_id'
			 and meta_key !='screen_id'";
		}
		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
		}
		
		$sql .= "  ORDER BY post_modified DESC";
		
		$sql .= " LIMIT $per_page";
		
		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;
		
		$result = $wpdb->get_results( $sql, 'ARRAY_A' );
		return $result;
	}
	
	protected function get_views() {
		
		//Add to GET parameters
		$all = http_build_query(array_merge($_GET, array("type"=>"all")));
		$posters = http_build_query(array_merge($_GET, array("type"=>"posters")));
		$iframes = http_build_query(array_merge($_GET, array("type"=>"iframes")));
		
		
		//Stupid solution to mark selected link
		
		$selected_all = '';
		$selected_posters = '';
		$selected_iframes = '';
		
		if (isset ($_GET['type'])) {
			switch ($_GET['type']) {
				case 'posters':
					$selected_posters = 'style="color: black; font-weight: bold;"';
					break;
				case 'iframes':
					$selected_iframes = 'style="color: black; font-weight: bold;"';
					break;
				default:
					$selected_all = 'style="color: black; font-weight: bold;"';
					$selected_posters = '';
					$selected_iframes = '';
					break;
			}
		}
		
		
		
		//Build links
		$status_links = array(
			"all"       => __("<a $selected_all href='?$all'>Všetky</a>",'page-rotation'),
			"posters" => __("<a $selected_posters href='?$posters'>Plagáty</a>",'page-rotation'),
			"iframes"   => __("<a $selected_iframes href='?$iframes'>Iframe</a>",'page-rotation')
		);
		return $status_links;
	}
	
	
	public static function delete_page( $id ) {
		global $wpdb;
		
		$wpdb->delete(
			"{$wpdb->prefix}posts",
			[ 'ID' => $id ],
			[ '%d' ]
		);
	}
	
	public static function record_count() {
		global $wpdb;
		
		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}posts";
		
		return $wpdb->get_var( $sql );
	}
	
	function column_post_title( $item ) {
		
		// create a nonce
		$delete_nonce = wp_create_nonce( 'delete_page' );
		$edit_nonce = wp_create_nonce( 'edit_page' );
		
		$title = '<strong>' . $item['post_title'] . '</strong>';
		
		if ( get_post_meta($item['ID'],'is_poster', true) ) {
			$actions = [
				'view' => sprintf( '<a href="%s">Zobraziť</a>',get_permalink($item['ID']) ),
				'edit' => sprintf( '<a href="?page=%s&action=%s&id=%s">Upraviť</a>', 'media-settings', 'edit', $item['ID'] ),
				'delete' => sprintf( '<a href="?page=%s&action=%s&id=%s&_wpnonce=%s">Zmazať</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['ID'] ), $delete_nonce )
			];
		}
		else {
			$actions = [
				'view' => sprintf( '<a href="%s">Zobraziť</a>',get_permalink($item['ID']) ),
				//'edit' => sprintf( '<a href="%s">Upraviť</a>',get_permalink($item['ID']) ),
				'delete' => sprintf( '<a href="?page=%s&action=%s&id=%s&_wpnonce=%s">Zmazať</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['ID'] ), $delete_nonce )
			];
		}
		
		
		
		return $title . $this->row_actions( $actions );
	}
	
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['ID']
		);
	}
	
	
	function get_columns() {
		$columns = [
			'cb'      => '<input type="checkbox" />',
			'post_title'    => __( 'Názov' ),
			'type'    => __( 'Typ stránky' ),
			'post_date' => __('Dátum pridania'),
			'post_modified' => __('Dátum úpravy')
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
			case 'post_title':
			case 'post_date':
			case 'post_modified':
				return $item[ $column_name ];
			case 'type':
				if ( get_post_meta($item['ID'],'is_poster', true) ) {
					return 'Plagát';
				}
				else if ( get_post_meta($item['ID'],'is_iframe', true) ) {
					return 'Iframe (webová stránka)';
				}
				else {
					return '--';
				}
				
				break;
			default:
				return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
		}
	}
	
	public function prepare_items() {
		
		$this->_column_headers = $this->get_column_info();
		
		/** Process bulk action */
		$this->process_bulk_action();
		
		$per_page     = $this->get_items_per_page( 'pages_per_page', 20 );
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
		$this->items = self::get_pages( $per_page, $current_page );
	}
	
	
	
	public function process_bulk_action() {
		//Detect when a bulk action is being triggered...
		if ( 'delete' === $this->current_action() ) {
			// In our file that handles the request, verify the nonce.
			$nonce = esc_attr( $_REQUEST['_wpnonce'] );
			
			if ( ! wp_verify_nonce( $nonce, 'delete_page' ) ) {
				die( 'Go get a life script kiddies' );
			}
			else {
				
				
				self::delete_page( absint( $_GET['id'] ) );
				
				
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
				
				self::delete_page( $id );
				
			}
			//wp_redirect(remove_query_arg(['action'],$_SERVER['HTTP_REFERER']));
			//exit;
		}
	}
}

add_filter('views_page_rotation_overview','page_rotation_status_links',10, 1);

function page_rotation_status_links($views) {
	$views['all'] =  "<a href='#'>Scheduled</a>";
	return $views;
}