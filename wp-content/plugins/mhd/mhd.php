<?php

/*
Plugin Name: Mhd
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: A brief description of the Plugin.
Version: 1.0
Author: martin
Author URI: http://URI_Of_The_Plugin_Author
License: A "Slug" license name e.g. GPL2
*/

function mhd_create_initial_db_tables() {
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	$table_name = $wpdb->prefix . "mhd_stops";
	$sql_stops = "CREATE TABLE IF NOT EXISTS $table_name (
	id mediumint(9) NOT NULL AUTO_INCREMENT,
	name varchar(50) NOT NULL,
	date_created TIMESTAMP NOT NULL,
	UNIQUE KEY (id)
	)$charset_collate ";

	$table_name = $wpdb->prefix . "mhd_lines";
	$refer = $wpdb->prefix . "mhd_stops";
	$sql_lines = "CREATE TABLE IF NOT EXISTS $table_name (
	id mediumint(9) NOT NULL AUTO_INCREMENT,
	line_no varchar(50) NOT NULL,
	end_stop varchar(150) NOT NULL,
	valid_since TIMESTAMP NOT NULL,
	stop_id mediumint(9) NOT NULL,
	 UNIQUE KEY (id),
	 FOREIGN KEY (stop_id) REFERENCES $refer(id) ON DELETE CASCADE 
	)$charset_collate ";

	$table_name = $wpdb->prefix . "mhd_times";
	$refer = $wpdb->prefix . "mhd_lines";
	$sql_times = "CREATE TABLE IF NOT EXISTS $table_name (
	id mediumint(9) AUTO_INCREMENT NOT NULL,
	time TIMESTAMP NOT NULL,
	days varchar(50) NOT NULL,
	line_id mediumint(9) NOT NULL,
	UNIQUE KEY (id),
	FOREIGN KEY (line_id) REFERENCES $refer(id) ON DELETE CASCADE 
	)$charset_collate ";

	$table_name = $wpdb->prefix . "mhd_free_days";
	$sql_free_days = "CREATE TABLE IF NOT EXISTS $table_name (
	id MEDIUMINT(9) AUTO_INCREMENT NOT NULL,
	date date NOT NULL,
	name varchar(200),
	type VARCHAR(200) NOT NULL,
	UNIQUE KEY (id),
	UNIQUE KEY (date)
)$charset_collate";

	require( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta($sql_stops);
	dbDelta($sql_lines);
	dbDelta($sql_times);
	dbDelta($sql_free_days);
}


function mhd_enqueue_admin_styles() {
	wp_register_style( 'mhd_admin_css', plugin_dir_url(__FILE__) . 'css/mhd_admin.css', false, '1.0.0' );
	wp_enqueue_style( 'mhd_admin_css' );
}


/**
 * Adds admin page to the left admin menu
 */
function mhd_add_menu_item() {
	require_once (plugin_dir_path(__FILE__) . "mhd-admin.php");
	//Original icon 'dashicons-editor-table'
	$icon_path = "data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iaXNvLTg4NTktMSI/PjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCI+PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB2ZXJzaW9uPSIxLjEiIGlkPSJDYXBhXzEiIHg9IjBweCIgeT0iMHB4IiB3aWR0aD0iNTEycHgiIGhlaWdodD0iNTEycHgiIHZpZXdCb3g9IjAgMCA0ODQuNSA0ODQuNSIgc3R5bGU9ImVuYWJsZS1iYWNrZ3JvdW5kOm5ldyAwIDAgNDg0LjUgNDg0LjU7IiB4bWw6c3BhY2U9InByZXNlcnZlIj48Zz48ZyBpZD0iZGlyZWN0aW9ucy1idXMiPjxwYXRoIGQ9Ik0zOC4yNSwzNTdjMCwyMi45NSwxMC4yLDQzLjM1LDI1LjUsNTYuMVY0NTljMCwxNS4zLDEwLjIsMjUuNSwyNS41LDI1LjVoMjUuNWMxNS4zLDAsMjUuNS0xMC4yLDI1LjUtMjUuNXYtMjUuNWgyMDRWNDU5ICAgIGMwLDE1LjMsMTAuMiwyNS41LDI1LjUsMjUuNWgyNS41YzE1LjMsMCwyNS41LTEwLjIsMjUuNS0yNS41di00NS45YzE1LjMtMTIuNzUsMjUuNS0zMy4xNDksMjUuNS01Ni4xVjEwMiAgICBjMC04OS4yNS05MS44LTEwMi0yMDQtMTAycy0yMDQsMTIuNzUtMjA0LDEwMlYzNTd6IE0xMjcuNSwzODIuNWMtMjAuNCwwLTM4LjI1LTE3Ljg1LTM4LjI1LTM4LjI1UzEwNy4xLDMwNiwxMjcuNSwzMDYgICAgczM4LjI1LDE3Ljg1LDM4LjI1LDM4LjI1UzE0Ny45LDM4Mi41LDEyNy41LDM4Mi41eiBNMzU3LDM4Mi41Yy0yMC40LDAtMzguMjUtMTcuODUtMzguMjUtMzguMjVTMzM2LjYsMzA2LDM1NywzMDYgICAgczM4LjI1LDE3Ljg1LDM4LjI1LDM4LjI1UzM3Ny40LDM4Mi41LDM1NywzODIuNXogTTM5NS4yNSwyMjkuNWgtMzA2VjEwMmgzMDZWMjI5LjV6IiBmaWxsPSIjNDI3NTk5Ii8+PC9nPjwvZz48Zz48L2c+PGc+PC9nPjxnPjwvZz48Zz48L2c+PGc+PC9nPjxnPjwvZz48Zz48L2c+PGc+PC9nPjxnPjwvZz48Zz48L2c+PGc+PC9nPjxnPjwvZz48Zz48L2c+PGc+PC9nPjxnPjwvZz48L3N2Zz4=";
	add_menu_page("MHD", "MHD", "manage_categories",
		"mhd-admin", "mhd_admin_page", $icon_path, 4);
}

/**
 * Add WP Cron weekly event
 */
add_filter( 'cron_schedules', 'mhd_add_weekly_schedule' );
function mhd_add_weekly_schedule( $schedules ) {
	$schedules['weekly'] = array(
		'interval' => 7 * 24 * 60 * 60, //7 days * 24 hours * 60 minutes * 60 seconds
		'display' => __( 'Once Weekly', 'mhd' )
	);
	
	return $schedules;
}


add_filter( 'cron_schedules', 'mhd_add_yearly_schedule' );
function mhd_add_yearly_schedule( $schedules ) {
	$schedules['yearly'] = array(
		'interval' => 365 * 24 * 60 * 60, //7 days * 24 hours * 60 minutes * 60 seconds
		'display' => __( 'Once a year', 'mhd' )
	);
	
	return $schedules;
}
/**
 * Add WP cron evenet for updating DB
 */
//On plugin activation schedule our daily database backup
register_activation_hook( __FILE__, 'mhd_create_weekly_update_schedule' );

function mhd_create_weekly_update_schedule(){
	//Use wp_next_scheduled to check if the event is already scheduled
	$timestamp = wp_next_scheduled( 'mhd_week_update' );
	
	//If $timestamp == false schedule daily backups since it hasn't been done previously
	if( $timestamp == false ){
		//Schedule the event for right now, then to repeat daily using the hook 'wi_create_daily_backup'
		wp_schedule_event( strtotime(date('Y-m-d', strtotime(' Sunday'))), 'weekly', 'mhd_week_update' );
	}
}
require_once (plugin_dir_path(__FILE__) . "mhd-admin.php");
add_action( 'mhd_week_update', 'mhd_update_all_lines' );


//On plugin activation schedule our daily database backup
register_activation_hook( __FILE__, 'mhd_create_yearly_update_schedule' );
function mhd_create_yearly_update_schedule(){
	//Use wp_next_scheduled to check if the event is already scheduled
	$timestamp = wp_next_scheduled( 'mhd_year_update' );
	
	//If $timestamp == false schedule daily backups since it hasn't been done previously
	if( $timestamp == false ){
		//Schedule the event for right now, then to repeat daily using the hook 'wi_create_daily_backup'
		wp_schedule_event( strtotime(date('Y-m-d', strtotime('first day of january next year'))), 'yearly', 'mhd_year_update' );
	}
}
require_once (plugin_dir_path(__FILE__) . "mhd-admin.php");
add_action( 'mhd_year_update', 'mhd_parse_free_days' );

/**
 * Add widget
 */
require_once( plugin_dir_path(__FILE__) . "mhd-widget.php");
add_action( 'widgets_init', function(){
	register_widget( 'mhd_widget' );
});

/**
 * Hooks
 */
add_action("admin_menu","mhd_add_menu_item");
add_shortcode('mhd', 'mhd_shortcode_handler');
register_activation_hook( __FILE__, 'mhd_create_initial_db_tables' );


function mhd_shortcode_handler($atts) {

    global $wpdb;
    $stop = $atts['id'];

    mhd_register_widget_scripts();
    mhd_register_widget_styles();

    ?>
    <div class="mhd-main">
        <?php

        $sql = "SELECT date,name,type  FROM wp_mhd_free_days
                    WHERE date - date(now()) = 0;";

        $holiday = $wpdb->get_results($sql);

        //Weekend
        if(mhd_is_weekend(date('Y-m-d'))) {
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

    ?>
    <script type="text/javascript">
        jQuery(document).ready(function () {
            jQuery('.content-area').css('width','100%');
        });
    </script>
    <?php
}

function mhd_is_weekend($date) {
    return (date('N', strtotime($date)) >= 6);
}

function mhd_register_widget_styles() {
    wp_register_style( 'mhd_css', plugin_dir_url(__FILE__) . 'css/mhd-display.css', false, '1.0.0' );
    wp_enqueue_style( 'mhd_css' );

    wp_register_style( 'catamaran-font','https://fonts.googleapis.com/css?family=Catamaran' );
    wp_enqueue_style( 'catamaran-font' );

    wp_register_style('source-sans','https://fonts.googleapis.com/css?family=Source+Sans+Pro');
    wp_enqueue_style('source-sans');
}

function mhd_register_widget_scripts() {
    //For moving rows in table
    wp_enqueue_script('mhd-script', plugin_dir_url(__FILE__) .
        'js/slide.js',array('jquery'),1.0);
    //For digital clock
    wp_enqueue_script('digital-time',plugin_dir_url(__FILE__) . 'js/time.js', array('jquery'));
}

?>