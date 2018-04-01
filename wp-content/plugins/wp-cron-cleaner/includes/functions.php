<?php

/***********************************************************************************
*
* Prepares tasks to display + message
*
***********************************************************************************/
function WPCC_prepare_items_to_display(&$items_to_display, &$WPCC_items_categories_info){

	// Prepare categories info
	$WPCC_all_items = WPCC_get_all_scheduled_tasks();
	$WPCC_items_categories_info = array(
			'all' 	=> array('name' => __('All tasks', 'wp-cron-cleaner'),		'color' => '#4E515B',  	'count' => 0),
			'o'		=> array('name' => __('Orphan tasks','wp-cron-cleaner'),	'color' => '#E97F31', 	'count' => "--"),
			'p'		=> array('name' => __('Plugins tasks', 'wp-cron-cleaner'),	'color' => '#00BAFF', 	'count' => "--"),
			't'		=> array('name' => __('Themes tasks', 'wp-cron-cleaner'),	'color' => '#45C966', 	'count' => "--"),
			'w'		=> array('name' => __('WP tasks', 'wp-cron-cleaner'),		'color' => '#D091BE', 	'count' => "--")
			);

	// Prepare items to display
	$belongs_to = '<span style="color:#cecece">' . __('Available in Pro version!', 'wp-cron-cleaner') . '</span>';
	foreach($WPCC_all_items as $item_name => $item_info){

		$WPCC_items_categories_info['all']['count'] += count($item_info['sites']);
		if($_GET['WPCC_cat'] != "all"){
			continue;
		}

		foreach($item_info['sites'] as $site_id => $site_item_info){
			array_push($items_to_display, array(
					'hook_name' 		=> $item_name,
					'site_id' 			=> $site_id,
					'next_run' 			=> $site_item_info['next_run'] . ' - ' . $site_item_info['frequency'],
					'hook_belongs_to'	=> $belongs_to
			));
		}
	}
}

/***********************************************************************************
*
* Function proper to scheduled tasks processes
*
***********************************************************************************/

/** Prepares all scheduled tasks for all sites (if any) in a multidimensional array */
function WPCC_get_all_scheduled_tasks() {
	$WPCC_all_tasks = array();
	if(function_exists('is_multisite') && is_multisite()){
		global $wpdb;
		$blogs_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
		foreach($blogs_ids as $blog_id){
			switch_to_blog($blog_id);
				WPCC_add_scheduled_tasks($WPCC_all_tasks, $blog_id);
			restore_current_blog();
		}
	}else{
		WPCC_add_scheduled_tasks($WPCC_all_tasks, "1");
	}
	return $WPCC_all_tasks;
}

/** Prepares scheduled tasks for one single site (Used by WPCC_get_all_scheduled_tasks() function) */
function WPCC_add_scheduled_tasks(&$WPCC_all_tasks, $blog_id) {
	$cron = _get_cron_array();
	$schedules = wp_get_schedules();
	foreach((array) $cron as $timestamp => $cronhooks){
		foreach( (array) $cronhooks as $hook => $events){
			foreach( (array) $events as $event){
				// If the frequency exist
				if($event['schedule']){
					if(!empty($schedules[$event['schedule']])){
						$WPCC_frequency = $schedules[$event['schedule']]['display'];
					}else{
						$WPCC_frequency = __('Unknown!', 'wp-cron-cleaner');
					}
				}else{
					$WPCC_frequency = "<em>" . __('One-off event', 'wp-cron-cleaner') ."</em>";
				}
				// If the task has not been added yet, add it and initiate its info
				if(empty($WPCC_all_tasks[$hook])){
					$WPCC_all_tasks[$hook] = array('belongs_to' => '', 'sites' => array());
				}
				// Add info of the task according to the current site
				$WPCC_all_tasks[$hook]['sites'][$blog_id] = array('frequency' => $WPCC_frequency,
																  'next_run' => get_date_from_gmt(date('Y-m-d H:i:s', $timestamp), 'M j, Y @ H:i:s'));

			}
		}
	}
}

?>