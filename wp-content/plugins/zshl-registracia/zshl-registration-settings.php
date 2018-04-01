<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 30.1.2017
 * Time: 20:36
 */

function zshl_settings_markup() {
	$defaultTimeZone='Europe/Bratislava';
	if(date_default_timezone_get()!=$defaultTimeZone) date_default_timezone_set($defaultTimeZone);
	zshl_enqueue_datetimepicker();
	zshl_styles();
	zshl_admin_scripts();
	
	if( isset($_GET['ac']) && $_GET['ac'] != 'delete') {
		if ( $_GET['ac'] == 'new_reg' ) {
			zshl_add_new_reg_page();
		}
		else if( $_GET['ac'] == 'edit_reg' ) {
		    
		    //Get reg id
		    if( isset($_GET['registration']) ) {
		        $reg_id = $_GET['registration'];
            }
            else {
		        wp_die('Chyba');
            }
            
            
            zshl_edit_reg_page($reg_id);
            
        }
        else if($_GET['ac'] == 'view') {
		    $reg_id = $_GET['registration'];
            zshl_get_registration_overview($reg_id);
            
        }
        
	}
	
	else {
		
	    //new registration was submitted
	    if (isset($_POST['new_reg_submit'])) {
	        zshl_create_new_registration($_POST['reg_name'],$_POST['start_date'],$_POST['end_date'],$_POST['no_of_teams']);
        }
        
        else if (isset ($_POST['edit_reg_submit'])) {
	        zshl_edit_registration($_POST['registration_id'], $_POST['reg_name'],$_POST['start_date'],$_POST['end_date'],$_POST['no_of_teams']);
        }
		
		$url = $_SERVER['REQUEST_URI'];
		$url = add_query_arg( [ 'ac' => 'new_reg' ], $url );
		
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">Registrácie</h1>
			<a href="<?php echo $url; ?>" class="page-title-action">Pridať novú</a>
			
			
			<?php
			
			require_once( plugin_dir_path( __FILE__ ) . 'RegistrationList.php' );
			$table = new RegistrationList();
			$table->prepare_items();
			$table->display();
			
			?>
		</div>
        <div id="dialog-delete" title="Zmazať záznam?">
            <p><span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>Ste si istý, že chcete zmazať tento záznam? Krok sa nedá vrátiť naspäť.</p>
        </div>
		<?php
	}
}

function zshl_add_new_reg_page() {
	?>
	<div class="wrap">
		<h1 class="wp-heading">Pridanie novej registrácie</h1>
		
		<form id="new_reg" method="post" action="<?php echo remove_query_arg('ac',$_SERVER['HTTP_REFERER']) ?>">
			<label for="reg_name">Názov</label>
			<input id="reg_name" name="reg_name" type="text" style="display: block">
			
			<label for="start_date">Čas začiatku registrácie</label>
			<input id="start_date" name="start_date" type="text" style="display: block">
			
			<label for="end_date">Čas ukončenia registrácie</label>
			<input id="end_date" name="end_date" type="text" style="display: block">
			
			<label for="no_of_teams">Počet tímov</label>
			<input id="no_of_teams" name="no_of_teams" type="number" style="display: block">
            
            <?php submit_button("Potvrdiť",'primary','new_reg_submit') ?>
		</form>
		
	</div>

<?php
}

function zshl_edit_reg_page($id) {
    global $wpdb;
    $table_name = $wpdb->prefix .'registrations';
    $sql = "SELECT * FROM $table_name WHERE id = $id";
    
    $results = $wpdb->get_row($sql);
    
    ?>

    <div class="wrap">
        <h1 class="wp-heading">Pridanie novej registrácie</h1>

        <form id="new_reg" method="post" action="<?php echo remove_query_arg('ac',$_SERVER['HTTP_REFERER']) ?>">
            <input type="hidden" name="registration_id" value="<?php echo $id; ?>"
            <label for="reg_name">Názov</label>
            <input id="reg_name" name="reg_name" type="text" style="display: block" value="<?php echo $results->name; ?>">

            <label for="start_date">Čas začiatku registrácie</label>
            <input id="start_date" name="start_date" type="datetime" style="display: block" value="<?php echo $results->start_date; ?>">

            <label for="end_date">Čas ukončenia registrácie</label>
            <input id="end_date" name="end_date" type="datetime" style="display: block" value="<?php echo $results->end_date; ?>">

            <label for="no_of_teams">Počet tímov</label>
            <input id="no_of_teams" name="no_of_teams" type="number" style="display: block" value="<?php echo $results->no_of_teams; ?>">
			
			<?php submit_button("Potvrdiť",'primary','edit_reg_submit') ?>
        </form>
    </div>
	
	
	<?php
}

function zshl_create_new_registration($name, $start_date, $end_date, $no_of_teams) {
    
    global $wpdb;
    
    $now = date('Y-m-d H:i:s');
    $wpdb->insert($wpdb->prefix . 'registrations',['name' => $name,
                                                    'start_date' => $start_date,
                                                    'end_date' => $end_date,
                                                    'no_of_teams' => $no_of_teams,
                                                    'date_created' => $now,
                                                    'date_updated' => $now]);
}

function zshl_edit_registration($reg_id, $name, $start_date, $end_date, $no_of_teams) {
	
	global $wpdb;
	
	$now = date('Y-m-d H:i:s');
	$wpdb->update($wpdb->prefix . 'registrations',['name' => $name,
	                                               'start_date' => $start_date,
	                                               'end_date' => $end_date,
	                                               'no_of_teams' => $no_of_teams,
	                                               'date_updated' => $now], ['id' => $reg_id]);
}


function zshl_get_registration_overview($reg_id) {
	require_once (plugin_dir_path(__FILE__) . 'zshl-registration.php');
	$teams = zshl_get_teams($reg_id);
	$registration = zshl_get_registration($reg_id);
	?>
    <table>
        <thead>
        <tr>
            <th>Poradie</th>
            <th>Názov tímu</th>
            <th>Čas registrácie</th>
            <th>Používateľ</th>
        </tr>
        </thead>
        <tbody>
		<?php
		for ($i = 0; $i < $registration->no_of_teams; $i++) {
			$name = "";
			$date = "";
			$user_login = "";
			foreach ($teams as $team) {
				if ($team->place - 1 == $i) {
					$name = $team->name;
					$date = $team->date_assigned;
					$user_id = $team->user_id;
					$user_login = get_userdata($user_id)->user_login;
				}
			}
			?>
            <tr>
                <td><?php echo $i + 1; ?></td>
                <td><?php echo $name; ?></td>
                <td><?php echo $date; ?></td>
                <td><?php echo $user_login; ?></td>
                <td></td>
                <td></td>
            </tr>
			<?php
		}
		?>
        </tbody>
    </table>
	
	<?php
}

function zshl_styles() {
	wp_enqueue_style('zshl_admin',plugin_dir_path(__FILE__) . 'styles/zshl_admin.css');
	wp_enqueue_style('jquery-ui',"//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css");
}

function zshl_admin_scripts() {
	wp_enqueue_script('zshl-datepicker',plugin_dir_url(__FILE__) . 'js/datepicker.js',array('jquery'),1.0);
	wp_enqueue_script('zshl-warnings',plugin_dir_url(__FILE__) . 'js/warnings.js',array('jquery'),1.0);
}

function zshl_enqueue_datetimepicker() {
	wp_enqueue_script('jquery-ui');
	wp_enqueue_script('jquery-ui-dialog');
	wp_enqueue_script( 'jquery-ui-datepicker' );
    wp_enqueue_script('datetimepicker','https://cdn.jsdelivr.net/jquery.ui.timepicker.addon/1.4.5/jquery-ui-timepicker-addon.min.js',array('jquery'),1.0);
    wp_enqueue_style('datetimepicker_css','https://cdn.jsdelivr.net/jquery.ui.timepicker.addon/1.4.5/jquery-ui-timepicker-addon.min.css');
}