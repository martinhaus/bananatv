<?php

/**
* ACM Main class
*/
class ACMmain {

	public $time_offset;

	public $ajax;

	public $crons = array();
	public $schedules = array();

	private $settings_array = array();

	public $protected_crons = array(
			'wp_maybe_auto_update',
			'wp_version_check',
			'wp_update_plugins',
			'wp_update_themes',
			'wp_scheduled_delete',
			'wp_scheduled_auto_draft_delete'
		);

	public $protected_schedules = array(
			'hourly',
			'daily',
			'twicedaily',
		);
	
	public function __construct() {

		$this->ajax = new ACMajax();

		$this->acm_schedules = get_option('acm_schedules', array());

		$this->settings = get_option( 'acm_settings', array(
			'log'        => false,
			'keep_in_db' => 100,
		) );

		add_filter( 'cron_schedules', array($this, 'add_schedules_to_filter') );

		$this->time_offset = get_option('gmt_offset') * 3600;
		
		add_action('admin_menu', array($this, 'add_menu_page'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts_and_styles'));

		if ( class_exists( 'ACMPRO' ) ) {
			$acm_pro = new ACMPRO( $this );
		}

	}

	public function add_schedules_to_filter($schedules) {

		foreach ( $this->acm_schedules as $name => $schedule ) {

			$schedules[$name] = array(
				'interval' => $schedule['interval'],
				'display' => $schedule['display']
			);

		}
		
		return $schedules;

	}

	public function enqueue_scripts_and_styles($hook) {

		if( 'tools_page_acm' != $hook )
	        return;

	    wp_enqueue_style('thickbox');
		wp_enqueue_script('thickbox');

	    wp_enqueue_script( 'acm_scripts', ACM_URL.'assets/js/admin.js', array('jquery'), 1, true );
	    wp_enqueue_script( 'acm_ajax_scripts', ACM_URL.'assets/js/ajax.js', array('jquery'), 1, true );
	    wp_enqueue_style( 'acm_style', ACM_URL.'assets/css/style.css' );

	}

	public function add_menu_page() {

		add_management_page('Advanced Cron Manager', 'Cron Manager', 'manage_options', 'acm', array($this, 'acm_page'));

	}

	public function acm_page() {

		$this->parse_crons();

		?>
		<div class="wrap acm metabox-holder">

			<?php $this->show_notifications(); ?>

			<h2><?php _e('Advanced Cron Manager', 'acm'); ?></h2>

			<div id="acm_content">
				<?php $this->display_content(); ?>
			</div>

			<div id="acm_aside">
				<?php $this->display_aside(); ?>
			</div>

		</div>
		<?php
	}

	public function parse_crons() {

		foreach (_get_cron_array() as $timestamp => $crons) {
			
			foreach ($crons as $cron_name => $cron_args) {

				foreach ($cron_args as $cron) {
					
					$this->crons[$timestamp + $this->time_offset][] = array(
						'hook' => $cron_name,
						'cron' => $cron,
						'hash' => acm_get_cron_hash($cron_name, $timestamp, $cron['args'], (!isset($cron['interval'])) ? 0 : $cron['interval'])
					);

				}

			}

		}

		ksort($this->crons);

	}

	public function show_notifications() {

		echo '<div id="notifications">';
			echo '<div id="notif-flex" style="display: none;" class="error"><p><strong></strong></p></div>';
			echo '<div id="notif-schedule-added" style="display: none;" class="updated"><p><strong>'.__('Schedule added successfully.', 'acm').'</strong></p></div>';
			echo '<div id="notif-schedule-removed" style="display: none;" class="updated"><p><strong>'.__('Schedule removed successfully.', 'acm').'</strong></p></div>';
			echo '<div id="notif-task-added" style="display: none;" class="updated"><p><strong>'.__('Task added successfully.', 'acm').'</strong></p></div>';
			echo '<div id="notif-task-removed" style="display: none;" class="updated"><p><strong>'.__('Task removed successfully.', 'acm').'</strong></p></div>';
			echo '<div id="notif-task-executed" style="display: none;" class="updated"><p><strong>'.__('Task executed successfully.', 'acm').'</strong></p></div>';
			echo '<div id="notif-settings-saved" style="display: none;" class="updated"><p><strong>'.__('Settings saved successfully.', 'acm').'</strong></p></div>';
			echo '<div id="notif-license-deactivated" style="display: none;" class="updated"><p><strong>'.__('License deactivated successfully.', 'acm').'</strong></p></div>';
		echo '</div>';

	}

	public function display_content() {

		$this->schedules = wp_get_schedules();

		$this->display_cron_table();

		if ( ! class_exists( 'ACMPRO' ) ) {

			echo '<div id="acm-pro" class="postbox ">
					<h3>'.__('Logs', 'acm').'</h3>
					<div class="inside placeholder-div">';
						echo '<h3><a href="https://www.wpart.co/downloads/advanced-cron-manager-pro/" target="_blank">' . __( 'Log cron executions with Advanced Cron Manager PRO', 'acm' ) . '</a></h3>';
			echo	'</div>
				</div>';

		}

		do_action( 'acm_after_table', $this );

	}

	public function display_aside() {

		echo '<div id="cron-schedules" class="postbox ">
				<h3>'.__('Cron Schedules', 'acm').'</h3>
				<div class="inside">';
					$this->display_cron_schedules();
		echo	'</div>
			</div>';

		echo '<div id="informations" class="postbox ">
				<h3>'.__('Informations', 'acm').'</h3>
				<div class="inside">';
					$this->display_informations_widget();
		echo	'</div>
			</div>';

		$this->settings_array = apply_filters( 'acm_settings', $this->settings_array );

		if ( ! empty( $this->settings_array ) ) {
			
			echo '<div id="settings" class="postbox ">
					<h3>'.__('Settings', 'acm').'</h3>
					<div class="inside">
						<form id="acm-save-settings-form" data-nonce="' . wp_create_nonce( 'acm_save_settings' ) . '">';
						$this->display_settings_widget();
				echo   '</form>
					</div>
				</div>';

		}
		
		echo '<div id="popslide" class="postbox ">
				<h3>'.__('Check this out!', 'acm').'</h3>
				<div class="inside">';
					$this->display_popslide_widget();
		echo	'</div>
			</div>';

	}

	public function display_informations_widget() {

		echo '<p>';
			_e('Please remember - after deactivation of this plugin added Schedules <strong>will be not available</strong>. Added Tasks will still work.', 'acm');
		echo '</p>';

		echo '<p>';
			_e('Important - WordPress Cron is depended on the User. WP Cron fires <strong>only on the page visit</strong> so it can be not accurate.', 'acm');
		echo '</p>';

	}

	public function display_settings_widget() {

		foreach ( $this->settings_array as $key => $setting ) {
			
			echo '<p>';

				if ( isset( $setting['label'] ) && ! empty( $setting['label'] ) ) {
					echo '<label for="acm-setting-' . $key . '">' . $setting['label'] . '<br>';
				}

					switch ( $setting['type'] ) {
						case 'text':
						case 'hidden':
						case 'number':
							$value = empty( $this->settings[ $key ] ) ? $setting['default'] : $this->settings[ $key ];
							$class = empty( $setting['class'] ) ? '' : $setting['class']; 
							$placeholder = empty( $setting['placeholder'] ) ? '' : $setting['placeholder']; 
							echo '<input type="' . $setting['type'] . '" class="' . $class . '" name="' . $key . '" id="acm-setting-' . $key . '" placeholder="' . $placeholder . '" value="' . $value . '">';
							break;

						case 'checkbox':
							$checked = empty( $this->settings[ $key ] ) ? $setting['default'] : $this->settings[ $key ];
							$class = empty( $setting['class'] ) ? '' : $setting['class']; 
							echo '<input type="' . $setting['type'] . '" class="' . $class . '" name="' . $key . '" id="acm-setting-' . $key . '" value="true" ' . checked( true, $checked, false ) . '>';
							break;

						case 'button':
							$class = empty( $setting['class'] ) ? '' : $setting['class']; 
							$id = empty( $setting['id'] ) ? '' : $setting['id']; 
							$data = empty( $setting['data'] ) ? '' : $setting['data']; 
							echo '<button class="' . $class . '" id="' . $id . '" ' . $data . '>' . $setting['text'] . '</button>';
							break;
						
						default:
							echo '';
							break;
					}

				if ( isset( $setting['label'] ) && ! empty( $setting['label'] ) ) {
					echo '</label>';
				}

			echo '</p>';

		}

		echo '<div class="field go"><button id="acm-save-settings" class="button button-secondary">' . __( 'Save', 'acm' ) . '</button></div>';

	}

	public function display_popslide_widget() {

		echo '<p>';
			_e('Did you ever searched for beautiful minimalistic and non aggressive popup?', 'acm');
		echo '</p>';

		echo '<a href=/wp-admin/plugin-install.php?tab=plugin-information&amp;plugin=popslide&amp;TB_iframe=true&amp;width=800&amp;height=600" class="thickbox button-primary" aria-label="'.__('Install Popslide', 'acm').'" data-title="Popslide">'.__('Try Popslide!', 'acm').'</a>';

	}

	public function display_cron_schedules() {

		echo '<ul id="schedules-list">';
		foreach ($this->schedules as $name => $schedule) {
			echo '<li id="single-schedule-'.$name.'">';
				echo $name.' - '.$schedule['display'];
				echo $this->get_schedule_actions($name);
				
			echo'</li>';
		}

		echo '</ul>';

		echo '<div class="field go"><button id="enable_schedule_form" class="button-secondary">'.__('Add Schedule', 'acm').'</button></div>';

		$this->display_add_schedule_form();

	}

	public function get_schedule_actions($name) {

		if ( in_array($name, $this->protected_schedules) || !isset($this->acm_schedules[$name]) )
			return ' <span class="disabled-action">'.__('Schedule protected', 'acm').'</span>';

		return ' <a data-confirm="'.sprintf(__('Are you sure you want to delete %s schedule?', 'acm' ), $name ).'" data-schedule="'.$name.'" data-noonce="'.wp_create_nonce( 'remove_schedule_'.$name ).'" class="remove remove-schedule">Remove</a>';

	}

	public function display_add_schedule_form() {
		?>

		<form id="add_schedule_form" method="POST" style="display: none;">
			<div class="field">
				<label for="interval"><?php _e('Interval (seconds)', 'acm'); ?></label>
				<input type="number" id="interval" name="interval" required min="1" value="1" />
			</div>

			<div class="field">
				<label for="name"><?php _e('Name', 'acm'); ?></label>
				<input type="text" id="name" name="name" class="widefat" required placeholder="<?php _e('weekly', 'acm'); ?>" />
			</div>

			<div class="field">
				<label for="nice_name"><?php _e('Description', 'acm'); ?></label>
				<input type="text" id="nice_name" name="nice_name" class="widefat" required placeholder="<?php _e('Once weekly', 'acm'); ?>" />
			</div>

			<div class="field go">
				<span id="add-schedule-indicator" class="spinner" style="display: none;"></span>
				<input type="submit" id="add-schedule" data-noonce="<?php echo wp_create_nonce('add_schedule'); ?>" name="add-schedule" class="button-primary" value="<?php _e('Add schedule', 'acm'); ?>" />
			</div>
			
		</form>

		<?php
	}

	public function display_cron_table() {
		?>
		<table class="wp-list-table widefat fixed crons" cellspacing="0">
			<thead>
				<tr>
					<th scope="col" id="hook" class="manage-column column-hook"><span><?php _e('Hook', 'acm'); ?></span></th>
					<th scope="col" id="schedule" class="manage-column column-schedule"><span><?php _e('Schedule', 'acm'); ?></span></th>
					<th scope="col" id="args" class="manage-column column-args"><span><?php _e('Arguments', 'acm'); ?></span></th>
					<th scope="col" id="next" class="manage-column column-next"><span><?php _e('Next execution', 'acm'); ?></span></th>
					<th scope="col" id="next" class="manage-column column-next"><span><?php _e('Action', 'acm'); ?></span></th>
				</tr>
			</thead>

			<tfoot>
				<tr>
					<th scope="col" id="hook" class="manage-column column-hook"><span><?php _e('Hook', 'acm'); ?></span></th>
					<th scope="col" id="schedule" class="manage-column column-schedule"><span><?php _e('Schedule', 'acm'); ?></span></th>
					<th scope="col" id="args" class="manage-column column-args"><span><?php _e('Arguments', 'acm'); ?></span></th>
					<th scope="col" id="next" class="manage-column column-next"><span><?php _e('Next execution', 'acm'); ?></span></th>
					<th scope="col" id="next" class="manage-column column-next"><span><?php _e('Action', 'acm'); ?></span></th>
				</tr>
			</tfoot>

			<tbody>
				<?php $this->display_cron_table_body(); ?>
			</tbody>
		</table>
		<?php
	}

	public function display_cron_table_body() {

		$alternate = '';
		
		foreach ($this->crons as $timestamp => $cron_many) {

			foreach ($cron_many as $cron) {
				
				$alternate = ($alternate == '') ? 'alternate' : '';
			
				echo '<tr class="single-cron cron-'.$cron['hash'].' '.$alternate.'">';
					echo '<td class="column-hook">';
						echo $cron['hook'];
						$this->display_row_actions($cron['hook'], (!isset($cron['cron']['interval'])) ? 0 : $cron['cron']['interval'], $cron['cron']['args']);
					echo '</td>';
					echo '<td class="column-schedule">';
						echo empty($cron['cron']['schedule']) ? _e('single', 'acm') : $cron['cron']['schedule'];
					echo '</td>';
					echo '<td class="column-args">'.acm_get_cron_arguments($cron['cron']['args']).'</td>';
					echo '<td class="column-next" data-timestamp="'.$timestamp.'">'.acm_get_next_cron_execution($timestamp).'</td>';
					echo '<td class="column-action"><a class="execute-task button-secondary" data-task="'.$cron['hook'].'" data-noonce="'.wp_create_nonce('execute_task_'.$cron['hook']).'" data-args="'.implode(',', $cron['cron']['args']).'">'.__('Execute', 'acm').'</a></td>';
				echo '</tr>';

			}

		}

		// Add task row
		$this->add_task_row($alternate);

	}

	public function add_task_row($alternate) {

		$alternate = ($alternate == '') ? 'alternate' : '';
		
		echo '<tr id="add_task_row" class="'.$alternate.'">';
			echo '<td colspan="5">';
				echo '<button id="show_task_form" class="button-secondary">'.__('Add Task', 'acm').'</button>';
			echo '</td>';
		echo '</tr>';

		echo '<tr id="add_task_form_row" class="'.$alternate.'" style="display: none;">';
			?>
			<form id="add_task_form" method="POST">
				<td>
					<input type="text" id="schedule_hook" name="schedule_hook" class="widefat" required placeholder="<?php echo __('schedule_hook_for_action', 'acm'); ?>" />
				</td>

				<td>
					<span><?php _e('Execute now', 'acm'); ?> +</span>
					<span><input type="number" id="timestamp_offset" name="timestamp_offset" required min="0" value="0" /> </span>
					<span><?php _e('seconds', 'acm'); ?>. </span>
					
					<span><?php _e('Then repeat', 'acm'); ?></span>
					<span><select id="select-schedule" name="schedule" required >
						<?php foreach ($this->schedules as $name => $schedule) {
							echo '<option value="'.$name.'">'.$schedule['display'].'</option>';
						} ?>
						<option value="single"><?php _e('Don\'t repeat', 'acm'); ?></option>
					</select></span>
				</td>

				<td>
					<div id="arguments-list"></div>
					<a id="add_argument_input" class="button-secondary"><?php _e('Add Argument', 'acm'); ?></a>
				</td>

				<td colspan="2">
					<a id="add-task" name="add-task" data-noonce="<?php echo wp_create_nonce('add_task'); ?>" class="button-primary"><?php _e('Add task', 'acm'); ?></a>
				</td>

			</form>

			<?php
		echo '</tr>';

	}

	public function display_row_actions($cron, $interval, $args) {

		echo '<div class="row-actions">';

			if ( in_array($cron, $this->protected_crons) ) {
				
				_e('Task protected', 'acm');

			} else {

				// echo '<span class="edit"><a href="#" data-noonce="'.wp_create_nonce( 'edit_task_'.$cron ).'">'.__('Edit', 'acm').'</a> | ';
				echo '<span class="trash"><a data-confirm="'.sprintf(__('Are you sure you want to delete %s task?', 'acm' ), $cron ).'" class="remove-task" data-interval="'.$interval.'" data-args="'.implode(",", $args).'" data-task="'.$cron.'" data-noonce="'.wp_create_nonce( 'remove_task_'.$cron ).'">'.__('Remove', 'acm').'</a>';

			}

		echo '</div>';

	}

}