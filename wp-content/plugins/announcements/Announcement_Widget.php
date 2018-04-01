<?php

/**
 * Created by PhpStorm.
 * User: martin
 * Date: 26.1.2017
 * Time: 19:58
 */
class Announcement_Widget extends WP_Widget {
	
	
	public function __construct() {
		$widget_ops = array(
			'classname' => 'Announcement_Widget',
			'description' => 'Widget for displaying announcements',
		);
		
		parent::__construct( 'Announcement_Widget', 'Oznamy', $widget_ops );
	}
	

	public function widget( $args, $instance ) {
		// outputs the content of the widget
		$this->announcements_register_widget_scripts();
		$this->announcements_register_widget_styles();
		extract( $args );
		
		echo $before_widget;
		
		?>
		<div id="announcements" class="announcements-all">
		<h2 class="widget-title">OZNAMY</h2>
		<div id="announcements-data" class="announcements-data">
		<?php
		$all_announcements = $this->get_all_announcements($instance['cat']);
        
		foreach ($all_announcements as $announcement) {
            $start_date = new DateTime($announcement->date_updated);
            $now = new DateTime();
            $diff = $start_date->diff($now);
            $diff->format("%a");
		    echo "<div class='announcement'> $announcement->announcement_text </div>";
		}
		
		$display_time = get_option("announcements-display-time");
		
		?>
		</div>
		</div>
		
        <script type="text/javascript">
            function cycleAnnouncements() {
                var interval = <?php echo $display_time; ?> * 1000;
                jQuery('.announcements-all').find('.announcement').each(function (index) {
                    jQuery(this).delay(index * (interval + 700) ).fadeIn(350).delay(interval-350).fadeOut(350);
                });
            }

            jQuery(document).ready(function () {

                var interval = <?php echo $display_time; ?> * 1000;
                var ann_count = jQuery('.announcements-all').find('.announcement').length;
                if (ann_count > 1) {

                    jQuery('.announcements-all').find('.announcement').each(function () {
                        jQuery(this).hide();
                    });

                    cycleAnnouncements();
                    setInterval(cycleAnnouncements, (interval + 700) * ann_count);
                }
            });

        </script>
		
		<?php
		echo $after_widget;
	}

	public function form( $instance ) {
		// outputs the options form on admin
		echo "Na stránke sa zobrazí slučka zobrazujúca oznamy";

        // outputs the options form on admin
        $instance = wp_parse_args( (array) $instance, array(
            'cat' => '',
        ));
        $stops = $instance['cat'];
        
        global $wpdb;
        $sql = "SELECT id,name FROM {$wpdb->prefix}announcements_categories";
        $options = $wpdb->get_results($sql);
        ?>
        
        <select id = <?php echo $this->get_field_id('cat'); ?> name=<?php echo $this->get_field_name('cat'); ?> class="widefat">
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
	

	public function update( $new_instance, $old_instance ) {
		// processes widget options to be saved
		
		
		$instance = $old_instance;
		
		$instance['cat'] = $new_instance['cat'];
		
		return $instance;
	}
	
	public function announcements_register_widget_styles() {
		wp_register_style( 'announcements-widget-css', plugin_dir_url(__FILE__) . 'styles/announcements-widget.css', false, '1.0.0' );
		wp_enqueue_style( 'announcements-widget-css' );
		
		wp_register_style( 'catamaran-font','https://fonts.googleapis.com/css?family=Catamaran' );
		wp_enqueue_style( 'catamaran-font' );
		
		wp_register_style('source-sans','https://fonts.googleapis.com/css?family=Source+Sans+Pro');
		wp_enqueue_style('source-sans');
		
		wp_register_style('pontano','https://fonts.googleapis.com/css?family=Pontano+Sans');
		wp_enqueue_style('pontano');
		
		wp_register_style('merriweather','https://fonts.googleapis.com/css?family=Merriweather');
		wp_enqueue_style('merriweather');
	}
	
	public function announcements_register_widget_scripts() {
	
	}
	
	private function get_all_announcements($cat_id) {
		global $wpdb;
		$sql = "SELECT * FROM {$wpdb->prefix}announcements WHERE start_date <= NOW() AND end_date >= NOW() AND category_id = $cat_id;";
		$results = $wpdb->get_results($sql);
		return $results;
	}
}