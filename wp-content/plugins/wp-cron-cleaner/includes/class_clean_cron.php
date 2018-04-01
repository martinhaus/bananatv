<?php

class WPCC_Tasks_List extends WP_List_Table {

	/** Holds the message to be displayed if any */
	private $WPCC_message = "";

	/** Holds the class for the message : updated or error. Default is updated */
	private $WPCC_class_message = "updated";

	/** Holds tasks that will be displayed */
	private $WPCC_tasks_to_display = array();

	/** Holds counts + info of tasks categories */
	private $WPCC_tasks_categories_info	= array();

    function __construct(){
        parent::__construct(array(
            'singular'  => __('Task', 'wp-cron-cleaner'),	//singular name of the listed records
            'plural'    => __('Tasks', 'wp-cron-cleaner'),	//plural name of the listed records
            'ajax'      => false							//does this table support ajax?
		));
		if(isset($_POST['WPCC_new_search_button']) && $_GET['WPCC_cat'] == "all"){
			$this->WPCC_message  = __('This feature is available in Pro version only.', 'wp-cron-cleaner');
			$this->WPCC_message .= " <a href='?page=wp_cron_cleaner&WPCC_tab=premium'>" . __('Please upgrade to pro version', 'wp-cron-cleaner') . "</a>";
			$this->WPCC_class_message  = "WPCC-upgrade-msg";
		}
		$this->WPCC_prepare_and_count_tasks();
		$this->WPCC_print_page_content();
    }

	/** Prepare tasks to display and count tasks for each category */
	function WPCC_prepare_and_count_tasks(){

		// Process bulk action if any before preparing tasks to display
		if(!isset($_POST['WPCC_new_search_button'])){
			$this->process_bulk_action();
		}

		// Prepare data
		WPCC_prepare_items_to_display($this->WPCC_tasks_to_display, $this->WPCC_tasks_categories_info);

		// Call WP prepare_items function
		$this->prepare_items();
	}

	/** WP: Get columns */
	function get_columns(){
		$WPCC_belongs_to_toolip = "<a class='WPCC-tooltips'>
									<img class='WPCC-margin-l-3' src='".  WPCC_PLUGIN_DIR_PATH . '/images/notice.png' . "'/>
									<span>" . __('Indicates the creator of the task. It can be a plugin name, a theme name or WordPress itself.','wp-cron-cleaner') ." </span>
								  </a>";	
		$columns = array(
			'cb'        		=> '<input type="checkbox" />',
			'hook_name' 		=> __('Hook name','wp-cron-cleaner'),
			'next_run'  		=> __('Next run - Frequency','wp-cron-cleaner'),
			'site_id'   		=> __('Site id','wp-cron-cleaner'),
			'hook_belongs_to'  	=> __('Belongs to','wp-cron-cleaner') . $WPCC_belongs_to_toolip
		);
		return $columns;
	}

	/** WP: Prepare items to display */
	function prepare_items() {
		$columns = $this->get_columns();
		$hidden = $this->get_hidden_columns();
		$sortable = array();
		$this->_column_headers = array($columns, $hidden, $sortable);
		$per_page = 50;
		$current_page = $this->get_pagenum();
		// Prepare sequence of options to display
		$display_data = array_slice($this->WPCC_tasks_to_display,(($current_page-1) * $per_page), $per_page);
		$this->set_pagination_args( array(
			'total_items' => count($this->WPCC_tasks_to_display),
			'per_page'    => $per_page
		));
		$this->items = $display_data;
	}

	/** WP: Get columns that should be hidden */
    function get_hidden_columns(){
		// If MU, nothing to hide, else hide Side ID column
		if(function_exists('is_multisite') && is_multisite()){
			return array();
		}else{
			return array('site_id');
		}
    }	

	/** WP: Column default */
	function column_default($item, $column_name){
		switch($column_name){
			case 'hook_name':
			case 'next_run':
			case 'site_id':
			case 'hook_belongs_to':
			  return $item[$column_name];
			default:
			  return print_r($item, true) ; //Show the whole array for troubleshooting purposes
		}
	}

	/** WP: Column cb for check box */
	function column_cb($item) {
		return sprintf('<input type="checkbox" name="WPCC_tasks_to_delete[]" value="%s" />', $item['site_id']."|".$item['hook_name']);
	}

	/** WP: Get bulk actions */
	function get_bulk_actions() {
		$actions = array(
			'delete'    => __('Delete','wp-cron-cleaner')
		);
		return $actions;
	}

	/** WP: Message to display when no items found */
	function no_items() {
		if($_GET['WPCC_cat'] == "all"){
			_e('No tasks found!','wp-cron-cleaner');
		}else{
			_e('Available in Pro version!', 'wp-cron-cleaner');
		}
	}

	/** WP: Process bulk actions */
    public function process_bulk_action() {
        // security check!
        if (isset($_POST['_wpnonce']) && !empty($_POST['_wpnonce'])){
            $nonce  = filter_input(INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING);
            $action = 'bulk-' . $this->_args['plural'];
            if (!wp_verify_nonce( $nonce, $action))
                wp_die('Security check failed!');
        }
        $action = $this->current_action();

        if($action == 'delete'){
			// If the user wants to clean the tasks he/she selected
			if(isset($_POST['WPCC_tasks_to_delete'])){
				if(function_exists('is_multisite') && is_multisite()){
					// Prepare tasks to delete in organized array to minimize switching from blogs
					$tasks_to_delete = array();
					foreach($_POST['WPCC_tasks_to_delete'] as $task){
						$task_info = explode("|", $task);
						if(empty($tasks_to_delete[$task_info[0]])){
							$tasks_to_delete[$task_info[0]] = array();
						}
						array_push($tasks_to_delete[$task_info[0]], $task_info[1]);
					}
					// Delete tasks
					foreach($tasks_to_delete as $site_id => $tasks){
						switch_to_blog($site_id);
						foreach($tasks as $task) {
							wp_clear_scheduled_hook($task);
						}
						restore_current_blog();
					}
				}else{
					foreach($_POST['WPCC_tasks_to_delete'] as $task) {
						$WPCC_cron_info = explode("|", $task);
						wp_clear_scheduled_hook($WPCC_cron_info[1]);
					}
				}
				// Update the message to show to the user
				$this->WPCC_message = __('Selected scheduled tasks cleaned successfully!', 'wp-cron-cleaner');
			}
        }
    }

	/** Print the page content */
	function WPCC_print_page_content(){
		// Print a message if any
		if($this->WPCC_message != ""){
			echo '<div id="WPCC_message" class="' . $this->WPCC_class_message . ' notice is-dismissible"><p>' . $this->WPCC_message . '</p></div>';
		}
		?>
		<div class="WPCC-content-max-width">
			<form id="WPCC_form" action="" method="post">
				<?php
				$WPCC_new_URI = $_SERVER['REQUEST_URI'];
				// Remove the paged parameter to start always from the first page when selecting a new category of tasks
				$WPCC_new_URI = remove_query_arg('paged', $WPCC_new_URI);
				?>
				<!-- Print numbers of tasks found in each category -->
				<div class="WPCC-category-counts">
					<?php
					$iterations = 0;
					foreach($this->WPCC_tasks_categories_info as $abreviation => $category_info){
						$iterations++;
						$WPCC_new_URI = add_query_arg('WPCC_cat', $abreviation, $WPCC_new_URI);?>
						<span class="<?php echo $abreviation == $_GET['WPCC_cat'] ? 'WPCC-selected-category' : ''?>" style="<?php echo $abreviation == $_GET['WPCC_cat'] ? 'border-bottom: 1px solid ' . $category_info['color'] : '' ?> ">
							<a href="<?php echo $WPCC_new_URI; ?>" class="WPCC-category-counts-links" style="color:<?php echo $category_info['color']; ?>">
								<span class="WPCC-category-color" style="background: <?php echo $category_info['color']; ?>"></span>
								<span><?php echo $category_info['name']; ?> : </span>
								<span><?php echo $category_info['count'];?></span>
							</a>	
						</span>
						<?php
						if($iterations < 5){
							echo '<span class="WPCC-category-separator"></span>';
						}
					}?>
				</div>

				<div class="WPCC-clear-both"></div>

				<!-- Code for "run new search" button + Show loading image -->
				<div class="WPCC-margin-t-20">
					<input id="WPCC_new_search_button" type="submit" class="button-primary WPCC-run-new-search" value="<?php _e('Detect orphan tasks','wp-cron-cleaner'); ?>"  name="WPCC_new_search_button"/>

					<div id="WPCC-please-wait">
						<div class="WPCC-loading-gif"></div>
						<?php 
						//_e('Searching...Please wait! If your browser stops loading without refreshing, please refresh this page.','wp-cron-cleaner');
						_e('Please wait!','wp-cron-cleaner');
						?>
					</div>
				</div>

				<div class="WPCC-clear-both WPCC-margin-b-20"></div>

				<!-- Print a notice/warning according to each type of tasks -->
				<?php
				if($_GET['WPCC_cat'] == 'all' && $this->WPCC_tasks_categories_info['all']['count'] > 0){
					echo '<div class="WPCC-box-warning">' . __('Below the list of all your scheduled tasks. Please do not delete any task unless you really know what you are doing!','wp-cron-cleaner') . '</div>';
				}

				if($_GET['WPCC_cat'] != 'all'){
					echo '<div class="WPCC-upgrade-msg notice is-dismissible"><p>' . __('This feature is available in Pro version only.') . ' <a href="?page=wp_cron_cleaner&WPCC_tab=premium">' . __('Please upgrade to pro version') . "</a>" . '</p></div>';
				}

				// Print the tasks
				$this->display();

				?>
			</form>
		</div>
		<div id="WPCC_dialog1" title="<?php _e('Cleaning...','wp-cron-cleaner'); ?>" class="WPCC-jquery-dialog">
			<p class="WPCC-box-warning">
				<?php _e('You are about to clean some of your scheduled tasks. This operation is irreversible. Don\'t forget to make a backup first.','wp-cron-cleaner'); ?>
			</p>
			<p>
				<?php _e('Are you sure to continue?','wp-cron-cleaner'); ?>
			</p>
		</div>
		<div id="WPCC_dialog2" title="<?php _e('Action required','wp-cron-cleaner'); ?>" class="WPCC-jquery-dialog">
			<p class="WPCC-box-info">
				<?php _e('Please select an action!','wp-cron-cleaner'); ?>
			</p>
		</div>
	<?php
	}
}

new WPCC_Tasks_List();

?>