<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 24.12.2017
 * Time: 15:25
 */

function announcements_permissions_page() {
	if(isset($_POST['announcement-permissions-update'])) {
		announcements_drop_all_permisions();
		if (isset($_POST['user'])) {
			announcements_save_all_permissions($_POST['user']);
		}
		
	}
	
	?>
	<h1>Nastavenie práv pre pridávanie oznamov</h1>
	<form method="post" action="">
	<?php
		require_once('Permission_List.php');
		$table = new Permission_List();
		$table->prepare_items();
		$table->display();
	?>
	<input type="submit" name="announcement-permissions-update" class="button-primary" value="Uložiť"/>
	</form>
<?php
}

function announcements_drop_all_permisions() {
	global $wpdb;
	$wpdb->query("DELETE  FROM {$wpdb->prefix}announcements_permissions");
}

function announcements_save_all_permissions( $users_permissions ) {
	global $wpdb;
	
	foreach ($users_permissions as $key => $user) {
		foreach ($user as $cat_id) {
			announcements_save_permission($key, $cat_id);
		}
	}
}

function announcements_save_permission($user_id, $category_id) {
	global $wpdb;
	
	$wpdb->insert("{$wpdb->prefix}announcements_permissions",['user_id' => $user_id, 'announcement_id' => $category_id]);
}