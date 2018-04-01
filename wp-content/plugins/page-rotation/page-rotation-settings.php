<?php



function page_rotation_sequence_help() {
	$screen = get_current_screen();
	
	// Add my_help_tab if current screen is My Admin Page
	$screen->add_help_tab( array(
		'id'	=> 'sequence_help',
		'title'	=> __('Sekvencie'),
		'content'	=> '<p>' . __( 'Táto stránka umožňuje spravovať sekvencie. 
		Sekvencie sa dajú priradzovať na obrazovky, je ich však možné zobrazovať aj samostatne pomocou priradenej URL.
		Úprava a mazanie sekvencii je možné pomocou príslušných tlačidiel <i>Upraviť</i> a <i style="color: red;">Zmazať</i>.' ) . '</p>',
	) );
	
	$screen->add_help_tab( array(
		'id'	=> 'sequence_help_add',
		'title'	=> __('Pridanie novej sekvencie'),
		'content'	=> '<p>' . __( 'Pridať novú sekvenciu je možné kliknutím na tlačidlo <i>Pridať novú</i>.
        Následne je nutné pridať sekvencii názov a prípadne aj komentár. 
        <br>
        Stránky sa do sekvencie pridávajú jednoduchým potiahnutím (Drag&Drop) z jednej tebuľky do druhej.
        
        <br><br>
        <i>Tip: Dvojklikom na riadok v tabuľke sa záznam zdvojí.<br>
        Tip 2: Pri prechádzaní myšou po jednotlivých záznamoch sa rovnaké záznami vysvecuju modrou farbou.</i>
        ' ) . '</p>',
	) );
	
	$screen->add_help_tab( array(
		'id'	=> 'sequence_help_edit',
		'title'	=> __('Úprava existujúcej sekvencie'),
		'content'	=> '<p>' . __( 'Upraviť sekvenciu je možné kliknutím na tlačidlo <i>Upraviť</i>, ktoré sa nachádza
        pod každým záznamom v tabuľke.
        Následne je možné sekvenciu ľubovoľne upravovať. 
        
        <br>
        Zmeny sa uložia až po stlačení tlačidla <i>Uprav</i>.
        <br>
        Stránky sa do sekvencie pridávajú jednoduchým potiahnutím (Drag&Drop) z jednej tebuľky do druhej.
        
        <br><br>
        <i>Tip: Dvojklikom na riadok v tabuľke sa záznam zdvojí.<br>
        Tip 2: Pri prechádzaní myšou po jednotlivých záznamoch sa rovnaké záznami vysvecuju modrou farbou.</i>
        ' ) . '</p>',
	) );
	
	$screen->add_help_tab( array(
		'id'	=> 'sequence_help_delete',
		'title'	=> __('Zmazanie sekvencie'),
		'content'	=> '<p>' . __( 'Zmazať sekvenciu je možné kliknutím na tlačidlo <i style="color:red;">Zmazať</i>, ktoré sa nachádza
        pod každým záznamom v tabuľke.
   
        <br><br>
        <i><b>Pozor:</b> Sekvenciu, ktorá je priradené nejakej obrazovke nie je možné zmazať! Najprv je nutné priradiť obrazovke inú sekvenciu.<br></i>
        ' ) . '</p>',
	) );
}

/**
* Creates main settings page
*/
function page_rotation_settings_page() {
	global $wpdb;
	
	if(isset($_GET['action']) && ($_GET['action'] != 'delete')) {
	    if($_GET['action'] == 'add') {
		    page_rotation_create_add_page();
        }
	    
	    else if($_GET['action'] == 'edit') {
		    if ( isset( $_GET['sequence'] ) ) {
			    page_rotation_create_edit_page( $_GET['sequence'] );
		    }
	    }
    }
    
    
    
	else {
        
		global $wpdb;
		
		//if submit button was pressed
		if(isset($_POST['create_sequence'])) {
			//retrieve POST variables
			$name = $_POST['name'];

			//Create entry in sequences
			$sequence_table = $wpdb->prefix . 'page_rotation_sequences';
			$wpdb->insert($sequence_table,array(
				'name' => $name,
                'comment' => $_POST['comment'],
				'date_created' => date("Y-m-d H:i:s")
			));
			$sequence_id =$wpdb->insert_id;
			
			//Create entry for every page
			page_rotation_create_entry_for_all_pages($sequence_id);
			//Create redirectional page
			page_rotation_create_redirect_page($name,$sequence_id);
		}
		
		//Edit page submitted
		else if(isset($_POST['edit_sequence'])) {
			//Delete old entries
			if(isset($_POST['sequence'])) {
				$sequence_id = $_POST['sequence'];
				$table_name = $wpdb->prefix . "page_rotation_pages";
				$wpdb->delete($table_name, array('sequence_id' => $_POST['sequence']));
			}
			else {
				wp_die('Nepodarilo sa nacitat ID sekvencie');
			}
			
			//Create new entries for pages
			page_rotation_create_entry_for_all_pages($sequence_id);
			
			//Update date when sequence was created
			//Update sequence name
			$table_name = $wpdb->prefix . 'page_rotation_sequences';
			$wpdb->update($table_name,
				array('date_modified' => date("Y-m-d H:i:s"),
					'name' => $_POST['name'], 'comment' => $_POST['comment'] ),array('id' => $sequence_id));
			
			
		}

        if (isset($sequence_id)) {
            page_rotation_sequence_permissions_delete_all($sequence_id);
        }
        if (isset($_POST['screen-permissions'])) {
            $permissions = $_POST['screen-permissions'];
            foreach ($permissions as $permission) {
                page_rotation_sequence_permissions_update($permission, $sequence_id);
            }
       }

		
		$url =  $_SERVER['REQUEST_URI'];
		$url = add_query_arg(['action' => 'add'], $url);
		
		
		?>

		<div class="wrap">
			<h1 class="wp-heading-inline">Sekvencie</h1>
            <a href="<?php echo $url; ?>" class="page-title-action">Pridať novú</a>
		<div id="dialog-delete" title="Zmazať záznam?">
            <p><span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>Ste si istý, že chcete zmazať tento záznam? Krok sa nedá vrátiť naspäť.</p>
        </div>
			<?php
			
			require_once(plugin_dir_path(__FILE__) . '/Sequence_List.php');
			$table = new  Sequence_List();
			$table->prepare_items();
			?>
			<form method="post" action="">
                <?php
                $table->display();
                ?>
            </form>
			<?php
	}
}

function page_rotation_create_entry_for_all_pages($sequence_id) {
	global $wpdb;
	$pages = $_POST['pages'];
	$pages_timing = $_POST['pages-timing'];
	//Create entry for every page
	$table_name = $wpdb->prefix . 'page_rotation_pages';
	for($i=0;$i<count($pages);$i++){
		//Previous page ID
		if($i != 0)
			$prev_page = $pages[$i-1];
		else
			$prev_page = -1;
		
		//Next page ID
		if($i != count($pages) -1)
			$next_page = $pages[$i+1];
		else
			$next_page = $pages[0];
		
		//Current page ID
		$cur_page = $pages[$i];
		
		//Page display time
		$timing = $pages_timing[$i];
		
		$wpdb->insert($table_name,array(
			'page_id' => $cur_page,
			'prev_page' => $prev_page,
			'next_page' => $next_page,
			'display_time' => $timing,
			'consecutive_number' => $i +1,
			'sequence_id' =>$sequence_id
		));
	}
}

/**
 * Returns list of pages in a sequence
 * @param $id
 *
 * @return array|null|object
 */
function page_rotation_get_sequence_pages($id) {
	global $wpdb;
	
	$sql = "SELECT page_id as id, display_time FROM wp_page_rotation_sequences 
	JOIN wp_page_rotation_pages 
	ON wp_page_rotation_sequences.id = wp_page_rotation_pages.sequence_id
	WHERE wp_page_rotation_pages.sequence_id = $id
	ORDER BY consecutive_number;";
	
	$sequence_pages = $wpdb->get_results($sql);
	return $sequence_pages;
	
}

/**
 * Creates "Add new sequence" page in the settings menu
 */
function page_rotation_create_add_page() {

	?>

	<div class="wrap">
	<h1>Sekvencie</h1>
	<form method="post" name="form" action="<?php echo remove_query_arg('action',$_SERVER['HTTP_REFERER']) ?>">
		<label for="name">Názov</label>
		<input type="text" id="name" name="name">
		<label for="comment">Komentár</label>
		<input type="text" id="comment" name="comment">
		<div id="tables">
		<div id="sequence-pages">
			<h2>Sekvencia</h2>
			<table id="sequence-pages-table" class="widefat pages-table">
				<thead>
				<tr>
					<th class="page-title-header">Názov stránky</th>
					<th>Čas zobrazenia</th>
					<th></th>
				</tr>
				</thead>
				<tbody>
				</tbody>
				</table>
		</div>
		
		<div id="all-pages">
        <h2 style="display: inline;">Všetky stránky</h2>
        <div class="filter-box"><input type="text" class="input-filter" id="input-filter" placeholder="Filter stránok"> </div>
		<table id="all-pages-table" class="widefat pages-table">
			<thead>
				<th class="page-title-header">Názov stránky</th>
			</thead>
			<tbody>
		<?php
		//Load all pages
		$all_pages = page_rotation_get_all_pages();
		//Load icons
		$x_icon_path = plugin_dir_url(__FILE__) . 'images/icons/x-icon.png';
		$plus_icon_path = plugin_dir_url(__FILE__) . 'images/icons/plus-icon.png';
		
		foreach ($all_pages as $page_id) {
			$page_title = get_the_title( $page_id );
			
			//Create poster tag
			if( page_rotation_check_if_poster($page_id) ) {
				$poster_tag = "<div class='poster-tag'>Plagát</div>";
				$date = page_rotation_get_poster_date($page_id);
				$mark ="";
				//Mark green
				if( page_rotation_check_poster_date($page_id) && $date) {
					$mark = "<div class='poster-tag-valid'>Zobrazuje sa do $date</div>";
				}
				else if ($date > 0) {
					$mark = "<div class='poster-tag-invalid'>Zobrazuje sa do $date</div>";
				}
				$poster_tag .= $mark;
			}
			else {
				$poster_tag = "";
			}
			
			echo "<tr class='item-row'>
					<td class='page-title'><input type='hidden' name='none' value=$page_id />
					<span class='page-title-value'>$page_title</span> $poster_tag</td>
					
					<td class='add-multiple'><a href='#'  id='add-multiple'><img src=$plus_icon_path /></a></td>
					<td class='delete-all'><a href='#'  id='delete-all'><img src=$x_icon_path /></a></td>
				</tr>";
		}
		?>
			</tbody>
		</table>
		</div>
		</div>


    <!-- SECTION FOR USER PERMISSIONS -->

        <h1>Práva používateľov</h1>
        <h3>Výber používateľov ktorý môžu spravovať sekvenciu</h3>

            <ul>
                <?php
                $users = get_users();
                foreach ($users as $user) {
                    if (user_can($user->ID, 'administrator') ||  user_can($user->ID, 'editor')) {
                        echo "<li><input type='checkbox' name='' disabled value='$user->ID' checked> $user->user_login</li>";
                    }
                    else {
                        echo "<li><input type='checkbox' name='screen-permissions[]' value='$user->ID'> $user->user_login</li>";
                    }
                }
                ?>
            </ul>
            <input type="submit" value="Uložiť" name="create_sequence" class="button-primary">
        </form>


		<div id="dialog-confirm" title="Zmazať všetky výskyty?">
			<p><span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>Všetky výskyty tejto stránky budú zmazané zo sekvencie. Ste si istý?</p>
		</div>
		<div id="dialog-form" title="Pridaj stránku viac krát">

			<form>
				<fieldset>
					<label for="number">Počet</label>
					<input type="text" name="number" id="number" value="3" class="text ui-widget-content ui-corner-all" style="display:block;">
					<label for="time">Čas</label>
					<input type="text" name="time" id="time" value="60" class="text ui-widget-content ui-corner-all"  style="display:block;">
				</fieldset>
			</form>
			<p class="error-message" style="display:none;">Pomer pridávaných stránok k stránkam v tabuľke je nevhodný</p>
		</div>
	</div>
	<?php
}

/**
 * Creates edit page for a sequence
 */
function page_rotation_create_edit_page($sequence_id) {
    ?>

	<div class="wrap">
		<h1>Sekvencie</h1>
		<form method="post" name="form" action="<?php echo remove_query_arg('action',$_SERVER['HTTP_REFERER']) ?>">
			<?php
			global $wpdb;
			$table_name = $wpdb->prefix . 'page_rotation_sequences';
			$sql = "SELECT name, comment FROM $table_name WHERE id = $sequence_id";
			$sequence_name = $wpdb->get_row($sql)->name;
			$sequence_comment = $wpdb->get_row($sql)->comment;
			?>
			<label for="name">Názov</label>
			<input type="text" id="name" name="name" value="<?php echo $sequence_name ?>">

			<label for="comment">Komentár</label>
            <input type="text" id="comment" name="comment" value="<?php echo $sequence_comment ?>">
			
			<div id="tables">
				<div id="sequence-pages">
					<h2><input type="hidden" value=<?php echo $sequence_id?> name="sequence"
					           />Sekvencia</h2>
					<table id="sequence-pages-table" class="pages-table widefat">
						<thead>
                            <tr>
                                <th class="page-title-header">Názov stránky</th>
                                <th>Čas zobrazenia</th>
                                <th></th>
                            </tr>
						</thead>
						<tbody class="connectedSortable">
						
	<?php
	
	$sequence_pages = page_rotation_get_sequence_pages($sequence_id);
	$x_icon_path = plugin_dir_url(__FILE__) . 'images/icons/x-icon.png';
	$plus_icon_path = plugin_dir_url(__FILE__) . 'images/icons/plus-icon.png';
	foreach ($sequence_pages as $page => $value) {
		$page_title = get_the_title( $value->id );
		$page_id    = $value->id;
		$timing     = $value->display_time;
		
		//Create poster tag
		if( page_rotation_check_if_poster($page_id) ) {
		    
			$poster_tag = "<div class='poster-tag'>Plagát</div>";
			$date = page_rotation_get_poster_date($page_id);
			$mark = "";
			//Mark green
			if( page_rotation_check_poster_date($page_id) && $date) {
				$mark = "<div class='poster-tag-valid'>Zobrazuje sa do $date</div>";
			}
			else if ($date > 0) {
				$mark = "<div class='poster-tag-invalid'>Zobrazuje sa do $date</div>";
			}
			$poster_tag .= $mark;
			
			
		}
		else {
			$poster_tag = "";
		}
		
		echo "<tr class='item-row'>
					<td class='page-title'><input type='hidden' name='pages[]' value=$page_id />
					$page_title $poster_tag</td> 
					<td class='time-input'><input type='text' name='pages-timing[]' value=$timing></td>
					<td class='delete-one' style='padding-top: 12px;'><a href='#' id='delete-one'><img src=$x_icon_path /></a></td>
				</tr>";
	}
	?>
						</tbody>
					</table>
				</div>
				
				<div id="all-pages">
					<h2 style="display: inline;">Všetky stránky</h2>
                    <div class="filter-box"><input type="text" class="input-filter" id="input-filter" placeholder="Filter stránok"> </div>
					<table id="all-pages-table" class="pages-table widefat">
						<thead>
						<tr>
						    <th class="page-title-header">Názov stránky</th>
						    <th></th>
                        </tr>
						</thead>
						<tbody class="connectedSortable">
						<?php
						$all_pages = page_rotation_get_all_pages();
						
						foreach ($all_pages as $page_id) {
							$page_title = get_the_title( $page_id );
							
							//Create poster tag
							if( page_rotation_check_if_poster($page_id) ) {
								$poster_tag = "<div class='poster-tag'>Plagát</div>";
								$date = page_rotation_get_poster_date($page_id);
								$mark = "";
								//Mark green
								if( page_rotation_check_poster_date($page_id) && $date) {
                                    $mark = "<div class='poster-tag-valid'>Zobrazuje sa do $date</div>";
                                }
                                else if ($date > 0) {
	                                $mark = "<div class='poster-tag-invalid'>Zobrazuje sa do $date</div>";
                                }
                                $poster_tag .= $mark;
							}
							else {
								$poster_tag = "";
							}
							
							echo "<tr class='item-row'>
					<td class='page-title'><input type='hidden' name='none' value=$page_id />
					<span class='page-title-value'>$page_title</span> $poster_tag</td> 
					<td class='add-multiple'><a href='#'  id='add-multiple'><img src=$plus_icon_path /></a></td>
					<td class='delete-all'><a href='#'  id='delete-all'><img src=$x_icon_path /></a></td>
				</tr>";
						}
						?>
						</tbody>
					</table>
				</div>
			</div>




    <!-- SECTION FOR USER PERMISSIONS -->

        <h1>Práva používateľov</h1>
        <h3>Výber používateľov ktorý môžu spravovať sekvenciu</h3>
        <form method="post" action="">
            <ul>
                <?php
                $users = get_users();
                foreach ($users as $user) {
                    $permission = page_rotation_check_sequence_user_perm($user->ID, $sequence_id);
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
        <input type="submit" value="Uložiť" name="edit_sequence" class="button-primary">
        </form>


    	<div id="dialog-confirm" title="Zmazať všetky výskyty?">
			<p><span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>Všetky výskyty tejto stránky budú zmazané zo sekvencie. Ste si istý?</p>
		</div>
		<div id="dialog-form" title="Pridaj stránku viac krát">

			<form>
				<fieldset>
					<label for="number">Počet</label>
					<input type="text" name="number" id="number" value="3" class="text ui-widget-content ui-corner-all" style="display:block;">
					<label for="time">Čas</label>
					<input type="text" name="time" id="time" value="60" class="text ui-widget-content ui-corner-all"  style="display:block;">
				</fieldset>
			</form>
			<p class="error-message" style="display:none;">Pomer pridávaných stránok k stránkam v tabuľke je nevhodný</p>
		</div>
	</div>


<?php
}

function page_rotation_delete_selected_entry($IDs) {
	global $wpdb;
	$table_name = $wpdb->prefix . "page_rotation_sequences";
	
	foreach ($IDs as $id) {
		$sql = "SELECT start_page_id as id from $table_name
			WHERE id = $id";
		$redirect_id = $wpdb->get_results($sql);
		wp_delete_post($redirect_id[0]->id,true);
		$wpdb->delete($table_name, array('id' => $id));
	}
}

/**
 * Creates an array of all pages excluding trashed pages and pages in sequence category
 */
function page_rotation_get_all_pages() {
	//empty array to store pages
	$filtered_pages = array();
	$IDs= get_all_page_ids();

	foreach ($IDs as $id) {
		$category = get_the_category($id);
		if($category)
			$category = $category[0]->name;
		$status = get_post_status($id);
		//Check if page is not a start of a sequence or trashed
		if($category != 'Sekvencie' && $status != 'trash'
		   && $status != 'draft' && $status != 'auto-draft' && $category != "Obrazovky")
			array_unshift($filtered_pages,$id);
	}

	return $filtered_pages;
}

/**
 * Checks if given page is a poster or not
 * @param $page_id
 *
 * @return bool
 */
function page_rotation_check_if_poster($page_id) {
	$category = get_the_category($page_id);
	if ($category) {
		$category = $category[0]->name;
	}
	
	//If the post is a poster
	if($category == 'Plagáty') {
		return true;
	}
	//If it is not
	return false;
}

/**
 * Returns true if poster end date hasn't passed yet
 * @param $page_id
 *
 * @return bool
 */
function page_rotation_check_poster_date($page_id) {
    
    $date = page_rotation_get_poster_date($page_id);
    //Date has already passed
    if (time() - strtotime( $date ) > 0) {
        return false;
    }
    return true;
    
}

function page_rotation_get_poster_date($page_id) {
    $date = get_post_meta($page_id,'poster_end_date',true);
    return $date;
}



/**
 * Creates a redirectional page for sequence
 */
function page_rotation_create_redirect_page($seq_name, $seq_id) {
	$category_name = "Sekvencie";
	global $wpdb;
	//Get ID of the 'sequence' category
	$cat_id = get_cat_ID($category_name);

	//Create a page for redirection
	$post_id = wp_insert_post(array(
		'post_title' => $seq_name,
		'post_name' => $seq_name,
		'post_type' => 'page',
		'post_status' => 'publish',
		'post_category' => array($cat_id)
	));

	$table_name = $wpdb->prefix . "page_rotation_sequences";

	$wpdb->update($table_name,array( 'start_page_id' => $post_id), array( 'id' => $seq_id));

	add_post_meta($post_id,'sequence_start',true);
	add_post_meta($post_id,'sequence_id',$seq_id);
}
