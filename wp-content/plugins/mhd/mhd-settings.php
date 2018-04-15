<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 12.7.2016
 * Time: 11:16
 */

/**
 * Finds bus stop name
 * @param $stop Name to search
 *
 * @return mixed
 */
function mhd_find_stop_name($stop) {
	require_once( plugin_dir_path(__FILE__) . 'simplehtmldom/simple_html_dom.php');
	$stop = str_replace(' ','+',$stop);
	$url = "http://imhd.sk/ba/vyhladavanie?hladaj=" . $stop;

	//Randomize browsers
	require_once(plugin_dir_path(__FILE__) .  'random_agent.php');

	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT,random_user_agent());
	$respone = curl_exec($ch);
	curl_close($ch);
	$html = str_get_html($respone);

	$full_name = $html->find('h2')[0]->plaintext;
	return  str_replace('Zastávka ','',$full_name);
}

/**
 * Creates an array of URLs leading to all timetables of buses travelling through specified bus stop
 * @param $stop Name of the bus stop
 *
 * @return array array of URLs to bus timetables
 */
function mhd_find_all_buses($stop) {
	require_once( plugin_dir_path(__FILE__) . 'simplehtmldom/simple_html_dom.php');
	$stop = str_replace(' ','+',$stop);
	$url = "http://imhd.sk/ba/vyhladavanie?hladaj=" . $stop;

	//Randomize browsers
	require_once(plugin_dir_path(__FILE__) .  'random_agent.php');

	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT,random_user_agent());
	$respone = curl_exec($ch);
	curl_close($ch);

	$html = str_get_html($respone);
	$html = $html->find('table')[0];
	$counter = 0;
	$buses = array();
	foreach ($html->find('a') as $link) {
		if($counter % 2)
			$buses[] =  "http://www.imhd.sk" . $link->href;
		$counter++;
	}

	return $buses;
}

function mhd_parse_timetable($bus_url,$stop_entry_id) {
	require_once(plugin_dir_path(__FILE__) .  'random_agent.php');

	$ch = curl_init($bus_url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_USERAGENT,random_user_agent());
	$respone = curl_exec($ch);
	curl_close($ch);


	$html = str_get_html($respone);

	//Finds date since when the timetable is valid
	$header = $html->find('table[class=zakladne_info]')[0];
	$valid_since = $header->find('td[class=platnost]')[0]->find('strong')[0]->plaintext;
	$valid_since = preg_replace("/[^0-9,.]/", "", $valid_since);
	$valid_since =  date("Y-m-d H:i:s",strtotime($valid_since));

	//Bus number
	$bus_number = $header->find('td[class=nazov_linky]')[0]->plaintext;
	$bus_number = preg_replace('/\s+/',"",$bus_number);
	//Finds end stop name
	$end_stop = $header->find('td[class=zastavka_smer]')[0]->find('span[class=smer]')[0]->plaintext;
	$end_stop = substr($end_stop, 5);

	global $wpdb;
	$wpdb->insert('wp_mhd_lines',array(
		'end_stop' => $end_stop,
		'line_no' => $bus_number,
		'stop_id' => $stop_entry_id,
		'valid_since' => $valid_since));

	$line_id = $wpdb->insert_id;

	$whole_timetable = $html->find('table[class=cp_obsah]')[0];
	
	//Finds number of timetable variatiopns (ie. Working days, Weekend, All days,...)
	$day_variations = $whole_timetable->find('td[class=nazov_dna]');
	$no_of_day_variations = count($whole_timetable->find('td[class=nazov_dna]'));

	//Cycle through all variations
	$whole_timetable = $whole_timetable->find('table[class=cp_odchody_tabulka_max]');
	for($i=0;$i<$no_of_day_variations;$i++) {
		$day =	preg_replace('/&nbsp;/',' ',$day_variations[$i]->plaintext);
		foreach( $whole_timetable[$i]->find('tr[class=cp_odchody]') as $row) {
			$hour =  $row->find('td[class=cp_hodina]')[0];
			foreach ($row->find('td') as $minute) {
				if($minute->class != 'cp_odchody_doplnenie' && $minute->class != 'cp_hodina') {
					$valid_minute = preg_replace("/[^0-9,.]/", "",$minute->plaintext);
					$time =  date('Y-m-d H:i:s',mktime($hour->plaintext,$valid_minute,0,1,1,2016));
					$wpdb->insert('wp_mhd_times',array( 'line_id' => $line_id,
						'days' => $day,
						'time' => $time));
				}
			}
		}
	}
}

/**
 * Creates an entry in DB with given bus stop name
 * @param $stop_name
 *
 * @return int ID of created entry
 */
function mhd_create_stop_entry($stop_name) {
	global $wpdb;

	$wpdb->insert('wp_mhd_stops',array('name' => $stop_name , 'date_created' => date("Y-m-d H:i:s")));
	$entry_id= $wpdb->insert_id;
	return $entry_id;
}


function mhd_parse_free_days() {
	global $wpdb;

	$school_holidays = "http://kalendar.azet.sk/prazdniny/";
	$state_holidays = "http://kalendar.azet.sk/sviatky/";

	require_once( plugin_dir_path(__FILE__) . 'simplehtmldom/simple_html_dom.php');

    $ch = curl_init($state_holidays);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $respone = curl_exec($ch);
    curl_close($ch);

    $html = str_get_html($respone);

	//Parse state holidays
	foreach ($html->find('a') as $element) {
		$href = $element->href;
		//Filter valid elements
		if( preg_match('/\/sviatky\/popis\//',$href) ) {
			//Remove unnecessary characters
			$holiday =  preg_replace($patterns = array('/\/sviatky\/popis\//','/\//'),"",$href);
			$holiday_date = date($holiday);
			//Extract holiday name
			$name = $element->plaintext;

			//Insert into DB
			$wpdb->query("INSERT IGNORE INTO wp_mhd_free_days (date,name,type) VALUES ('$holiday_date','$name','state_holiday');");
			//$wpdb->insert("wp_mhd_free_days",array("date" => $holiday_date, "name" => $name, "type" => "state_holiday"));
		}
	}

	//Parse school holidays
    
    $ch = curl_init($school_holidays);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $respone = curl_exec($ch);
    curl_close($ch);

    $html = str_get_html($respone);

	foreach ($html->find('tr') as $element) {
		//If not empty string and not regional holiday outside Bratislava
		if(preg_match('/[A-z]/',$element->plaintext)
		   && (!preg_match('/kraj/',$element->plaintext) || preg_match('/Bratislav/',$element->plaintext))) {
			//Day range
			if( count($element->find('a')) == 2  ) {
				$start_day = $element->find('a')[0]->href;
				$start_day = preg_replace($patterns = array('/\/prazdniny\/den\//','/\//'),"",$start_day);
				$start_day = new DateTime($start_day);
				$end_day = $element->find('a')[1]->href;
				$end_day = preg_replace($patterns = array('/\/prazdniny\/den\//','/\//'),"",$end_day);
				$end_day = new DateTime($end_day);
				$name =  $element->find('td')[1]->plaintext;

				//Create entries for range start - end
				$counter = $start_day;
				while( $counter->diff($end_day)->format("%R%a") >= 0 ) {
					$db_day = $counter->format("Y-m-d");
					$wpdb->query("INSERT IGNORE INTO wp_mhd_free_days (date,name,type) VALUES ('$db_day','$name','school_holiday');");
					//$wpdb->insert("wp_mhd_free_days",array('name'=> $name, 'type' => 'school_holiday', 'date' => $db_day));
					$counter->modify('+1 day');
				}
			}
			//Single day
			else {
				$day = $element->find('a')[0]->href;
				$day = preg_replace($patterns = array('/\/prazdniny\/den\//','/\//'),"",$day);
				$name =  $element->find('td')[1]->plaintext;
				$wpdb->query("INSERT IGNORE INTO wp_mhd_free_days (date,name,type) VALUES ('$day','$name','school_holiday');");
				//$wpdb->insert("wp_mhd_free_days",array('name' => $name, 'date' => $day, 'type'=>'school_holiday'));
			}
		}
	}
}

/**
 * Returns array of all IDs from mhd stops
 * @return array
 */
function mhd_get_all_stops_ids() {
	
	global $wpdb;
	$sql = "SELECT id FROM wp_mhd_stops";
	
	$ids = array();
	$id_results = $wpdb->get_results($sql);
	foreach ($id_results as $key => $row) {
		$ids[] = $row->id;
	}
	
	return $ids;
}


/**
 * Removes all bus entries from DB on gived stop ID
 * @param $stop_id
 */
function mhd_delete_buses($stop_id) {
	global $wpdb;
	
	$table_name = $wpdb->prefix . 'mhd_lines';
	$wpdb->delete($table_name, array( 'stop_id' => $stop_id));
}


function mhd_update_all_lines() {
	
	global $wpdb;
	
	//Retrieve all IDs of MHD stops
	$stops = mhd_get_all_stops_ids();
	
	foreach ( $stops  as $stop ) {
		
		//Get name of the stop
		$sql = "SELECT name FROM wp_mhd_stops WHERE id = $stop";
		$name = $wpdb->get_results($sql)[0]->name;
		
		//Delete old buses
		mhd_delete_buses($stop);
		
		//Find all buses that have this stop
		$all_buses = mhd_find_all_buses( $name );
	
		//Update each bus
		foreach ( $all_buses as $bus ) {
			mhd_parse_timetable( $bus, $stop );
		}
		
		//Update date of creation
		$wpdb->update( 'wp_mhd_stops', array( 'date_created' => date( "Y-m-d H:i:s" ) ), array( 'id' => $stop ) );
	}
}


/**
 * MHD settings page markup
 */
function mhd_settings_page() {
    mhd_register_settings_scripts();
    
	global $wpdb;
	if(isset($_POST['submit'])) {
		$all_buses = mhd_find_all_buses($_POST['stop']);
		$stop_name = mhd_find_stop_name($_POST['stop']);
		$stop_entry_id = mhd_create_stop_entry($stop_name);
		foreach ($all_buses as $bus) {
			mhd_parse_timetable( $bus, $stop_entry_id );
		}
		mhd_create_stop_wp_page($stop_entry_id, $stop_name);
	}

	if(isset($_POST['sviatky'])) {
		mhd_parse_free_days();
	}
	
	if(isset($_POST['obnova'])) {
		mhd_update_all_lines();;
	}

	mhd_enqueue_admin_styles();
	?>

	<div id="MHD" class="wrap">
        <h1>MHD</h1>
        <div class="stops-table">
        <?php
            
            require_once(plugin_dir_path(__FILE__) . '/Mhd_List.php');
            $table = new Mhd_List();
            $table->prepare_items();
            $table->display();
        
        ?>
        </div>
		<form id="mhd-control-form" action="<?php $_SERVER['PHP_SELF'] ?>" method="post">
			<div id="input">
                <h3>Pridanie zastávky</h3>
				<label for="stop">Názov zastavky</label>
				<input id="stop" type="text" name="stop" class="stop-name">
				<input type="submit" name="submit" class="button-primary" value="Pridaj">
				<input type="submit" name="sviatky" class="button-secondary" value="Nacitaj sviatky">
				<input type="submit" id="obnova" name="obnova" class="button-secondary" value="Obnov databázu">
			</div>
		</form>

        <div id="dialog-obnova" title="Obnoviť databázu?">
            <p><span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>Všetky rozpisy budú obnovené. Ste si istý? Proces môže trvať dlhší čas.</p>
        </div>
        <div id="dialog-delete" title="Zmazať záznam?">
            <p><span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>Ste si istý, že chcete zmazať tento záznam? Krok sa nedá vrátiť naspäť.</p>
        </div>
	</div>
	<?php
}

/**
 * Creates a default page with loaded MHD widget
 * @param $stop_id
 * @param $stop_name
 * @return mixed
 */
function mhd_create_stop_wp_page($stop_id, $stop_name) {

    $post_id = wp_insert_post([
       "post_title" => "MHD - {$stop_name}",
        "post_type" => "page",
        "post_content" => "[mhd id='{$stop_id}']"
    ]);

//    $wp_postmeta_key = "_sidebars_widgets";
//    $wp_postmeta_value = "a:3:{s:19:\"wp_inactive_widgets\";a:0:{}s:9:\"sidebar-2\";a:1:{i:0;s:12:\"mhd_widget-2\";}s:13:\"array_version\";i:3;}";
//    $wp_postmeta_sidebar_enabled_key = "_customize_sidebars";
//    $wp_postmeta_sidebar_enabled_value = "1";
//    $wp_options_key = "widget_{$post_id}_mhd_widget";
//    $wp_options_value = "{i:2;a:1:{s:5:\"stops\";s:1:\"{$stop_id}\";}s:12:\"_multiwidget\";i:1;}";
//
//    add_post_meta($post_id, $wp_postmeta_key, $wp_postmeta_value);
//    add_post_meta($post_id, $wp_postmeta_sidebar_enabled_key, $wp_postmeta_sidebar_enabled_value);
//    add_option($wp_options_key, $wp_options_value);

    return $post_id;
}

function mhd_register_settings_scripts() {
	//For warnings
	wp_enqueue_script('mhd-script', plugin_dir_url(__FILE__) .
	                                'js/warnings.js',array('jquery'),1.0);
}