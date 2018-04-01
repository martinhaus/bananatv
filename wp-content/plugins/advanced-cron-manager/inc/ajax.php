<?php

/**
* ACM Ajax class
*/
class ACMajax {

	public function __construct() {

		add_action('wp_ajax_acm_add_schedule', array($this, 'add_schedule'));
		add_action('wp_ajax_acm_remove_schedule', array($this, 'remove_schedule'));

		add_action('wp_ajax_acm_add_task', array($this, 'add_task'));
		add_action('wp_ajax_acm_remove_task', array($this, 'remove_task'));
		add_action('wp_ajax_acm_execute_task', array($this, 'execute_task'));

		add_action('wp_ajax_acm_save_settings', array($this, 'save_settings'));

		add_action('wp_ajax_acm_deactivate_license', array($this, 'deactivate_license'));

	}

	public function add_schedule() {

		$params = $_REQUEST;

		// Return error when noonce doesn't match
		if ( !wp_verify_nonce($params['noonce'], 'add_schedule') )
			die( json_encode( array('status' => 'error', 'details' => __('Sorry, wrong noonce.', 'acm')) ) );

		if ( $params['interval'] <= 0 )
			die( json_encode( array('status' => 'error', 'details' => __('Sorry, interval can\'t be less than 1 second.', 'acm')) ) );

		if ( is_numeric($params['name']) )
			die( json_encode( array('status' => 'error', 'details' => __('Sorry, Schedule name can\'t be numeric.', 'acm')) ) );

		// Get schedules from option and from WP
		$schedules_opt = get_option('acm_schedules', array());
		$schedules_arr = wp_get_schedules();
		$schedules = array_merge($schedules_opt, $schedules_arr);

		$params['name'] = strtolower(trim( str_replace(' ', '_', $params['name']) ));
		
		foreach ($schedules as $name => $schedule) {

			// Return error when there is that schedule already
			if ( $params['name'] == $name )
				die( json_encode( array('status' => 'error', 'details' => sprintf(__('Sorry, there already is %s schedule.', 'acm' ), $name )) ) );

			// Return error when there is schedule with the same interval
			if ( $params['interval'] == $schedule['interval'] )
				die( json_encode( array('status' => 'error', 'details' => sprintf(__('Sorry, there already is schedule with %1$s seconds interval (%2$s).', 'acm' ), $params['interval'], $name )) ) );
		}

		// Add new schedule
		$schedules_opt[$params['name']] = array(
			'interval' => $params['interval'],
			'display' => $params['display']
		);

		// Update option with new schedule
		update_option('acm_schedules', $schedules_opt);

		$li = '<li id="single-schedule-'.$params['name'].'">'.$params['name'].' - '.$params['display'].' <a data-confirm="'.sprintf(__('Are you sure you want to delete %s schedule?', 'acm' ), $params['name'] ).'" data-schedule="'.$params['name'].'" data-noonce="'.wp_create_nonce( 'remove_schedule_'.$params['name'] ).'" class="remove remove-schedule">Remove</a></li>';

		$select = '<option value="'.$params['name'].'">'.$params['display'].'</option>';

		die( json_encode( array('status' => 'success', 'li' => $li, 'select' => $select) ) );


	}	

	public function remove_schedule() {

		$params = $_REQUEST;

		// Return error when noonce doesn't match
		if ( !wp_verify_nonce($params['noonce'], 'remove_schedule_'.$params['name']) )
			die( json_encode( array('status' => 'error', 'details' => __('Sorry, wrong noonce.', 'acm')) ) );

		$schedules = get_option('acm_schedules', array());

		if ( !array_key_exists($params['name'], $schedules) )
			die( json_encode( array('status' => 'error', 'details' => __('Sorry, there is no schedule to remove.', 'acm')) ) );

		// Remove schedule
		unset( $schedules[$params['name']] );

		// Update option with removed schedule
		update_option('acm_schedules', $schedules);

		die( json_encode( array('status' => 'success', 'details' => $params['name'] ) ) );

	}

	public function add_task() {

		$params = $_REQUEST;

		// Return error when noonce doesn't match
		if ( !wp_verify_nonce($params['noonce'], 'add_task') )
			die( json_encode( array('status' => 'error', 'details' => __('Sorry, wrong noonce.', 'acm')) ) );

		// Hook empty
		if ( empty($params['hook']) )
			die( json_encode( array('status' => 'error', 'details' => __('Task hook can\'t be empty.', 'acm')) ) );

		// Schedule name empty
		if ( empty($params['schedule']) )
			die( json_encode( array('status' => 'error', 'details' => __('Schedule name can\'t be empty.', 'acm')) ) );


		// Prepare vars
		$hook = strtolower(trim( str_replace(' ', '_', $params['hook']) ));
		$timestamp = time() + $params['offset'];
		$args = (empty($params['args'])) ? array() : explode(',', $params['args']);

		if ( $params['schedule'] == 'single' ) { // schedule single event

			$status = wp_schedule_single_event( $timestamp, $hook, $args );

			if ( $status === false )
				die( json_encode( array('status' => 'error', 'details' => __('Sorry, something goes wrong.', 'acm')) ) );

		} else { // schedule regular event

			$status = wp_schedule_event( $timestamp, $params['schedule'], $hook, $args );

			if ( $status === false )
				die( json_encode( array('status' => 'error', 'details' => __('Sorry, something goes wrong.', 'acm')) ) );

		}

		// Render new table row

		$wptime_offset = get_option('gmt_offset') * 3600;

		$table = '<tr class="single-cron cron-added-new">';
			$table .= '<td class="column-hook">';
				$table .= $hook;
			$table .= '</td>';
			$table .= '<td class="column-schedule">';
				$table .= $params['schedule'];
			$table .= '</td>';
			$table .= '<td class="column-args">'.acm_get_cron_arguments($args).'</td>';
			$table .= '<td class="column-next">'.acm_get_next_cron_execution($timestamp+$wptime_offset).'</td>';
			$table .= '<td class="column-next"><a id="execute_task" data-task="'.$hook.'" data-noonce="'.wp_create_nonce('execute_task_'.$hook).'" data-args="'.implode(',', $args).'" class="button-secondary">'.__('Execute', 'acm').'</a></td>';
		$table .= '</tr>';

		die( json_encode( array('status' => 'success', 'table' => $table, 'timestamp' => $timestamp ) ) );

	}

	public function remove_task() {

		$params = $_REQUEST;

		// Return error when noonce doesn't match
		if ( !wp_verify_nonce($params['noonce'], 'remove_task_'.$params['task']) )
			die( json_encode( array('status' => 'error', 'details' => __('Sorry, wrong noonce.', 'acm')) ) );

		$args = (empty($params['args'])) ? array() : explode(',', $params['args']);
		$timestamp = wp_next_scheduled($params['task'], $args);

		$hash = acm_get_cron_hash($params['task'], $timestamp, $args, (!isset($params['interval'])) ? 0 : $params['interval']);

		if ( empty($timestamp) )
			die( json_encode( array('status' => 'error', 'details' => __('Sorry, it\'s wrong data to remove', 'acm')) ) );

		wp_unschedule_event( $timestamp, $params['task'], $args );

		die( json_encode( array('status' => 'success', 'task' => $params['task'], 'info' => '<td colspan="4">'.__('Removed.', 'acm').'</td>', 'hash' => $hash ) ) );

	}

	public function execute_task() {

		$params = $_REQUEST;

		// Return error when noonce doesn't match
		if ( !wp_verify_nonce($params['noonce'], 'execute_task_'.$params['task']) )
			die( json_encode( array('status' => 'error', 'details' => __('Sorry, wrong noonce.', 'acm')) ) );

		ob_start();

		if ( isset( $params['args'] ) && ! empty( $params['args'] ) ) {

			if ( is_string( $params['args'] ) ) {
				$args = explode( ',', $params['args'] );
			}

			do_action_ref_array( $params['task'], $args );
			
		} else {
			do_action( $params['task'] );
		}
		
		ob_end_clean();

		die( json_encode( array('status' => 'success') ) );

	}

	public function save_settings() {

		$params = $_REQUEST;

		// Return error when noonce doesn't match
		if ( !wp_verify_nonce($params['nonce'], 'acm_save_settings') )
			die( json_encode( array('status' => 'error', 'details' => __('Sorry, wrong noonce.', 'acm')) ) );

		$settings = array();
		parse_str( $params['fields'], $settings );

		// checkbox fix
		if ( ! isset( $settings['log'] ) ) {
			$settings['log'] = false;
		} else {
			$settings['log'] = true;
		}

		$refresh = false;

		// license handle
		if ( isset( $settings['license'] )  && ! empty( $settings['license'] ) ) {

			$api_params = array( 
				'edd_action'=> 'activate_license', 
				'license' 	=> $settings['license'], 
				'item_name' => urlencode( ACMPRO_NAME ),
				'url'       => home_url()
			);
			
			$response = wp_remote_get( add_query_arg( $api_params, ACMPRO_STORE_URL ), array( 'timeout' => 15, 'sslverify' => false ) );

			if ( is_wp_error( $response ) )
				die( json_encode( array('status' => 'error', 'details' => __('Couldn\'t activate your license file. Please try again.', 'acm')) ) );

			$license_data = json_decode( wp_remote_retrieve_body( $response ) );
			
			if (  ! isset( $license_data->license ) || $license_data->license != 'valid' ) {
				die( json_encode( array('status' => 'error', 'details' => __('Wrong license key. Please ensure you entered correct one.', 'acm')) ) );
			}

			$refresh = true;

		}

		update_option( 'acm_settings', $settings );

		die( json_encode( array(
			'status' => 'success',
			'refresh' => $refresh
		) ) );

	}

	public function deactivate_license() {

		$params = $_REQUEST;

		// Return error when noonce doesn't match
		if ( !wp_verify_nonce($params['nonce'], 'acm_deactivate_license') )
			die( json_encode( array('status' => 'error', 'details' => __('Sorry, wrong noonce.', 'acm')) ) );

		$settings = get_option( 'acm_settings' );

		$refresh = false;

		if ( isset( $settings['license'] )  && ! empty( $settings['license'] ) ) {

			$api_params = array( 
				'edd_action'=> 'deactivate_license', 
				'license' 	=> $settings['license'], 
				'item_name' => urlencode( ACMPRO_NAME ),
				'url'       => home_url()
			);
			
			$response = wp_remote_get( add_query_arg( $api_params, ACMPRO_STORE_URL ), array( 'timeout' => 15, 'sslverify' => false ) );

			if ( is_wp_error( $response ) )
				die( json_encode( array('status' => 'error', 'details' => __('Sorry, something goes wrong.', 'acm')) ) );

			$license_data = json_decode( wp_remote_retrieve_body( $response ) );
			
			if ( $license_data->license == 'deactivated' ) {

				unset( $settings['license'] );
				update_option( 'acm_settings', $settings );

				$refresh = true;

				die( json_encode( array(
					'status' => 'success',
					'refresh' => $refresh
				) ) );

			} else {

				die( json_encode( array('status' => 'error', 'details' => __('License couldn\'t be deactivated. Please try again.', 'acm')) ) );

			}

		} else {

			die( json_encode( array('status' => 'error', 'details' => '') ) );

		}

		update_option( 'acm_settings', $settings );

	}

}