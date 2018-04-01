<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 2.10.2017
 * Time: 9:44
 */


/* MARKUP CODE */

function fiit_ad_settings_page() {
    fiit_ad_settings_scripts();
    
    if (isset($_POST['new_company_submit'])) {
        fiit_ad_create_company($_POST['company_name'],$_POST['week_type']);
    }
    
    
    else if (isset($_POST['new_ad_submit'])) {
        
        fiit_ad_create_company_ad($_POST['company_id'], $_POST['ad_url']);
    }
	
	
    
	$url =  $_SERVER['REQUEST_URI'];
	$url = add_query_arg(['ac' => 'new_company'], $url);
	
	?>
	<div class="wrap">
		<h1 class="wp-heading-inline">Reklamný blok</h1>
		<a href="<?php echo $url; ?>" class="page-title-action">Pridať nový</a>
        
        <div class="week_number">
            <h5>Aktuálne prebieha <?php echo fiit_ad_get_week_number(); ?>. týždeň</h5>
        </div>
        
        <?php
            if (isset($_GET['ac'])) {
                if ($_GET['ac'] == 'new_company') {
                    fiit_ad_add_company_markup();
                }
            }
        ?>
        
        <div><h3>Párny týždeň</h3>
        <?php
            // Get all even companies and create a list of them
            $even_companies = fiit_ad_get_all_even_companies();
            fiit_ad_output_companies($even_companies);
        ?>
        </div>
        
        <div><h3>Nepárny týždeň</h3>
        <?php
            // Get all odd companies and create a list of them
            $odd_companies = fiit_ad_get_all_odd_companies();
            fiit_ad_output_companies($odd_companies);
        ?>
        </div>
        
	</div>
<?php
}

function fiit_ad_add_company_markup() {
    //Remove arguments from URL
	$url =  $_SERVER['REQUEST_URI'];
	$url = remove_query_arg('ac', $url);
    ?>
    
    <form method="post" action="<?php echo $url; ?>">
        <input type="text" name="company_name" placeholder="Názov spoločnosti">
        <input type="submit" name="new_company_submit" class="button-primary" value="Pridať spoločnosť">
        <br>
        <label>Týždeň</label>
        <input type="radio" name="week_type" value="even">Párny
        <input type="radio" name="week_type" value="odd">Nepárny
    </form>
    
<?php

}

function fiit_ad_output_companies($companies) {
	foreach ($companies as $company) {
  
		echo '<li>';
		echo "<span>{$company->name}</span>";
		echo "<input class='company_id' type=\"hidden\" name=\"company_id\" value=\"{$company->id}\" />";
		//echo '<a href="'. $url .'" class="page-title-action add-ad">Pridať URL</a>';
		echo '<a href="#" class="page-title-action add-ad">Pridať URL</a>';
		echo '<ul>';
		
		$all_ads = fiit_ad_get_all_company_ads($company->id);
		foreach ($all_ads as $ad) {
			$page_url = get_permalink($ad->ad_page_id);
			echo "<li>";
			echo "<a href =\"{$ad->poster_url}\">{$ad->poster_url}</a>";
			echo " | ";
			echo "<a href=\"{$page_url}\">{$page_url}</a>";
			echo "</li>";
		}
		echo '</ul>';
		echo '</li>';
	}
}


/************* BUSINESS LOGIC ***************/

/* INSERTS  */

/* Creates new company in DB */
function fiit_ad_create_company($name, $week) {
    global $wpdb;
    if ($week == 'even') {
        $week = true;
    }
    else {
        $week = false;
    }
    $sql = "select max(consecutive_number) from {$wpdb->prefix}fiit_ad_companies
    where even_week = {$week}";
    $max = $wpdb->get_var($sql);
    if (isset($max)) {
        $new_cons = $max + 1;
    }
    else {
        $new_cons = 1;
    }
    $wpdb->insert($wpdb->prefix . 'fiit_ad_companies', ['name' => $name, 'even_week' => $week, 'consecutive_number' => $new_cons]);
}

function fiit_ad_create_company_page($name, $url) {
    $cat_id = get_cat_ID('Partnerské reklamy');
	
	$post_id = wp_insert_post(array(
		'post_title' => $name,
		'post_status' => 'publish',
		'post_type' => 'page',
		'post_category' => array($cat_id),
		'meta_input' => array(
			'ad_url' => $url,
			'is_ad' => true,
		)
	));
	
	return $post_id;
}

function fiit_ad_create_company_ad($comp_id, $url) {
    global $wpdb;
    $count_ads = "SELECT COUNT(*) FROM {$wpdb->prefix}fiit_ad_company_ads WHERE comp_id = $comp_id";
    $count_ads = $wpdb->get_var($count_ads);
   
    $company_name = "SELECT name FROM {$wpdb->prefix}fiit_ad_companies WHERE id = $comp_id";
    $company_name = $wpdb->get_var($company_name);
    
    $company_name .= '-' . ($count_ads + 1);
    
    $local_url = fiit_ad_upload_image($url);
    
    $page_id = fiit_ad_create_company_page($company_name, $local_url);
    
    $wpdb->insert("{$wpdb->prefix}fiit_ad_company_ads", ['comp_id' => $comp_id, 'ad_page_id' => $page_id, 'poster_url' => $url]);
}


/* SELECTS */

function fiit_ad_get_all_companies() {
    global $wpdb;
    $sql = "SELECT * FROM {$wpdb->prefix}fiit_ad_companies";
    return $wpdb->get_results($sql);
}

function fiit_ad_get_all_even_companies() {
    global $wpdb;
    $sql = "SELECT * FROM {$wpdb->prefix}fiit_ad_companies where even_week = true ORDER BY consecutive_number";
    return $wpdb->get_results($sql);
}
function fiit_ad_get_all_odd_companies() {
    global $wpdb;
    $sql = "SELECT * FROM {$wpdb->prefix}fiit_ad_companies where even_week = false ORDER BY consecutive_number";
    return $wpdb->get_results($sql);
}


function fiit_ad_get_all_company_ads($comp_id) {
    global $wpdb;
    
    $sql = "SELECT * FROM {$wpdb->prefix}fiit_ad_company_ads WHERE comp_id = $comp_id";
    return $wpdb->get_results($sql);
}

//Returns week number
function fiit_ad_get_week_number() {
    global $wpdb;
	return $wpdb->get_var('select week(now());');
}


/* IMAGE HANDLING */

function fiit_ad_upload_image($url) {
 
 
	$file = $url;
	$filename = basename($file);
	$upload_file = wp_upload_bits($filename, null, file_get_contents($file));
	if (!$upload_file['error']) {
		$wp_filetype = wp_check_filetype($filename, null );
		$attachment = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
			'post_content' => '',
			'post_status' => 'inherit'
		);
		$attachment_id = wp_insert_attachment( $attachment, $upload_file['file']);
		if (!is_wp_error($attachment_id)) {
			require_once(ABSPATH . "wp-admin" . '/includes/image.php');
			$attachment_data = wp_generate_attachment_metadata( $attachment_id, $upload_file['file'] );
			wp_update_attachment_metadata( $attachment_id,  $attachment_data );
		}
		
		
		$path = wp_upload_dir()['path'] . '/';
		$filename = pathinfo(wp_get_attachment_url($attachment_id));
		$img = new imagick();
		$img->setResolution(300,300);
		$img->readImage(wp_get_attachment_url($attachment_id));
		$img->setCompressionQuality(100);
		$img->stripImage();
		$img->setImageResolution(300,300);
		$img->scaleImage(1920,1080);
		$img->setImageFormat('jpeg');
		unlink($path .  $filename['basename'] );
		$img->writeImage($path .  $filename['filename'] . '.jpg');
		$img->destroy();
		
		//Update info in media library
		wp_update_attachment_metadata( (int) $attachment_id, wp_generate_attachment_metadata( (int) $attachment_id, $path .  $filename['filename'] . '.jpg' ) );
		update_attached_file($attachment_id, $path .  $filename['filename'] . '.jpg');
	}
	
	return wp_get_attachment_url($attachment_id);
}

function fiit_ad_settings_scripts() {
	//For warnings
	wp_enqueue_script('ad-script', plugin_dir_url(__FILE__) .
	                                'js/admin.js',array('jquery'),1.0);
}

function fiit_ad_update_all_posters() {

}