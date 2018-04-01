<?php

/*  Copyright 2010 Simon Wheatley

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/


/**
 * Whether currently in a post template.
 *
 * This template tag allows you to determine whether or not you are in a post
 * template. You can optionally provide a template name and then the check will be
 * specific to that template.
 *
 * @since 1.3
 * @uses $wp_query
 *
 * @param string $template A template file name (not complete file path), if specific matching is required.
 * @return bool False on failure, true if success.
 */
function is_post_template($template = '') {
	if (!is_single()) {
		return false;
	}

	global $wp_query;

	$post = $wp_query->get_queried_object();
	$post_template = get_post_meta( $post->ID, 'custom_post_template', true );

	// We have no argument passed so just see if a page_template has been specified
	if ( empty( $template ) ) {
		if (!empty( $post_template ) ) {
			return true;
		}
	} elseif ( $template == $post_template) {
		return true;
	}

	return false;
}



?>