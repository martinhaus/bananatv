<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 30.1.2017
 * Time: 20:19
 */

/**
 * Plugin Name
 *
 * @package     ZSHL registrácia
 * @author      Martin Hauskrecht
 * @copyright   2016 Martin Hauskrecht
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: ZSHL registrácia
 * Plugin URI:  http://martinhaus.sk/zshl-reg
 * Description: Vytváranie a správa registračných formulárov pre ZSHL
 * Version:     1.0.0
 * Author:      Martin Hauskrecht
 * Author URI:  http://martinhaus.sk
 * Text Domain: zshl
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */



//Pridanie menu
function zshl_add_menu_item() {
	require_once (plugin_dir_path(__FILE__) . "zshl-registration-settings.php");
	add_menu_page("ZSHL registrácia", "ZSHL registrácia", "manage_options",
		"zshl-settings", "zshl_settings_markup", 'dashicons-welcome-write-blog', 99);
}

add_action('admin_menu','zshl_add_menu_item');

function zshl_create_initial_db_tables() {
	
	global $wpdb;
	
	$charset_collate = $wpdb->get_charset_collate();
	$sql_regs = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}registrations (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		name varchar(100) NOT NULL,
		date_created DATETIME NOT NULL,
		date_updated DATETIME NOT NULL,
		start_date DATETIME NOT NULL,
		end_date DATETIME NOT NULL,
		no_of_teams SMALLINT(5) NOT NULL,
  		UNIQUE KEY(id)
	)$charset_collate";
	
	$sql_items = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}teams (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		name varchar(100) NOT NULL,
		date_assigned DATETIME NOT NULL,
		date_removed DATETIME,
		active TINYINT(1) NOT NULL,
		place MEDIUMINT(9) NOT NULL,
		reg_id MEDIUMINT(9) NOT NULL,
		user_id BIGINT(20) UNSIGNED NOT NULL,
		UNIQUE KEY(id),
		FOREIGN KEY(reg_id) REFERENCES {$wpdb->prefix}registrations(id), 
		FOREIGN KEY(user_id) REFERENCES {$wpdb->prefix}users(ID) 
)$charset_collate";
	
	require( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta($sql_regs);
	dbDelta($sql_items);
	
}
register_activation_hook( __FILE__, 'zshl_create_initial_db_tables' );


function zshl_shortcode_handler($atts) {
	$defaultTimeZone='Europe/Bratislava';
	if(date_default_timezone_get()!=$defaultTimeZone) date_default_timezone_set($defaultTimeZone);
    
    $registraton = zshl_get_registration($atts['id']);
	if (!is_user_logged_in()) {
		wp_login_form();
	}
    if($registraton->start_date > date('Y-m-d H:i:s')) {
        ?>
        <p id="early">Registrácia začne <?php echo $registraton->start_date; ?></p>
        <?php
    }
    else if($registraton->end_date < date('Y-m-d H:i:s')) {
        ?>
        <p id="past">Registrácia už skončila</p>
        <?php
    }
    
    ?>

    
    <?php
    
    zshl_output_table( $registraton );
    
}

add_shortcode( 'zshl', 'zshl_shortcode_handler' );


function zshl_styles_front() {
	wp_enqueue_style('zshl_front',plugin_dir_url(__FILE__) . 'styles/zshl_front.css');
}

function ajax_check_user_logged_in() {
	echo is_user_logged_in()?'yes':'no';
	die();
}


add_action('wp_ajax_is_user_logged_in', 'ajax_check_user_logged_in');
add_action('wp_ajax_nopriv_is_user_logged_in', 'ajax_check_user_logged_in');

function zshl_output_table($registration) {
	zshl_enqueue_scripts();
	zshl_styles_front();
	$teams = zshl_get_teams($registration->id);
	
	?>
	<h2 style="margin-bottom: 0px;"><?php echo $registration->name ?></h2>
    <span id="clock""></span>
        <table id="registration-table">
            <thead>
                <tr>
                    <th class="id">#</th>
                    <th class="team-name">Názov tímu</th>
                    <th class="team-date">Čas registrácie</th>
                    <th class="reg-button"></th>
                </tr>
            </thead>
            <tbody>
            <?php
                // One row for each team
                for ($i = 0; $i < $registration->no_of_teams; $i++) {
                    $name = "";
                    $date = "";
                    foreach ($teams as $team) {
                        if ($team->place - 1 == $i) {
                            $name = $team->name;
                            $date = $team->date_assigned;
                        }
                    }
            ?>
                    
                        <tr>
                            <td class="id"><?php echo $i + 1;?></td>
                            <td class="team-name"><?php echo $name;?></td>
                            <td class="team-date"><?php echo $date;?></td>
                            <td><input class="team_reg" type="button" value="Prihlás tím"></td>
                        </tr>
                    <?php
                }
            ?>
            </tbody>
        </table>
    
        <form id="team_reg_form">
            <input type="hidden" id="reg_id" name="reg_id" value="<?php echo $registration->id; ?>">
            <input id="team_reg_input" type="hidden" name="place" value="">
            <input type="hidden" name="action" value="sign_team"/>
        </form>
    
        <script>
            var now = new Date(<?php echo time() * 1000 ?>);
            
            updateTime();
            
            function startInterval(){
                setInterval('updateTime();', 1000);
            }
            startInterval();//start it right away
            function updateTime(){
                var nowMS = now.getTime();
                nowMS += 1000;
                now.setTime(nowMS);
                var clock = document.getElementById('clock');
                if(clock){
                    clock.innerHTML = now.toTimeString().replace(/.*(\d{2}:\d{2}:\d{2}).*/, "$1");//adjust to suit
                }
            }

        </script>
    <!--<input type="button" value="UPDATE" class="update-button"> -->
<?php
	
}

function zshl_sign_team() {
	$defaultTimeZone='Europe/Bratislava';
	if(date_default_timezone_get()!=$defaultTimeZone) date_default_timezone_set($defaultTimeZone);
    global $wpdb;
    $place = $_POST['place'];
    $user_id = wp_get_current_user()->ID;
    $team_name = get_user_meta($user_id, 'team_name', true);
    $reg_id = $_POST['reg_id'];
    
    $registered = false;
    $place_empty = true;
    $to_start = false;
    
    $registration = zshl_get_registration($reg_id);
    $now = date('Y-m-d H:i:s');
    if ($registration->start_date < $now && $registration->end_date > $now ) {
        echo $to_start = true;
    }
    
    /*
     * check team is alredy registered
     */
    
    $sql = "SELECT active, place, name FROM {$wpdb->prefix}teams WHERE reg_id = $reg_id AND name = \"$team_name\" AND active = 1;";
    $registrations = $wpdb->get_results($sql);
    if (count($registrations) > 0) {
        $registered = true;
    }
    
    /*
     * check if place is empty
     */
    $sql = "SELECT active, place, name FROM {$wpdb->prefix}teams WHERE reg_id = $reg_id AND place = $place AND active = 1";
    $place_check = $wpdb->get_results($sql);
    if (count($place_check) > 0) {
        $place_empty = false;
    }
    
    
    if ($place_empty && $to_start) {
	    $table_name = $wpdb->prefix . 'teams';
        if ($registered) {
            $wpdb->update($table_name,['active' => 0, 'date_removed' => date( "Y-m-d H:i:s" )],['reg_id' => $reg_id, 'name' => $team_name]);
        }
        $wpdb->insert($table_name,[
                'active' => 1,
                'date_assigned' => date( "Y-m-d H:i:s" ),
                'name' => $team_name,
                'place' => $place,
                'reg_id' => $reg_id,
                'user_id' => $user_id
        ]);
    }
    die();
    
}
add_action('wp_ajax_sign_team', 'zshl_sign_team');



function zshl_update_reg_form() {
    $reg_id = $_GET['reg_id'];
    echo json_encode(zshl_get_teams($reg_id));
    die();
}
add_action('wp_ajax_get_teams', 'zshl_update_reg_form');

function zshl_get_teams($reg_id) {
	global $wpdb;
	
	$sql = "SELECT {$wpdb->prefix}teams.name, reg_id, date_assigned, active, place, user_id FROM {$wpdb->prefix}registrations 
    JOIN {$wpdb->prefix}teams ON {$wpdb->prefix}registrations.id = {$wpdb->prefix}teams.reg_id
    WHERE reg_id = $reg_id AND active = 1";
	$teams = $wpdb->get_results($sql);
	
	return $teams;
}

function zshl_get_registration($reg_id) {
    global $wpdb;
    
    $sql_reg = "SELECT id, name, start_date, end_date, no_of_teams  FROM {$wpdb->prefix}registrations 
    WHERE id = $reg_id";
	$registration = $wpdb->get_row($sql_reg);
	return $registration;
}

function zshl_enqueue_scripts() {
	wp_enqueue_script('zshl-countdown',plugin_dir_url(__FILE__) . 'js/zshl-countdown.js',array('jquery'),1.0);
	wp_enqueue_script('zshl-submit',plugin_dir_url(__FILE__) . 'js/zshl-submit.js',array('jquery'),1.0);
	wp_localize_script( 'zshl-submit', 'ajax_object',
		array( 'ajax_url' => admin_url( 'admin-ajax.php' )) );
	
}


/*
 * ALTERING USER REGISTRATION
 * added field for team name assosiated with the user
 */

add_action( 'user_new_form', 'zshl_team_register_form' );
add_action( 'edit_user_profile', 'zshl_team_register_form' );
add_action( 'show_user_profile', 'zshl_team_register_form' );
function zshl_team_register_form() {
	
    if (current_user_can('administrator')) {
	    $team_name = ( ! empty( $_POST['team_name'] ) ) ? trim( $_POST['team_name'] ) : '';
	    $user_id   = "";
	    if ( isset( $_GET['user_id'] ) ) {
		    $user_id = $_GET['user_id'];
	    }
	    $team_name = get_user_meta( $user_id, 'team_name', true );
	    ?>
        <p>
            <label for="team_name">Názov tímu<br/>
                <input type="text" name="team_name" id="team_name" class="input"
                       value="<?php echo esc_attr( wp_unslash( $team_name ) ); ?>" size="25"/></label>
        </p>
	    <?php
    }
}

//2. Add validation. In this case, we make sure team_name is required.
add_filter( 'registration_errors', 'zshl_team_registration_errors', 10, 3 );
function zshl_team_registration_errors( $errors, $sanitized_user_login, $user_email ) {
	
	if ( empty( $_POST['team_name'] ) || ! empty( $_POST['team_name'] ) && trim( $_POST['team_name'] ) == '' ) {
		$errors->add( 'team_name_error', __( '<strong>ERROR</strong>: You must include a team name.', 'mydomain' ) );
	}
	
	return $errors;
}

//3. Finally, save our extra registration user meta.
add_action( 'user_register', 'zshl_user_register' );
add_action('edit_user_profile_update','zshl_user_register');
add_action('personal_options_update','zshl_user_register');
add_action('profile_update','zshl_user_register');

function zshl_user_register( $user_id ) {
	if ( ! empty( $_POST['team_name'] ) ) {
		update_user_meta( $user_id, 'team_name', trim( $_POST['team_name'] ) );
	}
}

