<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 27.9.2016
 * Time: 14:39
 */


function page_rotation_screens_help() {
	$screen = get_current_screen();
	
	// Add my_help_tab if current screen is My Admin Page
	$screen->add_help_tab( array(
		'id'	=> 'my_help_tab',
		'title'	=> __('My Help Tab'),
		'content'	=> '<p>' . __( 'Descriptive content that will show in My Help Tab-body goes here.' ) . '</p>',
	) );
}

function page_rotation_screen_management() {
	
	global $wpdb;
	
	//Screen control
    if (isset($_GET['screen']) && !isset($_GET['ac'])) {
	    $screen_id = $_GET['screen'];
	
	    if ( isset( $_POST['screen_turn_on'] ) ) {
		    page_rotation_create_task( 'turn_on', current_time('mysql', false), 0, $screen_id );
	    }
	
	    else if ( isset( $_POST['screen_turn_off'] ) ) {
		    page_rotation_create_task( 'turn_off', current_time('mysql', false), 0, $screen_id );
	    }
	    
	    //Task creation
	    else if (isset($_POST['task_submit'])) {
		    $screen_id = $_POST['screen_id'];
		    $type = $_POST['task_type'];
		    $exec_time = $_POST['task_time'];
		    $rep = $_POST['rep'];
		
		    //If repeating task is submitted
		    if ($rep) {
			    //Week in seconds
			    $week = 604800;
			    foreach ($rep as $task) {
				    //Get time portion from datetime
				    $time = date("H:i:s",strtotime($exec_time));
				    //Find last day of week - day of week that has passed
				    $date = date('Y-m-d',strtotime('last ' . $task));
				    //Combine time and date to datetime
				    $datetime = date('Y-m-d H:i:s', strtotime("$date $time"));
				
				    page_rotation_create_task($type,$datetime,$week,$screen_id);
				
			    }
		    }
		    //If non-repeating task is submitted
		    else {
			    page_rotation_create_task($type, $exec_time, 0, $screen_id);
		    }
	    }
	
	    page_rotation_output_screen_management( $screen_id );
    }
    
    //Main screen
	else
    {
	    //If new screen was submitted
	    if ( isset( $_POST['screen-submit'] ) ) {
		    page_rotation_create_screen_db_entry();
	    }
	
	    //If screen sequence was updated
	    //TODO move to separate function
	    elseif ( isset( $_POST['sequence_selected'] ) ) {
		    //Retrieve post parameters
		    $screen_id = $_POST['screen_id'];
		    $sequence  = $_POST['sequence_selected'];
		
		    $table_name = $wpdb->prefix . "page_rotation_screens";
		    $wpdb->update( $table_name, array( 'sequence_id' => $sequence ), array( 'id' => $screen_id ) );
	    }
        
	    //If ac parameter is set
        if( isset( $_GET['ac'] ) ) {
	        //Command for new screen
	        if ( $_GET['ac'] == 'add-screen' ) {
		        page_rotation_create_new_screen();
		        page_rotation_screen_main_markup();
	        }
	        else if ( $_GET['ac'] == 'add-task' ) {
	            page_rotation_create_task_screen();
		        
            }
        }
        
        else {
            page_rotation_screen_main_markup();
        }
    }
    
}

function page_rotation_screen_main_markup() {
	$url = $_SERVER['REQUEST_URI'];
	$url = add_query_arg( [ 'ac' => 'add-screen' ], $url );
	?>

    <div id="screen-management">
        <div class="wrap">
            <h1 class="wp-heading-inline">Správa obrazoviek</h1>
            <?php if( current_user_can('administrator')) { ?>
               <a href="<?php echo $url; ?>" class="page-title-action">Pridať novú</a>
            <?php } ?>
        </div>
		<?php
		$results   = page_rotation_select_all_screens();
		$sequences = page_rotation_select_all_sequences();
		
		
		//HTML for each screen box
		foreach ( $results as $result => $value ) {
			
		    //Check if user has permissions for this screen
            if ( (!current_user_can('editor') && !current_user_can('administrator')) && !page_rotation_check_user_perm(get_current_user_id(), $value->id) ) {
                continue;
            }
		    
		    
			//Creates HTML select tags for all sequences
			$select_html = "";
			foreach ( $sequences as $sequence => $seq ) {
				if ( $value->sequence_id == $seq->id ) {
					$select_html = $select_html . "<option value='$seq->id' selected>$seq->name</option>";
				} else {
					$select_html = $select_html . "<option value='$seq->id'>$seq->name</option>";
				}
			}
			
			//Screen page url
			$screen_url = get_permalink( $value->screen_page_id );
			$page       = esc_attr( $_REQUEST['page'] );
			echo "
                    <div class='screen'>
                        <div class='screen-shortcut'>
                            <h2 class='full-name'><a href='?page=$page&screen=$value->id'>$value->name</a></h2>
                            <code class='screen_url'>$screen_url</code>
                        </div>
                        <div class='screen-management'>
                            <form method='post' action=\"\">
                            <input type='hidden' name='screen_id' value=\"$value->id\">
                            <label for='select-sequences'>Výber sekvencie</label>
                            <select id='select-sequences' class='select-sequences widefat' name='sequence_selected'>
                                $select_html
                            </select>
                            <input type='submit' class='button-primary' value='Aktualizuj'>
                            </form>
                        </div>
                    </div>";
		}
		?>
    </div>
<?php
}


function page_rotation_create_task_screen() {
    page_rotation_enque_datetimepicker();
	$page       = esc_attr( $_REQUEST['page'] );
    ?>
    
    <div class="wrap">
        <h1>Naplánovanie úlohy</h1>
        <form id="task-form" method="post" action="?page=<?php echo $page; ?>&screen=<?php echo $_GET['screen']; ?>">
            <input type="hidden" name="screen_id" value="<?php echo $_GET['screen']; ?>">
            <label for="type_on">Typ úlohy</label>
            <input id="type_on" name="task_type" type="radio" value="turn_on"> Zapnúť
            <input id="type_off" name="task_type" type="radio" value="turn_off"> Vypnúť
            <label for="task_time">Dátum a čas vykonania</label>
            <input id="task_time" type="datetime" name="task_time">
            <label for="repetition">Opakovanie</label>
            <div class="rep">
                <input type="checkbox" name="rep[]" value="Monday">Pondelok
                <input type="checkbox" name="rep[]" value="Tuesday">Utorok
                <input type="checkbox" name="rep[]" value="Wednesday">Streda
                <input type="checkbox" name="rep[]" value="Thursday">Štvrtok
                <input type="checkbox" name="rep[]" value="Friday">Piatok
                <input type="checkbox" name="rep[]" value="Saturday">Sobota
                <input type="checkbox" name="rep[]" value="Sunday">Nedeľa
            </div>
            <input type="submit" class="button-primary" name="task_submit" value="Potvrdiť">
        </form>
    </div>
    
    <?php
}


function page_rotation_create_task($type, $exec_time, $repeat, $screen_id) {
    global $wpdb;
    $wpdb->insert("{$wpdb->prefix}page_rotation_tasks",[ 'type' => $type,
        'exec_time' => $exec_time,
        'rep' => $repeat,
        'executed' => false,
        'screen_id' => $screen_id,
        'date_created' => current_time('mysql', false)
    ]);
}

function page_rotation_output_screen_management($screen_id) {
    $screen = page_rotation_select_screen($screen_id);
    require_once ('page-rotation-screen-permissions.php');

    if( isset($_POST['screen_update'])) {
        if (isset($_POST['progress_bar'])) {
            page_rotation_update_screen_settings($_GET['screen'],true);
        }
        else if (!isset($_POST['progress_bar'])) {
	        page_rotation_update_screen_settings($_GET['screen'],false);
        }
        
    }
    else if (isset($_POST['permission-update'])) {
        page_rotation_screen_permissions_delete_all($screen_id);
        if (isset($_POST['screen-permissions'])) {
            $permissions = $_POST['screen-permissions'];
            foreach ($permissions as $permission) {
                page_rotation_screen_permissions_update($permission, $screen_id);
            }
        }
    }
    
    ?>
    <div class="wrap">
        <h1>Správa obrazovky - <?php echo $screen->name; ?></h1>
        <section>
            <h2>Informácie o obrazovke</h2>
            <form method="post">
                <table>
                    <tbody>
                        <tr>
                            <td>ID</td>
                            <td><?php echo $screen->id; ?></td>
                        </tr>
                        <tr>
                            <td>Pridelená URL</td>
                            <td><?php echo page_rotation_get_task_url($screen->id) ?></td>
                        </tr>
                        <tr>
                            <td>Zobrazovať progress bar</td>
                            <td><input type="checkbox" name="progress_bar" <?php echo page_rotation_get_progress_bar_settings($_GET['screen']); ?>></td>
                        </tr>
                    </tbody>
                </table>
                <input type="submit" name="screen_update" class="button-secondary" value="Uložiť zmeny">
            </form>
        </section>
        
        <section>
            <h2>Ovládanie obrazovky</h2>
            <form method="post" action="">
                <input class="button-secondary" type="submit" value="Zapnúť" name="screen_turn_on" />
                <input class="button-secondary" type="submit" value="Vypnúť" name="screen_turn_off" />
            </form>
        </section>
        
        <?php
           $page       = esc_attr( $_REQUEST['page'] );
        ?>
        
        <section>
            <h1 class="wp-heading-inline">Plánované operácie</h1>
            <a class="page-title-action" href="?page=<?php echo $page; ?>&screen=<?php echo $screen->id; ?>&ac=add-task">Naplánovať operáciu</a>
            <?php
                require_once ('Task_List.php');
                $table = new Task_List();
                $table->prepare_items();
                ?>
                <form method="post" action="">
                    <?php $table->display(); ?>
                </form>
        </section>

        <!-- SECTION FOR USER PERMISSIONS -->
        <section>
            <h1>Práva používateľov</h1>
            <h3>Výber používateľov ktorý môžu spravovať obrazovku</h3>
            <form method="post" action="">
                <ul>
                    <?php
                    $users = get_users();
                    foreach ($users as $user) {
                        $permission = page_rotation_check_user_perm($user->ID, $screen_id);
                        if (user_can($user->ID, 'administrator') ||  user_can($user->ID, 'editor')) {
                            echo "<li><input type='checkbox' name='' disabled value='$user->ID' checked> $user->user_login</li>";
                        }
                        else if ($permission) {
                            echo "<li><input type='checkbox' name='screen-permissions[]' value='$user->ID' checked> $user->user_login</li>";
                        }
                        else {
                            echo "<li><input type='checkbox' name='screen-permissions[]' value='$user->ID'> $user->user_login</li>";
                        }
                    }
                    ?>
                </ul>
                <input type="submit" value="Uložiť práva" name="permission-update" class="button-primary">
            </form>
        </section>


        <div id="dialog-delete" title="Zmazať záznam?">
            <p><span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>Ste si istý, že chcete zmazať tento záznam? Krok sa nedá vrátiť naspäť.</p>
        </div>
    </div>
    <?php
}

/**
 * Markup for new screen page
 */
function page_rotation_create_new_screen() {
	?>
	
	<div id="screen-add">
		<h1>Pridanie novej obrazovky</h1>
		<form method="post" action="<?php echo remove_query_arg('ac',$_SERVER['HTTP_REFERER']) ?>" >
			<label for="name">Názov obrazovky</label>
			<input id="name" name="name" type="text" >
			<input type="submit" name="screen-submit" value="Pridaj" class="button-primary">
		</form>
	</div>
	
	
<?php
}

function page_rotation_get_task_url($screen_id) {
    global $wpdb;
    
    $sql = "SELECT task_page_id FROM {$wpdb->prefix}page_rotation_screens
    WHERE id = $screen_id;";
    $task_page_id =  $wpdb->get_var($sql);
    return get_permalink($task_page_id);
}

/**
 * Create entry for screen in DB
 */
function page_rotation_create_screen_db_entry() {
	
	global $wpdb;
	$table_name = $wpdb->prefix . "page_rotation_screens";
	
	$name = $_POST['name'];
	$wpdb->insert($table_name, array( 'name' => $name));
	$screen_id = $wpdb->insert_id;
	
	page_rotation_create_screen_redirect_page($screen_id,$name);
	page_rotation_create_task_page($screen_id,$name);
}

/**
 * Selects all screens in database
 * @return array|null|object Array outputed from select
 */
function page_rotation_select_all_screens() {
	global $wpdb;
	$table_name = $wpdb->prefix . "page_rotation_screens";
	$sql = "SELECT * FROM $table_name";
	$results = $wpdb->get_results($sql);
	return $results;
}

/**
 * Selects all sequences in database
 * @return array|null|object
 */
function page_rotation_select_all_sequences() {
	global $wpdb;
	$seq_table_name = $wpdb->prefix . "page_rotation_sequences";
	$pages_table_name = $wpdb->prefix . "page_rotation_pages";
	
	$sql = "SELECT * FROM $seq_table_name";
	$results = $wpdb->get_results($sql);
	return $results;
}

function page_rotation_select_screen($screen_id) {
    global $wpdb;
    
    $sql = "SELECT * FROM {$wpdb->prefix}page_rotation_screens WHERE id = $screen_id";
    return $wpdb->get_row($sql);
}


function page_rotation_create_screen_redirect_page($screen_id, $screen_name) {
	$category_name = "Obrazovky";
	global $wpdb;
	//Get ID of the 'sequence' category
	$cat_id = get_cat_ID($category_name);
	
	//Create a page for redirection
	$post_id = wp_insert_post(array(
		'post_title' => $screen_name,
		'post_name' => $screen_name,
		'post_type' => 'page',
		'post_status' => 'publish',
		'post_category' => array($cat_id)
	));
	
	$table_name = $wpdb->prefix . "page_rotation_screens";
	
	$wpdb->update($table_name,array( 'screen_page_id' => $post_id), array( 'id' => $screen_id));
	
	add_post_meta($post_id,'screen',true);
	add_post_meta($post_id,'screen_id',$screen_id);
}

function page_rotation_create_task_page($screen_id, $screen_name) {
	$category_name = "Obrazovky";
	global $wpdb;
	//Get ID of the 'sequence' category
	$cat_id = get_cat_ID($category_name);
	
	//Create a page for redirection
	$post_id = wp_insert_post(array(
		'post_title' => $screen_name . ' - ulohy',
		'post_name' => $screen_name,
		'post_type' => 'page',
		'post_status' => 'publish',
		'post_category' => array($cat_id)
	));
	
	$table_name = $wpdb->prefix . "page_rotation_screens";
	
	$wpdb->update($table_name,array( 'task_page_id' => $post_id), array( 'id' => $screen_id));
	
	add_post_meta($post_id,'screen_task',true);
	add_post_meta($post_id,'screen_id',$screen_id);
}


function page_rotation_mark_task_as_executed($task_id, $rep) {
    global $wpdb;
    $wpdb->update("{$wpdb->prefix}page_rotation_tasks",['executed' => true, 'rep' => $rep],['id' => $task_id]);
}

function page_rotation_check_for_task_page() {
    global $post;
    global $wpdb;
    $task_check = get_post_meta($post->ID,'screen_task',true);
    //if page contains tasks for presentation devices
    if ( $task_check ) {
        
        $screen_id = get_post_meta($post->ID,'screen_id',true);
        
        $tasks = page_rotation_get_all_tasks($screen_id);
	    ?>
        <div id="json-data"><?php echo json_encode($tasks); ?></div>
        <?php
        //Mark all loaded tasks as executed
        foreach ($tasks as $task) {
            page_rotation_mark_task_as_executed($task->id, $task->rep);
        }
        
    }
}

function page_rotation_get_all_tasks($screen_id) {
	global $wpdb;
    //Get all non-recurring non-executed tasks and all recurring task matching this date and time (within 59 seconds)
    $sql = "SELECT  * FROM wp_page_rotation_tasks
    WHERE exec_time  < NOW() AND screen_id = $screen_id AND
    ((UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(exec_time) ) % rep < 59
    OR (executed = 0 AND rep = 0));";
    
	$tasks = $wpdb->get_results($sql);
	return $tasks;
}

function page_rotation_get_progress_bar_settings($screen_id) {
    global $wpdb;
    
    $progress_bar =  $wpdb->get_var("SELECT show_progress FROM {$wpdb->prefix}page_rotation_screens WHERE id = $screen_id");
    if ($progress_bar) {
        return "checked";
    }
    else return "";
}

function page_rotation_update_screen_settings($screen_id, $progress_bar) {
	global $wpdb;
	
	return $wpdb->update($wpdb->prefix . 'page_rotation_screens', ['show_progress' => $progress_bar],  ['id' => $screen_id]);
}

function page_rotation_enque_datetimepicker() {
	wp_enqueue_script( 'jquery-ui-datepicker' );
	wp_enqueue_script('datetimepicker','https://cdn.jsdelivr.net/jquery.ui.timepicker.addon/1.4.5/jquery-ui-timepicker-addon.min.js',array('jquery'),1.0);
	wp_enqueue_style('datetimepicker_css','https://cdn.jsdelivr.net/jquery.ui.timepicker.addon/1.4.5/jquery-ui-timepicker-addon.min.css');
    wp_enqueue_script('datepicker',plugin_dir_url(__FILE__) . 'js/datetime-picker.js');
}