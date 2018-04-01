<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 27.7.2016
 * Time: 13:06
 */

class mhd_widget extends WP_Widget {

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {
		$widget_ops = array(
			'classname' => 'mhd_widget',
			'description' => 'Widget for displaying MHD',
		);

		parent::__construct( 'mhd_widget', 'MHD Odchody', $widget_ops );
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		// outputs the content of the widget
		$this->mhd_register_widget_styles();
		$this->mhd_register_widget_scripts();
		extract( $args );
		//Stop id
		$stop         = $instance['stops'];

		global $wpdb;

		echo $before_widget;
		?>
		<div class="mhd-main">
		<?php

		$sql = "SELECT date,name,type  FROM wp_mhd_free_days
				WHERE date - date(now()) = 0;";

		$holiday = $wpdb->get_results($sql);

		//Weekend
		if($this->isWeekend(date('Y-m-d'))) {
			$sql = "SELECT * FROM
				(SELECT name,line_no,end_stop , days , DATE_FORMAT(time,'%H:%i') as time, TIME(time) - TIME(NOW()) AS diff
				FROM wp_mhd_stops
				JOIN wp_mhd_lines ON wp_mhd_stops.id = wp_mhd_lines.stop_id
				JOIN wp_mhd_times ON wp_mhd_lines.id = wp_mhd_times.line_id
				WHERE wp_mhd_stops.id = $stop AND  TIME(time) -TIME(NOW())  > 500
				) AS a
				WHERE days = 'Denne'
				      OR days = 'Voľné dni'
				ORDER BY  diff ASC
				LIMIT 10;";
		}
		//Working days
		else {
			//School or state holiday
			if(count($holiday) > 0) {
				//School holiday on working day
				if ($holiday[0]->type == "school_holiday") {
					$sql = "SELECT * FROM
					(SELECT name,line_no,end_stop , days , DATE_FORMAT(time,'%H:%i') AS time, TIME(time) - TIME(NOW()) AS diff
					FROM wp_mhd_stops
					JOIN wp_mhd_lines ON wp_mhd_stops.id = wp_mhd_lines.stop_id
					JOIN wp_mhd_times ON wp_mhd_lines.id = wp_mhd_times.line_id
					WHERE wp_mhd_stops.id = $stop AND  TIME(time) -TIME(NOW())  > 500
					) AS a
					WHERE days = 'Denne'
					      OR days = 'Pracovné dni (školské prázdniny)'
					      OR days = 'Pracovné dni'
					ORDER BY  diff ASC
					LIMIT 10;";
				}
				//State holiday on working day
				else {
					$sql = "SELECT * FROM
					(SELECT name,line_no,end_stop , days ,DATE_FORMAT(time,'%H:%i') AS time, TIME(time) - TIME(NOW()) AS diff
					FROM wp_mhd_stops
					JOIN wp_mhd_lines ON wp_mhd_stops.id = wp_mhd_lines.stop_id
					JOIN wp_mhd_times ON wp_mhd_lines.id = wp_mhd_times.line_id
					WHERE wp_mhd_stops.id = $stop AND  TIME(time) -TIME(NOW())  > 500
					) AS a
					WHERE days = 'Denne'
					      OR days = 'Voľné dni'
					ORDER BY  diff ASC
					LIMIT 10;";
				}
			}
			//Regular working day
			else {
				$sql = "SELECT * FROM
				(SELECT name,line_no,end_stop , days , DATE_FORMAT(time,'%H:%i') as time, TIME(time) - TIME(NOW()) as diff
				FROM wp_mhd_stops
				JOIN wp_mhd_lines ON wp_mhd_stops.id = wp_mhd_lines.stop_id
				JOIN wp_mhd_times ON wp_mhd_lines.id = wp_mhd_times.line_id
				where wp_mhd_stops.id = $stop and  TIME(time) -TIME(NOW())  > 500
				) as a
				WHERE days = 'Denne'
				      OR days = 'Pracovné dni (školský rok)'
				      OR days = 'Pracovné dni'
				ORDER BY  diff ASC
				LIMIT 10;";
			}
		}
		$lines = $wpdb->get_results($sql);
		echo "<span class='stop-name'>Odchody zo zastávky </span>";
		echo $lines[0]->name . " <span id='time' class='time'></span><br>";
		echo "<table class='table'>";
		echo "<thead>
				<tr class='row-head'>
					<th class='line-no'></th>
					<th class='line-direction'>Smer</th>		
					<th class='dept-time'>Odchod</th>
				</tr>
			</thead>";
		echo "<tbody class='table-body'>";
		$count = 1;
		foreach ($lines as $key => $row) {
			echo "<tr class='row'><td class='line-no'>";
			echo $row->line_no . "</td><td class='line-direction'>"
			     . $row->end_stop . "</td><td class='dept-time'>"
			     . $row->time . "</td>";
			echo "</tr>";
			$count++;
		}
		echo "</tbody>";
		echo "</table>";
		?>
		</div>
		<?php
		echo $after_widget;
		
		?>
		<script type="text/javascript">
		             jQuery(document).ready(function () {
			             jQuery('.content-area').css('width','100%');
			             console.log('aaa');
		             });
        </script>
        
        <?php
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form( $instance ) {
		// outputs the options form on admin
		$instance = wp_parse_args( (array) $instance, array(
			'stops' => 'lala',
		));
		$stops = $instance['stops'];

		global $wpdb;
		$sql = "SELECT id,name FROM wp_mhd_stops";
		$options = $wpdb->get_results($sql);
		?>

		<select id = <?php echo $this->get_field_id('stops'); ?> name=<?php echo $this->get_field_name('stops'); ?> class="widefat">
			<?php
			foreach ( $options as $key => $row ) {
				$selected = ( $stops == $row->id ) ?
					'selected="selected"' : '';
				echo '<option value =' . "\"$row->id\" " . $selected . '>' . $row->name . '</option>';
			}
			?>
		</select>

		<?php
	}

	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 */
	public function update( $new_instance, $old_instance ) {
		// processes widget options to be saved

		$instance = $old_instance;

		$instance['stops'] = $new_instance['stops'];

		return $instance;
	}

	/**
	 * Checks if given day is weekend or not
	 * @param $date
	 *
	 * @return bool
	 */
	private function isWeekend($date) {
        return (date('N', strtotime($date)) >= 6);
	}

	public function mhd_register_widget_styles() {
		wp_register_style( 'mhd_css', plugin_dir_url(__FILE__) . 'css/mhd-display.css', false, '1.0.0' );
		wp_enqueue_style( 'mhd_css' );

		wp_register_style( 'catamaran-font','https://fonts.googleapis.com/css?family=Catamaran' );
		wp_enqueue_style( 'catamaran-font' );

		wp_register_style('source-sans','https://fonts.googleapis.com/css?family=Source+Sans+Pro');
		wp_enqueue_style('source-sans');
	}

	public function mhd_register_widget_scripts() {
		//For moving rows in table
		wp_enqueue_script('mhd-script', plugin_dir_url(__FILE__) .
		                                       'js/slide.js',array('jquery'),1.0);
		//For digital clock
		wp_enqueue_script('digital-time',plugin_dir_url(__FILE__) . 'js/time.js', array('jquery'));
	}


}