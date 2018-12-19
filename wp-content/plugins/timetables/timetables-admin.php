<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 12.6.2016
 * Time: 20:35
 */

/**
 * Creates admin page
 */
function timetables_admin_page() {

	//Load new timetable
	if ( isset( $_POST['submit'] ) ) {
		if( isset( $_POST['mistnost'] ) ) {
			$timetable = timetables_get_whole_timetable($_POST['mistnost']);
			$parsed_timetable = timetables_parse($timetable);
			timetables_create_db_entry($parsed_timetable);
		}
	}

	//Delete selected timetables
	else if( isset( $_POST['Delete']) ) {
		if( isset( $_POST['rows'] ) ) {
			$rows = $_POST['rows'];
			foreach ($rows as $row) {
				timetables_delete($row);
			}

		}
	}
	
	else if ( isset($_POST['update_timetables'])) {
	    timetables_update_all_timetables();
    }
    
	


	timetables_admin_markup();
}

function timetables_delete($row_id) {
	global $wpdb;
	$table_name = $wpdb->prefix . "timetables_timetables";
	$wpdb->delete($table_name,array('id' => $row_id));
}

function timetables_create_db_entry($parsed_timetable) {
	$term = timetables_get_last_term_name();

	$room = $parsed_timetable[0]->getRoom();

	global $wpdb;
	$table_name = $wpdb->prefix . "timetables_timetables";
	$wpdb->insert($table_name,array('term' => $term, 'room' => $room, 'created' =>  date("Y-m-d H:i:s", time()) ));
	//ID of the created entry
	$lastid = $wpdb->insert_id;

	timetables_create_db_time_entries($parsed_timetable,$lastid);
}

function timetables_create_db_time_entries($parsed_timetable, $timetable_id) {
    global $wpdb;
    
	//Creates all entries for each lesson
	$table_name = $wpdb->prefix . "timetables_lessons";
	foreach ($parsed_timetable as $lesson) {
		$lesson_start = timetables_parse_start_time($lesson->getStartTime());
		$lesson_end = timetables_parse_end_time($lesson->getEndTime());
		$teacher = $lesson->getTeacher();
		$lesson_name = $lesson->getName();
		$day = timetables_parse_day_of_week($lesson->getDayOfWeek());
		
		$wpdb->insert($table_name,array(
			'name' => $lesson_name,
			'day_of_week' => $day,
			'start_time' => $lesson_start,
			'end_time' => $lesson_end,
			'teacher' => $teacher,
			'timetable_id' => $timetable_id
		));
	}
}

/**
 * Parses start time for SQL query
 * @param $time time to be parsed
 *
 * @return bool|string parsed time
 */
function timetables_parse_start_time($time) {
	return date('Y-m-d H:i:s',mktime($time,0,0,1,1,2016));
}

/**
 * Parses end time (substracts 10 minutes) for SQL query
 * @param $time time to be parsed
 *
 * @return bool|string parsed time
 */
function timetables_parse_end_time($time) {
	return date('Y-m-d H:i:s',mktime($time - 1,50,0,1,1,2016));
}

/**
 * Parses day from string Slovak format into integer
 * @param $day day to be parsed
 *
 * @return int number of day in the week
 */
function timetables_parse_day_of_week($day) {
	switch($day) {
		case 'Po': return 0;
		case 'Ut': return 1;
		case 'St': return 2;
		case 'Št': return 3;
		case 'Pi': return 4;
		case 'So': return 5;
		case 'Ne': return 6;
	}
}

class Lesson {
	var $start_time;
	var $end_time;
	var $teacher;
	var $name;
	var $day_of_week;
	var $room;

	/**
	 * Lesson constructor.
	 *
	 * @param $start_time
	 * @param $end_time
	 * @param $teacher
	 * @param $name
	 * @param $day_of_week
	 * @param $room
	 */

	public function __construct( $start_time, $end_time, $teacher, $name, $day_of_week, $room ) {
		$this->start_time  = $start_time;
		$this->end_time    = $end_time;
		$this->teacher     = $teacher;
		$this->name        = $name;
		$this->day_of_week = $day_of_week;
		$this->room        = $room;
	}


	/**
	 * @return mixed
	 */
	public function getStartTime() {
		return $this->start_time;
	}

	/**
	 * @return mixed
	 */
	public function getEndTime() {
		return $this->end_time;
	}

	/**
	 * @return mixed
	 */
	public function getTeacher() {
		return $this->teacher;
	}

	/**
	 * @return mixed
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return mixed
	 */
	public function getDayOfWeek() {
		return $this->day_of_week;
	}

	/**
	 * @return mixed
	 */
	public function getRoom() {
		return $this->room;
	}



}

/**
 * Parses the given timetable, resulting in an array of objects with lessons from the given timetable
 * @param $timetable Timetable to be parsed
 *
 * @return array Array of lesson objects, contains all lessons with start and end time and their teacher
 */
function timetables_parse($timetable) {

	//Create object for html parsing
	$timetable = str_get_html($timetable);
	$begin = 0;

	//Find all hours in the timetable
	$valid = false;
	foreach($timetable->find('th[class="zahlavi"]') as $element) {
		foreach ($element->find('small') as $hour) {
			//Excludes column with no time atribute
			if($hour->plaintext != 'Deň') {
				//Dump DOM object to string and adds it to the array
				$begin = $hour->plaintext;
				$begin = intval(substr($begin,0,1));
				$valid = true;
				break;
			}
		}
		//If valid time was found break the main loop
		if($valid) {
			break;
		}
	}

	//Updates start time as a start of the new day
	$start = $begin;
	$all_lessons = array();
	//Extract just first table in HTML - it has the timetable
	foreach ($timetable->find('table') as $table) {
		//For each row in the table
		foreach ( $table->find( 'tr' ) as $row ) {
			//For each column in the table
			foreach ( $row->find( 'td' ) as $data ) {
				//Extracts day from tablerow
				if ( ($data->class == "zahlavi") && ($data->plaintext != "") ) {
					$day = $data->plaintext;
					//Eliminate dividing lines in the table
				} elseif ( $data->class != "odsazena" ) {
					//Block larger than one hour
					if ( $data->colspan != "" ) {
						//Mark block start and finish time
						$lesson_start = $start;
						$lesson_end = $start += $data->colspan;
						$lesson_name = "";
						$teacher = "";
						//Valid lesson entry
						if($data->class == 'rozvrh-cvic' || $data->class == 'rozvrh-pred') {
							$counter = 0;
							foreach ( $data->find( 'a' ) as $anchor ) {
								$counter ++;
								//Lesson room
								if( $counter == 1) {
									$room = $anchor->plaintext;
									$room = str_replace(" (BA-MD-FIIT)","",$room);
								}

								//Lesson name
								if ( $counter == 2 ) {
									$lesson_name = $anchor->plaintext;
								} //Teacher
								elseif ( $counter == 3 ) {
									$teacher = $anchor->plaintext;
								}
							}
							//Creates new lessson object and adds it to the array
							$lesson = new Lesson($lesson_start,$lesson_end,$teacher,$lesson_name,$day,$room);
							$all_lessons[] = $lesson;

						}
					//Empty gap
					} elseif( ($data->class != "zahlavi")) {
						$start ++;
					}
				//Not a valid timetable entry - ie. dividing line
				}
			}

			//Updates start time as the start of the new day
			$start = $begin;
		}
		//Breaks the cycle, only first table is needed
		break;
	}

	return $all_lessons;

}

/**
 * Gets whole, unparsed HTML timetable, based on provided id by the user
 * @param $selected timetable id
 */
function timetables_get_whole_timetable( $selected ) {

	$post = [
		'mistnost' => $selected,
		'rozvrh' => timetables_get_last_term_id(),
		'den' => '0',
		'f' => '70',
		'format' => 'html',
		'garant' => '0',
		'lang' => 'sk',
		'predmet' => '0',
		'rocnik' => '0',
		'skupina' => '0',
		'studijni_zpet' => '0',
		'ucitel' => '0',
		'ustav' => '0',
		'z' => '0',
		'zobraz' => 'Zobrazi»',
	];

	/*
	 * Get whole page with all options using curl
	 */
	$ch = curl_init('https://is.stuba.sk/katalog/rozvrhy_view.pl');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	curl_setopt($ch, CURLOPT_ENCODING ,"UTF-8");
	$respone = curl_exec($ch);
	curl_close($ch);

	//Charset decode, otherwise it wouldn't show correct names in Slovak
	/*
	 * Changed on 5.8.2016, probably change in AIS, no longer needed
	 */
	//$respone = iconv('ISO-8859-2', 'UTF-8', $respone);

	return $respone;
}


/**
 * Gets last term name
 * @return mixed last term name - ie. LS 2015/16
 */
function timetables_get_last_term_name() {
	$html = timetables_get_all_terms();
	foreach($html->find('td') as $element) {
		if(strpos($element,'LS') || strpos($element,'ZS') ) {
			$term = $element->plaintext;
		}
	}
	return $term;
}

/**
 * Gets newest term ID
 */
function timetables_get_last_term_id() {
	$html = timetables_get_all_terms();
	$stack = array();
	foreach($html->find('input[type="checkbox"]') as $element) {
		array_push( $stack, $element->value );
	}
	return $last_checkbox_id =  array_pop($stack);
}

/**
 * Gets all timetables in the last term
 */
function timetables_get_all_timetable_options() {
	$term_id = timetables_get_last_term_id();
	//Post parameters
	$post = [
		'rozvrh' => $term_id,
	];

	/*
	 * Get whole page with all options using curl
	 */
	$ch = curl_init('https://is.stuba.sk/katalog/rozvrhy_view.pl');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	$respone = curl_exec($ch);
	curl_close($ch);

	/*
	 * Filter options to just get rooms
	 */
	$html = str_get_html($respone);
	$option_list = "";
	foreach ($html->find('select[name="mistnost"]') as $element) {
		$option_list = $element;
	}

	return $option_list;
}


function timetables_admin_markup() {

	timetables_enqueue_admin_styles();
    timetables_enqueue_scripts();
	$options = timetables_get_all_timetable_options();
	?>
    <div class="wrap">
        <h1>Rozvrhy</h1>
        <div class="timetables-table">
        <?php
            require_once(plugin_dir_path(__FILE__) .'/Timetables_List.php');
            $table = new Timetables_List();
            $table->prepare_items();
            $table->display();
        ?>
        </div>
            <h3>Pridanie nového rozvrhu</h3>
            <form method="post" name="form" action="<?php $_SERVER['PHP_SELF'] ?>"
            <select name="rooms" size="10">
                <?php
                print $options;
                ?>
            </select>
        
            <div id="submit">
                <input type="submit" name="submit" value="Pridať" class="button button-primary">
                <input id="obnova" type="submit" name="update_timetables" value="Obnoviť databázu" class="button button-secondary"
            </div>
            </form>
        <div id="dialog-delete" title="Zmazať záznam?">
            <p><span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>Ste si istý, že chcete zmazať tento záznam? Krok sa nedá vrátiť naspäť.</p>
        </div>
        <div id="dialog-obnova" title="Obnoviť databázu?">
            <p><span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>Všetky rozpisy budú obnovené. Ste si istý? Proces môže trvať dlhší čas.</p>
        </div>
    </div>
	<?php
}

/**
 * Gets all available terms which have timetables from AIS
 */
function timetables_get_all_terms() {

	require_once(plugin_dir_path(__FILE__) . 'simplehtmldom/simple_html_dom.php');
	
	$ch = curl_init('https://is.stuba.sk/katalog/rozvrhy_view.pl?konf=1;f=70;lang=sk');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$respone = curl_exec($ch);
	curl_close($ch);

	$html = str_get_html($respone);

	return $html;
}

function timetables_get_all_timetables(){
	global $wpdb;
	
	$sql = 'SELECT id, room, created FROM wp_timetables_timetables';
	return $wpdb->get_results($sql);
}

function timetables_delete_time_entries($timetable_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . "timetables_lessons";
    $wpdb->delete($table_name,array ('timetable_id' => $timetable_id));
}

function timetables_update_time_created_for_entry($timetable_id) {
    global $wpdb;
	
	$table_name = $wpdb->prefix . "timetables_timetables";
	$wpdb->update($table_name, array( 'created' => date( "Y-m-d H:i:s" )),array('id' =>$timetable_id));
}

function timetables_update_term_created_for_entry($timetable_id) {
    global $wpdb;
	$term = timetables_get_last_term_name();
	$table_name = $wpdb->prefix . "timetables_timetables";
	$wpdb->update($table_name, array( 'term' => $term),array('id' =>$timetable_id));
}

function timetables_update_all_timetables() {
	
	global $wpdb;
	//Get all created timetables
	$all_timetables        = timetables_get_all_timetables();
	$all_timetable_options = timetables_get_all_timetable_options();
	
	foreach ( $all_timetables as $timetable ) {
		
		//Find timetable id in the list off all available timetables
		foreach ( $all_timetable_options->find( 'option' ) as $timetable_option ) {
			
			//If the string matching room name is found
			if ( strpos( $timetable_option->plaintext, $timetable->room ) !== false ) {
				//Id for AIS database
				$timetable_id = $timetable_option->value;
				//Id in local database
				$timetable_local_id = $timetable->id;
				
				
				//Delete time entries for timetable
				timetables_delete_time_entries( $timetable_local_id );
				
				//Update created time in db
				timetables_update_time_created_for_entry( $timetable_local_id );
				
				//Update term
				timetables_update_term_created_for_entry( $timetable_local_id );
				
				$whole_timetable  = timetables_get_whole_timetable( $timetable_id );
				$parsed_timetable = timetables_parse( $whole_timetable );
				timetables_create_db_time_entries( $parsed_timetable, $timetable_local_id );
				
				break;
			}
		}
	}
}

function timetables_enqueue_scripts() {
	//For warnings
	wp_enqueue_script('timetables-script', plugin_dir_url(__FILE__) .
	                                'js/warnings.js',array('jquery'),1.0);
}