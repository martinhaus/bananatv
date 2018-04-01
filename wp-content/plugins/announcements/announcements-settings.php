<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 24.1.2017
 * Time: 20:57
 */

function announcements_settings_page() {

	
	announcements_enqueue_admin_styles();
	
	if(isset($_GET['ac']) && $_GET['ac'] == 'new_ann') {
		announcements_new_announcement_page();
	}
	else if (isset($_GET['ac']) && $_GET['ac'] == 'new_cat') {
	   announcements_new_category_page();
    }
	elseif(isset($_GET['action']) && $_GET['action'] == 'edit') {
		$ann_id = $_GET['announcement'];
		announcements_edit_announcement_page($ann_id);
	}
	else if (isset($_POST['ann-submit'])) {
		announcements_insert_into_db($_POST);
		announcement_settings_page_index();
	}
	else if (isset($_POST['ann-edit'])) {
		$params = $_POST;
	    announcements_update_db_entry($params);
        announcement_settings_page_index();
	}
	else if (isset($_POST['ann-cat-submit'])) {
		announcements_insert_category($_POST['cat-name']);
		announcement_settings_page_index();
	}
	else {
		announcement_settings_page_index();
	}
}

function announcement_settings_page_index() {
	
	//Include WP list table
	if ( ! class_exists( 'WP_List_Table' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
	}
	require_once('Announcement_List.php');
	
	$table = new Announcement_List();
	$table->prepare_items();
	
	$url =  $_SERVER['REQUEST_URI'];
	$url = add_query_arg(['ac' => 'new_ann'], $url);
	
	$url_cat =  $_SERVER['REQUEST_URI'];
	$url_cat = add_query_arg(['ac' => 'new_cat'], $url);
	
	?>
	
	<div class="wrap">
		
		<h1 class="wp-heading-inline">Oznamy</h1>
		<a href="<?php echo $url; ?>" class="page-title-action">Pridať nový</a>
		<?php if(current_user_can('administrator')) { ?>
            <a href="<?php echo $url_cat; ?>" class="page-title-action">Pridať kategóriu</a>
        <?php } ?>
        <form method="post" action="">
            <?php
            $table->display();
            ?>
        </form>
        <div id="dialog-delete" title="Zmazať záznam?">
            <p><span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>Ste si istý, že chcete zmazať tento záznam? Krok sa nedá vrátiť naspäť.</p>
        </div>
        
	</div>
	<?php
}

function announcements_new_announcement_page() {
 
	?>
	<h1 class="wp-heading">Pridanie oznamu</h1>
	<form method="post" action="<?php echo remove_query_arg('ac',$_SERVER['HTTP_REFERER']) ?>">
<!--		<label for="ann_text" class="ann-label">Text oznamu</label>-->
<!--		<textarea id="ann_text" name="ann_text" class="ann_text"></textarea>-->

        <?php wp_editor( null, 'ann_text', $settings = array('media_buttons' => false) ); ?>

		<label for="ann-start" class="ann-label"><h3>Začiatok zobrazovania oznamu</h3></label>
		<input id="ann-start" type="datetime" name="ann-start">
		<label for="ann-end" class="ann-label"><h3>Koniec zobrazovania oznamu</h3></label>
		<input id="ann-end" type="datetime" name="ann-end">
		<?php announcements_output_category_box(null); ?>
		<?php submit_button("Potvrdiť",'primary','ann-submit') ?>
	</form>
	
<?php
}

function announcements_edit_announcement_page($id) {
    
    
    $announcement = announcements_get_announcement($id);
	?>
	<h1 class="wp-heading">Pridanie oznamu</h1>
	<form method="post" action="<?php echo remove_query_arg('ac',$_SERVER['HTTP_REFERER']) ?>">
        <input type="hidden" name="ann-id" value="<?php echo $id; ?>">
		<label for="ann_text" class="ann-label">Text oznamu</label>
		<textarea id="ann_text" name="ann_text" class="ann_text"><?php echo $announcement->announcement_text ?></textarea>
		<label for="ann-start" class="ann-label">Začiatok zobrazovania oznamu</label>
		<input id="ann-start" type="datetime" name="ann-start" value="<?php echo date('d.m.Y', strtotime($announcement->start_date)); ?>">
		<label for="ann-end" class="ann-label">Koniec zobrazovania oznamu</label>
		<input id="ann-end" type="datetime" name="ann-end" value="<?php echo date('d.m.Y', strtotime($announcement->end_date)); ?>">
		<?php announcements_output_category_box($announcement->category_id); ?>
		<?php submit_button("Potvrdiť",'primary','ann-edit') ?>
	</form>
	
	<?php
}

function announcements_get_announcement($id) {
    global $wpdb;
    
    $sql = "SELECT * FROM {$wpdb->prefix}announcements
    JOIN {$wpdb->prefix}announcements_categories ON category_id = {$wpdb->prefix}announcements_categories.id
    WHERE {$wpdb->prefix}announcements.id = $id";
    $result = $wpdb->get_row($sql);
    return $result;
    
}


function announcement_esc_end_date($date) {
	if($date == '') {
		return '9999-12-31';
	}
	else {
		return $date;
	}
	
}

function announcements_insert_into_db($params) {
	
	global $wpdb;
	
	$wpdb->insert($wpdb->prefix . 'announcements',['announcement_text' => $params['ann_text'],
												 'start_date' => date("Y-m-d H:i:s",strtotime($params['ann-start'])),
												 'end_date' => date('Y-m-d H:i:s', strtotime(announcement_esc_end_date($params['ann-end']))),
													'date_created' => date("Y-m-d H:i:s"),
													'date_updated' => date("Y-m-d H:i:s"),
                                                    'category_id' => $params['ann-cat']
		
	]);
	
}


function announcements_update_db_entry($params) {
    global $wpdb;
    
    $wpdb->update($wpdb->prefix . 'announcements',['announcement_text' => $params['ann_text'],
                                                   'start_date' => date("Y-m-d H:i:s",strtotime($params['ann-start'])),
                                                    'end_date' => date('Y-m-d H:i:s', announcement_esc_end_date(strtotime($params['ann-end']))),
                                                    'date_updated' => date('Y-m-d H:m:i')],
        ['id' => $params['ann-id']]);
}

function announcements_new_category_page() {
    ?>
    <h1 class="wp-heading">Pridanie kategórie pre oznamy</h1>
    <form method="post" action="<?php echo remove_query_arg('ac',$_SERVER['HTTP_REFERER']) ?>">
        <label for="cat-name" class="ann-label">Názov kategórie</label>
        <input type="text" id="cat-name" name="cat-name" class="cat-name"/>
        <?php submit_button("Potvrdiť",'primary','ann-cat-submit') ?>
    </form>
	<?php
}

function announcements_insert_category($name) {
    global $wpdb;
    $wpdb->insert($wpdb->prefix . 'announcements_categories', ['name' => $name]);
}

function announcements_get_all_categories($userid) {
    global $wpdb;
    
    $sql = "SELECT {$wpdb->prefix}announcements_categories.id, name from {$wpdb->prefix}announcements_categories ";
    
    if ($userid != null) {
        $sql .= "JOIN {$wpdb->prefix}announcements_permissions ON announcement_id = {$wpdb->prefix}announcements_categories.id
        WHERE user_id = $userid";
    }
    
    return $wpdb->get_results($sql);
}

function announcements_output_category_box($selected) {
    
    // If user is not admin or super admin, display categories accordingly
    if ( current_user_can('administrator') or current_user_can('editor') ) {
	    $all_cat = announcements_get_all_categories(null );
    }
    else {
	    $all_cat = announcements_get_all_categories(get_current_user_id());
    }
    ?>
    <label for="ann-cat" style="display: block;"><h3>Kategória oznamu</h3></label>
    <select id="ann-cat" name="ann-cat" style="display: block;">
       <?php
        foreach ($all_cat as $cat) {
            if ($selected == $cat->id) {
                $selected_text = "selected";
            }
            else {
                $selected_text = "";
            }
            echo "<option value='$cat->id' $selected_text>$cat->name</option>";
        }
       ?>
    </select>

    <?php
}

/**
 * Page for actual announcement settings
 */
function announcements_subsettings_page() {
    
    // If new settings were submitted
    if (isset($_POST['announcements-settings-save'])) {
        // If display time was submitted
        if (isset($_POST['announcement-time'])) {
            $settings['time'] = $_POST['announcement-time'];
            if (isset($_POST['announcement-auto-sequence'])) {
	            $settings['auto-sequence'] = true;
            }
            else {
	            $settings['auto-sequence'] = false;
            }
            
            announcement_subsettings_save($settings);
        }
    }
    
    $settings = announcement_subsettings_load();

    /*
     *  <label for="announcement-auto-sequence">Automatické prispôsobenie času zobrazenie stránky počtu oznamov</label>
        <input id="announcement-auto-sequence" name="announcement-auto-sequence" type="checkbox" style="display: inline-block;" <?php echo $settings['auto-sequence'];?>>

     */
    ?>
    <div class="wrap">
        <h2>Nastavenia pre modul oznamy</h2>
        <div>
            <form method="post">
                <label for="announcement-time">Dĺžka zobrazenie jedného oznamu</label>
                <input id="announcement-time" name="announcement-time" type="number" style="display: block;" value="<?php echo $settings['time'];?>">
                <input type="submit" name="announcements-settings-save" style="display: block;" class="button-primary" value="Uložiť">
            </form>
        </div>
    </div>
    
    
<?php
}

function announcement_subsettings_save($settings) {
    update_option('announcements-display-time',$settings['time'], true);
    update_option('announcements-auto-sequence',$settings['auto-sequence'], true);
}

function announcement_subsettings_load() {
    $settings['time'] = get_option("announcements-display-time");
    if (get_option("announcements-auto-sequence")) {
        $settings['auto-sequence'] = "checked";
    }
    else {
        $settings['auto-sequence'] = "";
    }
    return $settings;
}


function announcemets_update_sequences() {

}