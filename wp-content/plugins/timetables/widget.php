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
		$rooms[]         = $instance['room1'];
		$rooms[]         = $instance['room2'];
		$rooms[]         = $instance['room3'];

		global $wpdb;

		echo $before_widget;

		?>
		<div class="timetable-main">
			<div id="room_name">
			<?php
			echo "</div>";
		foreach ($rooms as $room) {
			
			$sql = "SELECT room from wp_timetables_timetables WHERE id = $room";
			$room_name = $wpdb->get_results($sql);
			
			$sql = "SELECT name,teacher,date_format(start_time,'%H:%i') as start_time,date_format(end_time,'%H:%i') as end_time,TIME_TO_SEC(end_diff) as end_diff  FROM(
					SELECT name,start_time, teacher, end_time,
					CONCAT(timediff(time('2018-05-22 10:00:00.00'),
						time(start_time))) as start_diff,
					CONCAT(timediff(time('2018-05-22 10:00:00.00'),
									time(end_time))) as end_diff,
					day_of_week,timetable_id
					FROM wp_timetables_lessons) as a
					WHERE day_of_week = WEEKDAY('2018-05-22 10:00:00.00') and timetable_id = $room and start_diff > '0' and end_diff < '0'
					ORDER BY start_diff DESC
					LIMIT 1;";
			$pending = $wpdb->get_results($sql);
			echo "<div class='lessons'>";
			echo "<div class='timetable-info room-name'>" . $room_name[0]->room . "</div>";
			echo "<div id='current_lesson' class='lesson'>";
			$time_till_end ="";
			foreach ( $pending as $key => $row ) {
				echo "<span class='acronym'>" . $this->timetables_subject_acronym($row->name)  . "</span><br>";
				// echo "<span class='timetable-info'>" . $row->name . "</span><br>";
				echo "<span class='timetable-info'>" . $row->teacher . "</span><br>";
				echo "<span class='timetable-info'>" .$row->start_time . " - " . $row->end_time . "</span><br>";
				$time_till_end = $row->end_diff;
			}

			if(!$pending) {
				echo "";
			} else {

				$id = chr( rand( 65, 90 ) ) . chr( rand( 65, 90 ) ) . chr( rand( 65, 90 ) );
				?>
				<!-- Do konca zostáva  <span id="<?php echo $id ?>" class="timetable-highlight">00:00:00</span><br> -->
				<span id="<?php echo $id ?>" class="timetable-highlight">00:00:00</span><br>
				<script
					type="text/javascript">startTimer(<?php echo abs( $time_till_end ) ?>, document.querySelector('#<?php echo $id ?>'));</script>
				<?php
			}
			echo "</div>";


			echo "<div id='next_lesson' class='lesson'>";
			$sql = "SELECT name,teacher,DATE_FORMAT(start_time,'%H:%i') as start_time,
					TIME_TO_SEC(diff) as diff, DATE_FORMAT(end_time,'%H:%i') as end_time FROM(
					SELECT name,start_time, end_time, teacher, CONCAT(timediff(time('2018-05-22 10:00:00.00'),
					time(start_time))) as diff,day_of_week,timetable_id
					FROM wp_timetables_lessons) as a
					WHERE day_of_week = WEEKDAY('2018-05-22 10:00:00.00') and timetable_id = $room and diff < '0'
					ORDER BY diff DESC
					LIMIT 1;
					";


			$next_hour = $wpdb->get_results($sql);
			$time_till_end ="";
			foreach ($next_hour as $key => $row) {
				echo "<span class='acronym'>" . $this->timetables_subject_acronym($row->name)  . "</span><br>";
				// echo "<span class='timetable-info'>" . $row->name . "</span><br>";
				echo "<span class='timetable-info'>" . $row->teacher . "</span><br>";
				echo "<span class='timetable-info'>" . $row->start_time . " - " . $row->end_time . "</span><br>";

				$time_till_end = $row->diff;
			}

			if(!$next_hour) {
				echo "Nasleduje: - <br>";
			} else {

				//Print countdown timer
				$id = chr( rand( 65, 90 ) ) . chr( rand( 65, 90 ) ) . chr( rand( 65, 90 ) );
				?>
				<!-- Do začiatku zostáva  <span id="<?php echo $id ?>" class="timetable-highlight">00:00:00</span> -->
				<span id="<?php echo $id ?>" class="timetable-highlight">00:00:00</span>
				<script
					type="text/javascript">startTimer(<?php echo abs( $time_till_end ) ?>, document.querySelector('#<?php echo $id ?>'));</script>
				<?php

			}
			echo "</div>";
			echo "</div>";
		}
		
		?>
		</div>
		<?php
		echo $after_widget;
		
		?>
        
        <script type="text/javascript">
        jQuery(document).ready(function () {
            jQuery('.content-area').css('width','100%');
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
			'room1' => '',
			'room2' => '',
			'room3' => '',
		));
		$room1 = $instance['room1'];
		$room2 = $instance['room2'];
		$room3 = $instance['room3'];

		global $wpdb;
		$sql = "SELECT id,room FROM wp_timetables_timetables";
		$options = $wpdb->get_results($sql);
		?>

		<select id = <?php echo $this->get_field_id('room1'); ?> name=<?php echo $this->get_field_name('room1'); ?> class="widefat">
			<?php
			foreach ( $options as $key => $row ) {
				$selected = ( $room1 == $row->id ) ?
					'selected="selected"' : '';
				echo '<option value =' . "\"$row->id\" " . $selected . '>' . $row->room . '</option>';
			}
			?>
		</select>

		<select id = <?php echo $this->get_field_id('room2'); ?> name=<?php echo $this->get_field_name('room2'); ?> class="widefat">
			<option value="">--</option>
			<?php
			foreach ( $options as $key => $row ) {
				$selected = ( $room2 == $row->id ) ?
					'selected="selected"' : '';
				echo '<option value =' . "\"$row->id\" " . $selected . '>' . $row->room . '</option>';
			}
			?>
		</select>

		<select id = <?php echo $this->get_field_id('rooms'); ?> name=<?php echo $this->get_field_name('room3'); ?> class="widefat">
			<option value=""></option>
			<?php
			foreach ( $options as $key => $row ) {
				$selected = ( $room3 == $row->id ) ?
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

		$instance['room1'] = $new_instance['room1'];
		$instance['room2'] = $new_instance['room2'];
		$instance['room3'] = $new_instance['room3'];

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

	/**
	 * Create acronym from subject name
	 */
	private function timetables_subject_acronym( $name = null) {
	    // Remove HTML whitespaces and dashes and replace them with whitespace
	    $words = str_replace("&nbsp;", " ", $name);
	    $words = str_replace("-", " ", $words);
	    //Slice up the string by whitespace
        $words = explode(" ", $words);
        $acronym = "";

        foreach ($words as $w) {
          if ( strlen($w) > 3) {
              $w[0] = strtoupper($w[0]);
          }
          $acronym .= $w[0];
        }
        return $acronym;
    }

}
