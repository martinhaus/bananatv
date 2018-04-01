<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 15.9.2017
 * Time: 22:57
 */



function page_rotation_screen_permissions_delete_all($screen_id) {
    global $wpdb;
    $wpdb->delete("{$wpdb->prefix}usermeta", ['meta_key' => 'can_manage_screen-' . $screen_id]);
}

function page_rotation_screen_permissions_update($user, $screen) {
    $key =  'can_manage_screen-' . $screen;
    add_user_meta($user, $key ,true);
}

function page_rotation_check_user_perm ($user_id, $screen_id) {
    $key =  'can_manage_screen-' . $screen_id;
	$data = get_user_meta($user_id, $key);
	if ($data) {
		return true;
	}
	return false;
}


function page_rotation_sequence_permissions_delete_all($screen_id) {
    global $wpdb;
    if ($screen_id != null) {
        $wpdb->delete("{$wpdb->prefix}usermeta", ['meta_key' => 'can_manage_sequence-' . $screen_id]);
    }
}

function page_rotation_sequence_permissions_update($user, $screen) {
    $key =  'can_manage_sequence-' . $screen;
    add_user_meta($user, $key ,true);
}

function page_rotation_check_sequence_user_perm ($user_id, $screen_id) {
    $key =  'can_manage_sequence-' . $screen_id;
    $data = get_user_meta($user_id, $key);
    if ($data) {
        return true;
    }
    return false;
}
