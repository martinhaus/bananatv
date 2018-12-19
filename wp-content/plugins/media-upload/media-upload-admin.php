<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 4.10.2016
 * Time: 14:34
 */


function media_upload_success_notice($id) {
	$url = get_permalink($id);
    ?>
	<div class="notice notice-success is-dismissible">
		<p><?php _e( "Plagát bol úspešne pridaný <a href=$url>$url</a>", '' ); ?></p>
	</div>
	<?php
}

function media_upload_iframe_success_notice($post_id) {
    
    $url = get_permalink($post_id);
    
	?>
	<div class="notice notice-success is-dismissible">
		<p><?php _e( "Iframe bol úspešne pridaný <a href=$url>$url</a>", '' ); ?></p>
	</div>
	<?php
}

function media_upload_success_edit_notice($post_id) {
    
    $url = get_permalink($post_id);
    
	?>
	<div class="notice notice-success is-dismissible">
		<p><?php _e( "Plagát bol upravený <a href=$url>$url</a>", '' ); ?></p>
	</div>
	<?php
}

function media_upload_add_to_sequences($sequences, $post_id, $display_time) {
    global $wpdb;
    
	$table_pages = $wpdb->prefix . "page_rotation_pages";
	
    
    foreach ($sequences as $sequence) {
        
        //Get last post in the sequence
        $sql = "SELECT * FROM wp_page_rotation_pages WHERE sequence_id = $sequence
        ORDER BY consecutive_number DESC
        LIMIT 1";
        
        $last_post = $wpdb->get_results($sql);
       
        //Get consecutive number from the last post and inc by 1
        $new_consc = $last_post[0]->consecutive_number + 1;
        $next_page = $last_post[0]->next_page;
        $last_post_id = $last_post[0]->id;
        
        $wpdb->update($table_pages,array( 'next_page' => $post_id),
            array('id' => $last_post_id));
        $wpdb->insert($table_pages,array(
           'next_page' => $next_page,
            'prev_page' => $last_post_id,
            'consecutive_number' => $new_consc,
            'sequence_id' => $sequence,
            'page_id' => $post_id,
            'display_time' => $display_time
        ));
        
    }  
}

function media_admin_page() {

    
    
    //Parse get parameters
	if(isset($_GET['action'])) {
	    //Display edit page
	    if ($_GET['action'] == 'edit') {
	        media_admin_edit_page();
        }
    }
    else {
	    //If form was submitted
	    if ( isset( $_POST['create'] ) ) {
		    $tags       = $_POST['tag-list'];
		    $name       = $_POST['name'];
		    $image_url  = $_POST['image-url'];
		    $date_start = $_POST['date_start'];
		    $date_end   = $_POST['date_end'];
		    $name       = str_replace( ' ', '_', $name );
		    if ( isset( $_POST['image-url'] ) ) {
		        if ($_POST['subtype'] != 'gif') {
                    $image_url = media_upload_resize_image( $_POST['attachement-id'], $image_url );
                }
			    $id        = media_upload_create_post( $name, $tags, $image_url, $date_start, $date_end );
			    //If post is to be added to sequences
			    if ( isset( $_POST['sequences'] ) ) {
				    media_upload_add_to_sequences( $_POST['sequences'], $id, $_POST['display_time'] );
			    }
			
			    media_upload_success_notice( $id );
		    } else if ( isset( $_POST['pdf-url'] ) ) {
			    media_upload_create_pdf_sequence( $_POST['pdf-url'], $tags, $name, $_POST['display_time'] );
		    } else if ( isset( $_POST['zip-url'] ) ) {
			    media_upload_create_zip_sequence( $_POST['zip-url'], $tags, $name, $_POST['display_time'] );
		    }
	    }
	 
	    ?>

        <div id="media-upload">
            <h1>Pridanie plagátu</h1>
            <form method="post" action="<?php $_SERVER['PHP_SELF'] ?>">
                <div id="control">
                    <div class="first-input-group">
                        <label for="name">Názov stránky</label>
                        <input type="text" name="name" id="name">
                    </div>
                    <input type="button" class="button-secondary add-image" value="Vyber obrázok">
                    <label for="tags">Značky</label>
                    <input type="text" id="tags">
                    <input type="button" class="button-secondary add-tag" value="Pridaj značku">
                    <ul id="tag-list"></ul>
                    <label for="date_start">Dátum začiatku zobrazovania</label>
                    <input type="text" id="date_start" name="date_start">
                    <label for="date_end">Dátum konca zobrazovania</label>
                    <input type="text" id="date_end" name="date_end">
                    <label for="display_time">Dĺžka zobrazenia (v sekundách)</label>
                    <input type="number" id="display_time" name="display_time" value="30">
				    <?php media_upload_create_sequence_box(); ?>
                    <input type="submit" name="create" id="create_button" style='display: block;' class="button-primary"
                           value="Potvrdiť">
                </div>
                <div id="thumbnail"></div>
            </form>
        </div>
	    <?php
    }
}

function media_admin_edit_page() {
	
	// If edit form was submitted
    if ( isset($_POST['edit_save']) ) {
		media_upload_update_details($_POST['post_id'], $_POST['name'], $_POST['date_start'], $_POST['date_end']);
	    media_upload_success_edit_notice($_POST['post_id']);
	}
	
	
	$url = "";
    $details = media_upload_load_poster_details($_GET['id']);
    
    ?>
    <div id="media-upload">
        <h1>Úprava plagátu</h1>
        <form method="post" action="<?php $_SERVER['PHP_SELF'] ?>">
            <div id="control">
                <div class="first-input-group">
                    <label for="name">Názov stránky</label>
                    <input type="text" name="name" id="name" value="<?php echo $details['title']; ?>">
                </div>
                <input type="hidden" name="post_id" value="<?php echo $_GET['id']; ?>">
                <label for="date_start">Dátum začiatku zobrazovania</label>
                <input type="text" id="date_start" name="date_start" value="<?php echo $details['start_date']; ?>">
                <label for="date_end">Dátum konca zobrazovania</label>
                <input type="text" id="date_end" name="date_end" value="<?php echo $details['end_date']; ?>">
                <input type="submit" name="edit_save" id="save_button" style='display: block;'  class="button-primary" value="Potvrdiť">
            </div>
            <div id="thumbnail"></div>
        </form>
    </div>
	
	
	<?php
}

function media_upload_update_details($id, $title, $start_time, $end_time ) {
    
    wp_update_post(['ID' => $id, 'post_title' => $title]);
    update_post_meta($id, 'poster_start_date', $start_time);
    update_post_meta($id, 'poster_end_date', $end_time);
}

function media_upload_load_poster_details( $id ) {
    $details = [];
    
    $details['title'] = get_the_title($id);
    $details['start_date'] = get_post_meta($id, 'poster_start_date', true);
    $details['end_date'] = get_post_meta($id, 'poster_end_date', true);
    
    return $details;
}

function media_upload_resize_image($id, $img_url) {
	$path = wp_upload_dir()['path'] . '/';
	$filename = pathinfo($img_url);
	$file = $path .  $filename['basename'];

	$img = new imagick();
	$img->setResolution(300,300);
    $img->readImage($file);
    $img->setCompressionQuality(100);
    $img->stripImage();
    $img->setImageResolution(300,300);
    $img->scaleImage(1920,1080);
    $img->setImageFormat('jpeg');
    unlink($path .  $filename['basename'] );
	$img->writeImage($path .  $filename['filename'] . '.jpg');
	$img->destroy();
	
	//Update info in media library
	wp_update_attachment_metadata( (int) $id, wp_generate_attachment_metadata( (int) $id, $path .  $filename['filename'] . '.jpg' ) );
	update_attached_file($id, $path .  $filename['filename'] . '.jpg');
	return wp_upload_dir()['url'] . '/' . $filename['filename'] . '.jpg';
	
}

function media_upload_get_all_sequences($user_id = null) {
    
    global $wpdb;
    
    $table_name = $wpdb->prefix . "page_rotation_sequences";
    $sql = "SELECT * from $table_name";
    
    $all_sequences = $wpdb->get_results($sql);

    if ($user_id) {
        require_once (ABSPATH . 'wp-content/plugins/page-rotation/page-rotation-screen-permissions.php');
        foreach ($all_sequences as $key => $sequence) {
            if (!current_user_can('administrator') &&
                !current_user_can('editor') &&
                !page_rotation_check_sequence_user_perm($user_id, $sequence->id)) {
                unset($all_sequences[$key]);
            }
        }
    }
    return $all_sequences;
}

function media_upload_create_sequence_box() {
    ?>
    <div id="sequence_box_overall">
    <label for="sequence_box">Umiestniť do sekvencií:</label>
    <h4 class="sequence_box_title">Sekvencie</h4>
    <div id="sequence_box" >
        <ul>
            <?php
                $sequences = media_upload_get_all_sequences(get_current_user_id());
                foreach ($sequences as $sequence) {
                    $name = $sequence->name;
                    $id = $sequence->id;
                    echo "<li><input type='checkbox' value='$id' name='sequences[]'>$name</input></li>";
                }
            ?>
        </ul>
        
    </div>
    </div>
<?php
    
    
}

function media_upload_create_zip_sequence($zip_url, $tags, $name, $timing) {
	
    //Create new folder for the images
	$path = wp_upload_dir()['path'] . '/zip_' . $name . '/';
	$temp_zip = $path . 'temp.zip';
	wp_mkdir_p($path);
    $file = wp_upload_dir()['path'] . '/' . pathinfo($zip_url)['basename'];
	if(copy($file, $temp_zip)) {
		$zip = new ZipArchive;
		if ($zip->open($temp_zip, ZIPARCHIVE::CHECKCONS) == TRUE) {
			$zip->extractTo($path);
			$zip->close();
			
			//Delete temp zip
            unlink($temp_zip);
			
            //Get all files in a folder
			
			$objects = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($path),
				RecursiveIteratorIterator::SELF_FIRST
			);
			foreach ($objects as $file => $object) {
				$basename = $object->getBasename();
				if ($basename == '.' or $basename == '..') {
					continue;
				}
				if ($object->isDir()) {
					continue;
				}
				$files[] = $object->getPathname();
			}
			
			
			//Convert to 1920x1080
			$img = new imagick();
			$img->setResolution(300,300);
			$i = 0;
			foreach ($files as $file) {
			    $img->readImage($file);
			    $img->setCompressionQuality(100);
			    $img->stripImage();
			    $img->setImageResolution(300,300);
				$img->scaleImage(1920,1080);
				$img->setImageFormat('jpeg');
				$img_name = '/' . $name .'-'.$i.'.jpg';
				$i++;
				$img->writeImage($path . $img_name);
				$image_urls[] = wp_upload_dir()['url'] . '/zip_' . $name . $img_name;
				unlink($file);
            }
            $img->destroy();
		}
	}
	media_upload_create_sequence_of_images($name,$image_urls, $tags,$timing);
}

function media_upload_create_pdf_sequence($pdf_url, $tags, $name, $timing) {
	$image_urls = [];

    $path = wp_upload_dir()['path'] . '/';
    $filename = pathinfo($pdf_url);
    $file = $path .  $filename['basename'];


    $img = new imagick();
	$img->setResolution(300,300);
	$img->readImage($file);
	$img->setImageCompressionQuality(100);
	$num_pages = $img->getNumberImages();
	$img->stripImage();
    $path = wp_upload_dir()['path'] . '/pdf_' . $name;
    wp_mkdir_p($path);
	for($i = 0;$i < $num_pages; $i++) {
		// Set iterator postion
		$img->setIteratorIndex($i);
		$img->setImageUnits(imagick::RESOLUTION_PIXELSPERINCH);
		$img->setImageResolution(300,300);
		$img->scaleImage(1920,1080);
		// Set image format
		$img->setImageFormat('jpeg');
		$img_name = '/' . $name .'-'.$i.'.jpg';
		// Write Images to temp 'upload' folder
		$img->writeImage($path . $img_name);
		$image_urls[] = wp_upload_dir()['url'] . '/pdf_' . $name . $img_name;
	}
	$img->destroy();
	
	media_upload_create_sequence_of_images($name,$image_urls, $tags,$timing);
}

function media_upload_create_sequence_of_images($name, $image_urls,$tags, $timing) {
	//Create new sequence for posters
	$sequence_id = media_upload_create_poster_sequence($name);
	require_once ( ABSPATH . 'wp-content/plugins/page-rotation/page-rotation-admin.php');
	page_rotation_create_redirect_page($name,$sequence_id);
	//Insert posters into newly created sequence
	media_upload_insert_posters($sequence_id,$image_urls,$name,$tags,$timing);
	
	media_upload_success_notice($sequence_id);
}

function media_upload_create_poster_sequence($name) {
    global $wpdb;
	
	$wpdb->insert("{$wpdb->prefix}page_rotation_sequences",array(
		'name' => $name,
		'date_created' => date("Y-m-d H:i:s")
	));
	return $wpdb->insert_id;
}

function media_upload_insert_posters($sequence_id, $image_urls, $name, $tags, $timing) {
    global $wpdb;
    
    
    //Put every post into the sequence
    for($i=0; $i < count($image_urls); $i++) {
        $post_id = media_upload_create_post($name . ' plagát - '. $i,$tags,$image_urls[$i], null, null);
        media_upload_insert_poster_sequence($i,$post_id,count($image_urls),$timing,$sequence_id);
    }
    
    
}

function media_upload_insert_poster_sequence($index, $post_id, $no_of_posts, $timing, $sequence_id) {
    global $wpdb;
    
    if ($index != 0) {
        $prev_page = $post_id - 1;
    }
    else {
        $prev_page = -1;
    }
    
    if($index == $no_of_posts - 1) {
        $next_page = $post_id - $no_of_posts + 1;
    }
    else {
        $next_page = $post_id + 1;
    }
	
	$wpdb->insert("{$wpdb->prefix}page_rotation_pages",array(
		'page_id' => $post_id,
		'prev_page' => $prev_page,
		'next_page' => $next_page,
		'display_time' => $timing,
		'consecutive_number' => $index +1,
		'sequence_id' =>$sequence_id
	));
    
    
    
}

function media_upload_create_post($name, $tags, $image_url, $date_start, $date_end) {
	$cat_id = get_cat_ID('Plagáty');
	
	$post_id = wp_insert_post(array(
		'post_title' => $name,
		'post_status' => 'publish',
		'post_type' => 'page',
		'tags_input' => $tags,
		'post_category' => array($cat_id),
		'meta_input' => array(
			'poster_url' => $image_url,
			'is_poster' => true,
            'poster_start_date' => $date_start,
            'poster_end_date' => $date_end
		)
		
	));
	
	return $post_id;
}

function enqueue_media_uploader() {
	wp_enqueue_media();
	wp_enqueue_script('upload-dialog',plugin_dir_url(__FILE__) . 'js/upload-dialog.js',array(),true);
	
	wp_enqueue_script('tag-management', plugin_dir_url(__FILE__) . 'js/tag-management.js',array(),true);
	
	
	wp_localize_script( 'upload-dialog', 'ajax_object',
		array( 'ajax_url' => admin_url( 'admin-ajax.php' )) );
	
}

function media_upload_enqueue_styles() {
	wp_register_style( 'media-upload-admin', plugin_dir_url(__FILE__) . 'css/media-upload-admin.css', false, '1.0.0' );
	wp_enqueue_style('media-upload-admin');
}

function media_upload_submit_iframe_page($name, $url, $refresh_rate) {
	
    //Get category ID
	$cat_id = get_cat_ID('Externý obsah');
	
	//Create post
	$post_id = wp_insert_post(array(
		'post_title' => $name,
		'post_status' => 'publish',
		'post_type' => 'page',
		'post_category' => array($cat_id),
		'meta_input' => array(
			'iframe_url' => $url,
			'is_iframe' => true,
			'iframe_refresh_rate' => $refresh_rate
		)
	));
	
	media_upload_iframe_success_notice($post_id);
    
}

function media_upload_iframe_admin_page() {
    
    //If page was submitted
    if( isset($_POST['submit_iframe']) ) {
        $name = $_POST['page_name'];
        $url = $_POST['page_url'];
        $refresh_rate = $_POST['page_refresh_rate'];
        
        //If all necessary fields were posted
        if (isset($name) && isset($refresh_rate) && isset($url)) {
	        media_upload_submit_iframe_page($name, $url, $refresh_rate);
        }
        else {
            wp_die("Neboli zadane vsetky potrebne udaje");
        }
        
    }
    
    ?>
    <div id="iframe-settings">
        <h1>Pridanie iframe stránky</h1>
        <form method="post" action="<?php $_SERVER['PHP_SELF'] ?>">
            <label for="page_name_input">Názov stránky</label>
            <input id="page_name_input" type="text" placeholder="Názov stránky" name="page_name">
            <label for="page_url_input">URL stránky</label>
            <input id="page_url_input" type="text" placeholder="URL stránky" name="page_url">
            <label for="page_time_input">Interval obnovenia</label>
            <input id="page_time_input" type="text" placeholder="Interval obnovenia" name="page_refresh_rate">
            <input type="submit" value="Potvrdiť" name="submit_iframe" class="button-primary">
        </form>
        
    </div>
    
<?php
}

add_action("admin_enqueue_scripts", "enqueue_media_uploader");
add_action('admin_enqueue_scripts','media_upload_enqueue_styles');



