<?php

/**
 * Created by PhpStorm.
 * User: martin
 * Date: 21.6.2016
 * Time: 11:27
 */
class timetable_widget extends WP_Widget {

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {
		$widget_ops = array(
			'classname' => 'timetable_widget',
			'description' => 'Widget for displaying timetables',
		);


		add_action( 'wp_enqueue_scripts', array( $this, 'timetables_register_widget_scripts' ) );
		$this->timetables_register_widget_styles();
		parent::__construct( 'my_widget', 'Rozvrhy', $widget_ops );
	}

	/**
	 * Outputs the content of the widget
	 */

	public function widget( $args, $instance ) {
		add_action('page', 'timetables_load_scripts');
		extract( $args );


		//Room id
		$room         = $instance['rooms'];

		global $wpdb;

		echo $before_widget;

		?>
		<div class="timetable-main">
			<div id="room_name">
			<?php

		$sql = "SELECT room from wp_timetables_timetables WHERE id = $room";
		$room_name = $wpdb->get_results($sql);
		echo "<span class='timetable-info'> Miestnosť: " . $room_name[0]->room . "</span>";
		echo "</div>";


		$sql = "SELECT name,teacher,date_format(start_time,'%H:%i') as start_time,date_format(end_time,'%H:%i') as end_time,TIME_TO_SEC(end_diff) as end_diff  FROM(
				SELECT name,start_time, teacher, end_time,
				  CONCAT(timediff(time(NOW()),
				      time(start_time))) as start_diff,
				  CONCAT(timediff(time(NOW()),
				                  time(end_time))) as end_diff,
				  day_of_week,timetable_id
				FROM wp_timetables_lessons) as a
				WHERE day_of_week = WEEKDAY(NOW()) and timetable_id = $room and start_diff > '0' and end_diff < '0'
				ORDER BY start_diff DESC
				LIMIT 1;";
		$pending = $wpdb->get_results($sql);

		echo "<div id='current_lesson'>";

		$time_till_end ="";
		foreach ( $pending as $key => $row ) {
			echo "Aktuálne prebieha: ";
			echo "<span class='timetable-info'>" . $row->name . "</span><br>";
			echo "Vyučujúci: <span class='timetable-info'>" . $row->teacher . "</span><br>";
			echo "Čas: <span class='timetable-info'>" .$row->start_time . " - " . $row->end_time . "</span><br>";
			$time_till_end = $row->end_diff;
		}

		if(!$pending) {
			echo "Prebieha: - <br>";
		} else {

			$id = chr( rand( 65, 90 ) ) . chr( rand( 65, 90 ) ) . chr( rand( 65, 90 ) );
			?>
			Do konca zostáva  <span id="<?php echo $id ?>" class="timetable-highlight">00:00:00</span><br>
			<script
				type="text/javascript">startTimer(<?php echo abs( $time_till_end ) ?>, document.querySelector('#<?php echo $id ?>'));</script>
			<?php
		}
		echo "</div>";


		echo "<div id='next_lesson'>";
		$sql = "SELECT name,teacher,DATE_FORMAT(start_time,'%H:%i') as start_time,
				TIME_TO_SEC(diff) as diff, DATE_FORMAT(end_time,'%H:%i') as end_time FROM(
				SELECT name,start_time, end_time, teacher, CONCAT(timediff(time(NOW()),
				time(start_time))) as diff,day_of_week,timetable_id
				FROM wp_timetables_lessons) as a
				WHERE day_of_week = WEEKDAY(NOW()) and timetable_id = $room and diff < '0'
				ORDER BY diff DESC
				LIMIT 1;
				";




		$next_hour = $wpdb->get_results($sql);
		$time_till_end ="";
		foreach ($next_hour as $key => $row) {
			echo "Nasleduje: ";
			echo "<span class='timetable-info'>" . $row->name . "</span><br>";
			echo "Vyučujúci: <span class='timetable-info'>" . $row->teacher . "</span><br>";
			echo "Čas: <span class='timetable-info'>" . $row->start_time . " - " . $row->end_time . "</span><br>";

			$time_till_end = $row->diff;
		}

		if(!$next_hour) {
			echo "Nasleduje: - <br>";
		} else {

			//Print countdown timer
			$id = chr( rand( 65, 90 ) ) . chr( rand( 65, 90 ) ) . chr( rand( 65, 90 ) );
			?>
			Do začiatku zostáva  <span id="<?php echo $id ?>" class="timetable-highlight">00:00:00</span>
			<script
				type="text/javascript">startTimer(<?php echo abs( $time_till_end ) ?>, document.querySelector('#<?php echo $id ?>'));</script>
			<?php

		}

		echo "</div>";
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
			'rooms' => 'lala',
		));
		$rooms = $instance['rooms'];

		global $wpdb;
		$sql = "SELECT id,room FROM wp_timetables_timetables";
		$options = $wpdb->get_results($sql);
		?>

		<select id = <?php echo $this->get_field_id('rooms'); ?> name=<?php echo $this->get_field_name('rooms'); ?> class="widefat">
			<?php
			foreach ( $options as $key => $row ) {
				$selected = ( $rooms == $row->id ) ?
					'selected="selected"' : '';
				echo '<option value =' . "\"$row->id\" " . $selected . '>' . $row->room . '</option>';
			}
			?>
		</select>

		<?php
	}

	/**
	 * Processing widget options on save
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 */
	public function update( $new_instance, $old_instance ) {
		// processes widget options to be saved

		$instance = $old_instance;

		$instance['rooms'] = $new_instance['rooms'];

		return $instance;
	}

	/**
	 * Loads javacript countdown
	 */
	public function timetables_register_widget_scripts() {
		wp_enqueue_script('timetables-script', plugin_dir_url(__FILE__) .
			'js/countdown.js',array(),1.0);
	}

	/**
	 * Loads CSS
	 */
	public function timetables_register_widget_styles() {
		wp_register_style( 'timetable_css', plugin_dir_url(__FILE__) . 'css/timetable_display.css', false, '1.0.0' );
		wp_enqueue_style( 'timetable_css' );

		wp_register_style( 'catamaran-font','https://fonts.googleapis.com/css?family=Catamaran' );
		wp_enqueue_style( 'catamaran-font' );

		wp_register_style('source-sans','https://fonts.googleapis.com/css?family=Source+Sans+Pro');
		wp_enqueue_style('source-sans');
	}

}
