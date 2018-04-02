<?php

/*
Plugin Name: Sekvencie
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: Modul slúžiaci na vytvorenie rotujúcej postupnosti zobrazených stránok
Version: 1.0
Author: Martin Hauskrecht
Author URI: http://URI_Of_The_Plugin_Author
License: A "Slug" license name e.g. GPL2
*/



/**
 * Adds menu item to the left Wordpress menu panel
 */
function page_rotation_add_menu_item() {
    require_once( plugin_dir_path( __FILE__ ) . "page-rotation-settings.php");
	$sequence_page = add_menu_page("Sekvencie", "Sekvencie", "upload_files",
		"page-rotation", "page_rotation_settings_page", 'dashicons-randomize', 2);
	
	// Adds my_help_tab when my_admin_page loads
	add_action('load-'.$sequence_page, 'page_rotation_sequence_help');
}

function page_rotation_add_menu_item_screens() {
	require_once( plugin_dir_path( __FILE__ ) . "screen-management.php");
	require_once( plugin_dir_path( __FILE__ ) . "page-rotation-screen-permissions.php");
	$screen_page = add_menu_page("Obrazovky", "Obrazovky", "upload_files",
		"page-rotation-screens", "page_rotation_screen_management", 'dashicons-desktop', 1);
	
	// Adds my_help_tab when my_admin_page loads
	add_action('load-'.$screen_page, 'page_rotation_screens_help');
	
}

function page_rotation_add_menu_item_overview() {
	require_once( plugin_dir_path( __FILE__ ) . "page-overview.php");
	$overview_page= add_menu_page("Stránky", "Stránky", "upload_files",
		"page-rotation-overview", "page_rotation_overview_page", 'dashicons-admin-page', 2);
	
	// Adds my_help_tab when my_admin_page loads
	add_action('load-'.$overview_page, 'page_rotation_overview_help');
}


/**
 * Creates a category to store redirect pages
 *
 */
function page_rotation_create_sequence_category() {
	$name = "Sekvencie";
	wp_create_category( $name );

	$name = "Obrazovky";
	wp_create_category( $name );
}

/**
 * Creates necessary tables in wordpress database
 */
function page_rotation_create_db_tables() {
	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();

	$sequence_table = $wpdb->prefix . 'page_rotation_sequences';
	$sql_sequence = "CREATE TABLE IF NOT EXISTS $sequence_table(
	id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	name varchar(100) NOT NULL,
	start_page_id BIGINT(20) UNSIGNED,
	date_created DATETIME NOT NULL,
	date_modified DATETIME,
	comment TEXT,
	UNIQUE KEY (id)) $charset_collate;";


	$pages_table = $wpdb->prefix . "page_rotation_pages";
	$sql_pages = "CREATE TABLE IF NOT EXISTS $pages_table (
	id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	page_id BIGINT(20) UNSIGNED  NOT NULL,
	next_page BIGINT(20),
	prev_page BIGINT(20),
	consecutive_number BIGINT(20) NOT NULL,
	sequence_id BIGINT(20) UNSIGNED NOT NULL,
	display_time MEDIUMINT(9) NOT NULL DEFAULT 3600,
	UNIQUE KEY (id),
	FOREIGN KEY (sequence_id) REFERENCES $sequence_table (id)
	ON DELETE CASCADE ) $charset_collate;
)";

	$screens_table = $wpdb->prefix . "page_rotation_screens";
	$sql_screens = "CREATE TABLE IF NOT EXISTS $screens_table (
	id BIGINT(20) NOT NULL AUTO_INCREMENT,
	name VARCHAR(100) NOT NULL,
	sequence_id BIGINT(20) UNSIGNED,
	screen_page_id BIGINT(20) UNSIGNED,
	task_page_id BIGINT(20) UNSIGNED,
	show_progress BOOLEAN NOT NULL DEFAULT FALSE,
	UNIQUE KEY (id),
	FOREIGN KEY (sequence_id) REFERENCES {$wpdb->prefix}page_rotation_sequences(id),
	FOREIGN KEY (screen_page_id) REFERENCES {$wpdb->prefix}posts(ID) ON DELETE CASCADE,
	FOREIGN KEY (task_page_id) REFERENCES {$wpdb->prefix}posts(ID) ON DELETE CASCADE
); $charset_collate";


	$sql_tasks = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}page_rotation_tasks (
    id BIGINT(20) NOT NULL AUTO_INCREMENT,
    type varchar(30) NOT NULL,
    executed BOOLEAN NOT NULL,
    exec_time DATETIME NOT NULL,
    rep BIGINT(20) UNSIGNED NOT NULL,
    date_created DATETIME NOT NULL,
    screen_id BIGINT(20) NOT NULL,
    UNIQUE KEY(id),
    FOREIGN KEY (screen_id) REFERENCES {$wpdb->prefix}page_rotation_screens(id)
  ); $charset_collate";
	
	require( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta($sql_sequence);
    dbDelta($sql_pages);
	dbDelta($sql_screens);
	dbDelta($sql_tasks);
}

//Loads jQuery
function setup_scripts() {
	wp_enqueue_script('jquery');
	wp_enqueue_script('jquery-ui-sortable');
	wp_enqueue_script('jquery-ui-dialog');
}

//Loads external JavaScripts
function pw_load_scripts() {
	wp_enqueue_script('sortable-js', plugin_dir_url(__FILE__) . 'js/jquery-sortable.js', array(), '1.0');
	wp_enqueue_script('input-filter', plugin_dir_url(__FILE__) . 'js/pages-filter.js', array(), '1.0');
	wp_enqueue_script('warnings', plugin_dir_url(__FILE__) . 'js/warnings.js', array(), '1.0');
}

//Loads external CSS
function wpdocs_enqueue_custom_admin_style() {
	wp_register_style( 'custom_wp_admin_css', plugin_dir_url(__FILE__) . 'styles/admin-settings.css', false, '1.0.0' );
	wp_enqueue_style( 'custom_wp_admin_css' );

	wp_register_style('screen_management_css',
		plugin_dir_url(__FILE__) . 'styles/screen-management.css', false, '1.0.0');

	wp_enqueue_style('screen_management_css');
	wp_register_style(
		'jquery-ui-dialog',
		plugin_dir_url(__FILE__) . 'styles/jquery-ui.min.css'
	);
	wp_enqueue_style( 'jquery-ui-dialog' );
}

function page_rotation_check_for_redirect() {
	global $post;
	global $wpdb;

	$display_time = 60*60;
	$page_url = "";

	//Check if page is screen redirect page
	$screen = get_post_meta($post->ID,'screen',true);
	$sequence = get_post_meta($post->ID,'sequence_start',true);

	if($screen) {
		$screen_id = get_post_meta( $post->ID, 'screen_id', true );
		$sql       = "SELECT * FROM wp_page_rotation_screens
		JOIN wp_posts ON wp_page_rotation_screens.screen_page_id = wp_posts.ID
  		JOIN wp_page_rotation_sequences ON wp_page_rotation_screens.sequence_id = wp_page_rotation_sequences.id
  		JOIN wp_page_rotation_pages ON wp_page_rotation_sequences.id = wp_page_rotation_pages.sequence_id
		WHERE wp_page_rotation_screens.id = $screen_id
		LIMIT 1;";

		$result = $wpdb->get_results( $sql )[0];
		$seq_id = $result->sequence_id;
		$next_id = $result->page_id;

		//Create GET string
		$get_data = array(
			'seq' => $seq_id,
			'cons' => '1',
			'screen' => $screen_id
		);
		$get_string = http_build_query($get_data);

		//Check for next page URL
		$page_url = get_permalink($next_id);
		$page_url = $page_url . "&" . $get_string;

		$display_time = 0;
	}



	//Outputs redirect meta tag if the page is start of a sequence
	else if($sequence) {
		//Get sequence ID
		$seq_id = get_post_meta($post->ID,'sequence_id',true);

		//Query for next page
		$next_page_sql = "SELECT page_id FROM wp_page_rotation_sequences
		JOIN wp_page_rotation_pages ON wp_page_rotation_sequences.id = wp_page_rotation_pages.sequence_id
		WHERE sequence_id = $seq_id AND consecutive_number = 1;
		";

		$results = $wpdb->get_results($next_page_sql);
		$next_id = $results[0]->page_id;


		//Create GET string
		$get_data = array(
			'seq' => $seq_id,
			'cons' => '1'
		);
		$get_string = http_build_query($get_data);

		//Check for next page URL
		$page_url = get_permalink($next_id);
		$page_url = $page_url . "&" . $get_string;

		$display_time = 0;
	}

	//If page is part of a sequence output next page tag
	else if(isset($_GET['seq']) && isset($_GET['cons']) && !isset($_GET['screen'])) {

		//Retrieve GET parameters
		$seq_id = $_GET['seq'];
		$consec_number = $_GET['cons'];

		$count_sql = "SELECT COUNT(*) as count FROM wp_page_rotation_sequences
		JOIN wp_page_rotation_pages ON wp_page_rotation_sequences.id = wp_page_rotation_pages.sequence_id
		WHERE sequence_id = $seq_id;
		";
		$pages_count = $wpdb->get_results($count_sql)[0]->count;

		$next_page_sql = "SELECT next_page, display_time FROM wp_page_rotation_sequences
		JOIN wp_page_rotation_pages ON wp_page_rotation_sequences.id = wp_page_rotation_pages.sequence_id
		WHERE sequence_id = $seq_id AND consecutive_number = $consec_number
		LIMIT 1";
		$results = $wpdb->get_results($next_page_sql);

		$display_time = $results[0]->display_time;
		$next_id = $results[0]->next_page;

		if ($pages_count <= $consec_number)
			$next_cons = 1;
		else
			$next_cons = $consec_number + 1;

		//Create GET string
		$get_data = array(
			'seq' => $seq_id,
			'cons' => $next_cons
		);
		$get_string = http_build_query($get_data);

		//Check for next page URL
		$page_url = get_permalink($next_id);
		$page_url = $page_url . "&" . $get_string;

		//Output redirect meta tag
		//if no display time was set

		//echo "<meta http-equiv=\"refresh\" content=\"$display_time; url= $page_url \">";

		//If no display time is set, set it to 30 minutes
		if($display_time == 0) {
			$display_time = 60*30;
		}
	}
	//If also screen parameter is set
	else if(isset($_GET['seq']) && isset($_GET['cons']) && isset($_GET['screen'])) {

		//Retrieve GET parameters
		$sequence_id = $_GET['seq'];
		$consec_number = $_GET['cons'];
		$screen_id = $_GET['screen'];

		//Check if sequence hasn't changed
		$screen_table = $wpdb->prefix . "page_rotation_screens";
		$sql = "SELECT * FROM $screen_table WHERE id = $screen_id LIMIT 1;";
		//Returns a single result
		$result = $wpdb->get_results($sql)[0];

		$attached_sequence = $result->sequence_id;

		$sequence_table = $wpdb->prefix . "page_rotation_sequences";
		$sql = "SELECT COUNT(*) as count FROM $sequence_table
		JOIN wp_page_rotation_pages ON wp_page_rotation_sequences.id = wp_page_rotation_pages.sequence_id
		WHERE sequence_id = $attached_sequence;";
		$count = $wpdb->get_results($sql)[0]->count;


		$next_cons = 0;
		//If sequence was changed, reset consecutive number
		if ($attached_sequence != $sequence_id) {

			$next_cons = 1;

			$next_page_sql = "SELECT next_page, display_time, page_id FROM wp_page_rotation_screens
			JOIN wp_page_rotation_sequences ON wp_page_rotation_screens.sequence_id = wp_page_rotation_sequences.id
			JOIN wp_page_rotation_pages ON wp_page_rotation_sequences.id = wp_page_rotation_pages.sequence_id
			WHERE wp_page_rotation_screens.id = $screen_id AND consecutive_number= $next_cons ;
			";
            // $attached_sequence = $sequence_id;

		}
		else {
		    //If end of sequence
			if($consec_number  == $count) {
				$next_cons = 1;
			}

			$next_page_sql = "SELECT next_page, display_time, page_id FROM wp_page_rotation_screens
			JOIN wp_page_rotation_sequences ON wp_page_rotation_screens.sequence_id = wp_page_rotation_sequences.id
			JOIN wp_page_rotation_pages ON wp_page_rotation_sequences.id = wp_page_rotation_pages.sequence_id
			WHERE wp_page_rotation_screens.id = $screen_id AND consecutive_number= $consec_number ;
			";
		}



		$result = $wpdb->get_results($next_page_sql)[0];
		if ($attached_sequence != $sequence_id) {
			$display_time = 1;
			$next_id = $result->page_id;
		}
		else {
			$next_id = $result->next_page;
			$display_time = $result->display_time;
		}

		//If not new sequence or end of loop add 1
		if ($next_cons != 1)
			$next_cons = $consec_number + 1;

		//Create GET string
		$get_data = array(
			'seq' => $attached_sequence,
			'cons' => $next_cons,
			'screen' => $screen_id
		);
		$get_string = http_build_query($get_data);

		//Check for next page URL
		$page_url = get_permalink($next_id);
		$page_url = $page_url . "&" . $get_string;

		//If no display time is set, set it to 30 minutes
		if($display_time == 0) {
			$display_time = 60*30;
		}

	}


	/*
    * POSTER CHECK
	*/
	$poster = get_post_meta($post->ID,'is_poster',true);
	$main_ad = get_post_meta($post->ID,'main_ad_page',true);
	
	
	if($poster) {
		$date_end = get_post_meta( $post->ID, 'poster_end_date', true );
		$date_start = get_post_meta( $post->ID, 'poster_start_date', true );

		// Apply only if in sequence or screen
		if (isset($_GET['screen']) || isset($_GET['seq'])) {
		    // Poster is accessed before end date
            if (time() - strtotime($date_start) < 0 && $date_start > 0) {
                //Continue to next page immediately
                $display_time = 0;
            }
        }

		//Poster is past end date
		if ( time() - strtotime( $date_end ) > 0 && $date_end > 0) {
	        //Continue to next page immediately
            $display_time = 0;

            //Get sequences containing poster
			$table_name = $wpdb->prefix . "page_rotation_pages";
			$sql = "SELECT DISTINCT (sequence_id) FROM $table_name
              WHERE page_id = $post->ID";

			$sequences = $wpdb->get_results($sql);
            //Delete poster from all sequences
			foreach ($sequences as $sequence) {
			    //Get one sequecne
			    $sql = "SELECT * FROM $table_name WHERE sequence_id = $sequence->sequence_id
			    ORDER BY consecutive_number";
			    $results = $wpdb->get_results($sql);

			    $delimiter = 0;
			    $i = 0;
			    foreach ($results as $page) {

                    //If page is refering the poster
                    if( $page->next_page == $post->ID) {

                        //If page is last in the sequence
	                    if($i == count($results) -1) {
		                    //Traverse through the list from the beginning
	                        $counter = 0;
		                    $new_next = $results[$counter]->page_id;
	                        while ($new_next == $post->ID) {
	                            $counter++;
		                        $new_next = $results[$counter]->page_id;
                            }
	                    }
	                    //If page is not last
	                    else {
		                    //Traverse through the list until page that doesn't contain the poster is found (in case multiple posters are following)
                            $counter = $i + 1;
		                    $new_next = $results[ $counter ]->next_page;

		                    while ( $new_next == $post->ID ) {
                                $counter ++;

			                    //If we need to travel past the last item in sequence
			                    if ($counter >= count($results)) {
			                        $counter = 0;
                                }

                                $new_next = $results[ $counter ]->next_page;


		                    }
	                    }
	                    $wpdb->update($table_name,array('next_page' => $new_next),array( 'consecutive_number' => $i+1,
                                                                                         'page_id' =>$page->page_id,
                                                                                         'sequence_id' => $sequence->sequence_id));
                    }

				    //If page in sequence is a desired poster, increase the delimiter
                    if( $page->page_id == $post->ID ) {
                        $delimiter++;
                    }

                    //Change consecutive numbers to preserve correct order
                    if ($delimiter > 0) {
                        $new_cons = $page->consecutive_number - $delimiter;
	                    $wpdb->update($table_name,array('consecutive_number' => $new_cons),array( 'consecutive_number' => $i+1,
                                                                                                  'page_id' => $page->page_id,
                                                                                                  'sequence_id' => $sequence->sequence_id));
                    }
				    $i++;
                }
				$wpdb->delete($table_name,array( 'page_id' => $post->ID, 'sequence_id' => $sequence->sequence_id));
            }
		}





	}
	
	/*
	 * MAIN AD PAGE
	 */
	else if ($main_ad || get_post_meta($post->ID,'is_ad',true)) {
	    //Get ad cons number
	    if (!isset($_GET['ad'])) {
	        $cons_ad = 0;
        }
        else {
	        $cons_ad = $_GET['ad'];
        }
        
	    $screen_id = $_GET['screen'];
	    $count_companies = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}fiit_ad_companies WHERE !even_week = (week(now()) % 2)");
	    // Count screens with active ads
	    $screen_count = page_rotation_count_ad_screens();
	    
        $no_of_ads_pre_screen = ceil($count_companies / (float)$screen_count);
	    /*
	    $offset = $wpdb->get_var("SELECT (row_number-1 + (HOUR(now()) % $count_companies)) % $count_companies as offset  FROM (
        SELECT @curRow := @curRow + 1 AS row_number, wp_page_rotation_screens.id as screen_id FROM wp_page_rotation_screens
        join wp_page_rotation_sequences on wp_page_rotation_screens.sequence_id = wp_page_rotation_sequences.id
        join wp_page_rotation_pages on wp_page_rotation_sequences.id = wp_page_rotation_pages.sequence_id
          JOIN    (SELECT @curRow := 0) r
        where page_id = (select post_id from wp_postmeta where meta_key like 'main_ad_page' LIMIT 1)) as rn
        WHERE screen_id = $screen_id;");
	    */
	    
        $offset = $wpdb->get_var("SELECT (row_number-1 + ((HOUR(NOW()) * 2 + FLOOR(MINUTE(NOW()) / 30)) % $count_companies)) % $count_companies as offset  FROM (
        SELECT @curRow := @curRow + 1 AS row_number, wp_page_rotation_screens.id as screen_id FROM wp_page_rotation_screens
        join wp_page_rotation_sequences on wp_page_rotation_screens.sequence_id = wp_page_rotation_sequences.id
        join wp_page_rotation_pages on wp_page_rotation_sequences.id = wp_page_rotation_pages.sequence_id
        JOIN    (SELECT @curRow := 0) r
        where page_id = (select post_id from wp_postmeta where meta_key like 'main_ad_page' LIMIT 1)) as rn
        WHERE screen_id = $screen_id;");
	    
	    $company_to_display = $wpdb->get_var("SELECT id FROM wp_fiit_ad_companies WHERE !even_week = (week(now()) % 2) LIMIT 1 OFFSET $offset;");
     
	    
	    $ads_to_display = $wpdb->get_results("SELECT * FROM wp_fiit_ad_company_ads WHERE comp_id = $company_to_display ORDER BY id;");
	    //wp_die(var_dump($companies_ids  ));
    
        $url = get_permalink($ads_to_display[$cons_ad]->ad_page_id);
	    $url = add_query_arg(['seq' => $_GET['seq'], 'cons' => $_GET['cons'], 'screen' => $_GET['screen'], 'ad' => $cons_ad + 1], $url);
     
	    if($main_ad) {
		    $display_time = 0;
		
		    $minute_dif = $wpdb->get_var("
                            SELECT FLOOR((UNIX_TIMESTAMP(now()) - UNIX_TIMESTAMP(date_created)) / 60) FROM {$wpdb->prefix}fiit_ad_log
                            WHERE screen_id = $screen_id
                            ORDER BY  date_created DESC LIMIT 1;");
		    if (($minute_dif == null ||$minute_dif > 30) && $count_companies > 0) {
                $wpdb->insert("{$wpdb->prefix}fiit_ad_log",['screen_id' => $screen_id, 'company_id' => $company_to_display]);
                $page_url = $url;
		    }
        }
	    
	    else if (count($ads_to_display) > $cons_ad) {
	      $page_url = $url;
	      $display_time = 15;
        
        }
        else if (count($ads_to_display) == $cons_ad) {
          $display_time = 15;
        }
	    
	    //wp_die($count_companies);
	    //wp_die($screen_count);
	    //wp_die($no_of_ads_pre_screen);

    }

    
	//Output redirect meta tag
	echo "<meta http-equiv=\"refresh\" content=\"$display_time; url= $page_url \">";

}

function page_rotation_count_ad_screens() {
    global $wpdb;
    $sql = "SELECT COUNT(DISTINCT(screen_page_id)) as screen_count FROM {$wpdb->prefix}page_rotation_screens
            join {$wpdb->prefix}page_rotation_sequences on {$wpdb->prefix}page_rotation_screens.sequence_id = {$wpdb->prefix}page_rotation_sequences.id
            join {$wpdb->prefix}page_rotation_pages on {$wpdb->prefix}page_rotation_sequences.id = {$wpdb->prefix}page_rotation_pages.sequence_id
            where page_id = (select post_id from {$wpdb->prefix}postmeta where meta_key like 'main_ad_page' LIMIT 1);
            ";
    return $wpdb->get_var($sql);
}

function page_rotation_output_countdown() {
	global $wpdb;

	$display_time = 0;
	//Get display time for current page
	if(isset($_GET['seq']) && isset($_GET['cons'])) {
		//Retrieve GET parameters
		$sequence_id = $_GET['seq'];
		$consec_number = $_GET['cons'];

		$display_time_sql = "SELECT display_time FROM {$wpdb->prefix}page_rotation_sequences
		JOIN {$wpdb->prefix}page_rotation_pages ON {$wpdb->prefix}page_rotation_sequences.id = {$wpdb->prefix}page_rotation_pages.sequence_id
		WHERE sequence_id = $sequence_id AND consecutive_number = $consec_number
		LIMIT 1";
		$results = $wpdb->get_results($display_time_sql);
		$display_time = $results[0]->display_time;

	}
	if (isset($_GET['ad'])) {
	    $display_time = 15;
    }

	//If display time is greater than zero display countdown
	if ($display_time > 0 && page_rotation_get_progress_bar_binary_settings($_GET['screen'])) {
	    ?>
		<div id="page-countdown" style="background: white; width: 100%; height: 20px; border-radius:0 25px 25px 0;"></div>
		<script type="text/javascript">
			var countdown = jQuery('#page-countdown');
			var interaval = <?php echo $display_time?> * 1000;

			countdown.animate({width: 0},interaval);
		</script>
		<?php
	}
}

require_once ('screen-management.php');
//Frontend hooks
add_action('wp_head','page_rotation_check_for_redirect');
add_action('wp_head','page_rotation_check_for_task_page');
//Admin hooks
add_action("admin_menu", "page_rotation_add_menu_item");
add_action("admin_menu", "page_rotation_add_menu_item_screens");
add_action("admin_menu", "page_rotation_add_menu_item_overview");
add_action('admin_enqueue_scripts', 'setup_scripts');
add_action('admin_enqueue_scripts', 'pw_load_scripts');

add_action( 'admin_enqueue_scripts', 'wpdocs_enqueue_custom_admin_style' );
register_activation_hook( __FILE__, 'page_rotation_create_db_tables' );
register_activation_hook( __FILE__, 'page_rotation_create_sequence_category' );

add_action('after_body','page_rotation_output_countdown');


function page_rotation_exclude_categories($query) {
	$category_name = "Sekvencie";

	//Get ID of the 'sequence' category
	$se_cat_id = get_cat_ID($category_name);

    $category_name = "Obrazovky";

	//Get ID of the 'sequence' category
	$obr_cat_id = get_cat_ID($category_name);
    $query->set('cat', "-$se_cat_id -$obr_cat_id");

	return $query;
}

function page_rotation_get_progress_bar_binary_settings($screen_id) {
	global $wpdb;
	return $wpdb->get_var("SELECT show_progress FROM {$wpdb->prefix}page_rotation_screens WHERE id = $screen_id");
}

//add_filter('pre_get_posts', 'page_rotation_exclude_categories');
