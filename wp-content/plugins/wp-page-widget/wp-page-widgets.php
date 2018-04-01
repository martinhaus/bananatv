<?php
/*
  Plugin Name: Wordpress Page Widgets
  Plugin URI: http://www.codeandmore.com/products/wordpress-plugins/wp-page-widget/
  Description: Allow users to customize Widgets per page.
  Author: CodeAndMore
  Version: 3.2
  Author URI: http://www.codeandmore.com/
 */

define('PAGE_WIDGET_VERSION', '3.2');

/* Hooks */
add_action('plugins_loaded', 'pw_load_plugin_textdomain');
add_action('admin_init', 'pw_init');
add_action('admin_print_scripts', 'pw_print_scripts');
add_action('admin_print_styles', 'pw_print_styles');
add_action('admin_menu', 'pw_admin_menu');
add_action('save_post', 'pw_save_post', 10, 2);
add_action('edit_term', "pw_save_term", 10, 2);

/* AJAX Hooks */
add_action('wp_ajax_pw-widgets-order', 'pw_ajax_widgets_order');
add_action('wp_ajax_pw-save-widget', 'pw_ajax_save_widget');
add_action('wp_ajax_pw-toggle-customize', 'pw_ajax_toggle_customize');
//add_action('wp_ajax_pw-get-taxonomy-widget', 'pw_returnTaxonomyWidget');
add_action('wp_ajax_pw-remove-inactive-widget', 'pw_ajax_remove_inactive_widget');

/* Filters */
add_filter('sidebars_widgets', 'pw_filter_widgets');
add_filter('widget_display_callback', 'pw_filter_widget_display_instance', 10, 3);
add_filter('widget_form_callback', 'pw_filter_widget_form_instance', 10, 2);
add_filter( 'dynamic_sidebar_params', 'sidebar_params', 9999, 2);

function pw_load_plugin_textdomain()
{
	load_plugin_textdomain('wp-page-widgets', false, basename( dirname( __FILE__ ) ) . '/languages' );
}

function pw_init() {
	global $wpdb;

	$current_version = get_option('page_widget_version', '1.0');
	$upgraded        = false;

	if (version_compare($current_version, '1.1', '<')) {
		// we set enable customize sidebars for posts which hve been customized before.
		$post_ids = $wpdb->get_col($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key LIKE %s", '_sidebars_widgets'));

		if (!empty($post_ids)) {
			foreach ($post_ids as $post_id) {
				update_post_meta($post_id, '_customize_sidebars', 'yes');
			}
		}
		$upgraded = true;
	}
	if (version_compare($current_version, '2.5', '<')) {
		// do nothing
		$upgraded = true;
	}
	if ($upgraded) {
		update_option('page_widget_version', PAGE_WIDGET_VERSION);
	}
}

function pw_print_scripts() {
	global $pagenow, $typenow;

	// currently this plugin just work on edit page screen.
	if (
			in_array($pagenow, array('post-new.php', 'post.php', 'edit-tags.php', 'term.php'))
			||
			// Page widget config for front page, search page
			( in_array($pagenow, array('admin.php')) && (($_GET['page'] == 'pw-front-page') || ($_GET['page'] == 'pw-search-page')) )
	) {
		if (is_plugin_active('image-widget/image-widget.php')) {
			wp_enqueue_script('tribe-image-widget', WP_PLUGIN_URL . '/image-widget/resources/js/image-widget.js', array('jquery', 'media-upload', 'media-views'), false, true);
			wp_localize_script( 'tribe-image-widget', 'TribeImageWidget', array(
			'frame_title' => __( 'Select an Image', 'image_widget' ),
			'button_title' => __( 'Insert Into Widget', 'image_widget' ),
			) );
		}

		// Simple Link List Widget plugin support
		if (is_plugin_active('simple-link-list-widget/simple-link-list-widget.php')) {
			wp_enqueue_script( 'sllw-sort-js', WP_PLUGIN_URL .'/simple-link-list-widget/js/sllw-sort.js');
		}

		if (is_plugin_active('easy-related-posts/easy_related_posts.php')) {
			wp_enqueue_script( 'wp-color-picker');
		}

		wp_enqueue_script('pw-widgets', plugin_dir_url(__FILE__) . 'assets/js/page-widgets.js', array('jquery', 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-droppable'), PAGE_WIDGET_VERSION, true);
		wp_localize_script( 'pw-widgets', 'wp_page_widgets', array(
			'remove_inactive_widgets_text'  => __('Press the following button will remove all of these inactive widgets', 'wp-page-widgets'),
			'remove_inactive_widgets' => __( 'Remove inactive widgets', 'wp-page-widgets' ),
		) );
	}
}

function pw_print_styles() {
	global $pagenow, $typenow;

	// currently this plugin just work on edit page, edit tags screen.
	if (
			in_array($pagenow, array('post-new.php', 'post.php', 'edit-tags.php', 'term.php'))
			||
			// Page widget config for front page, search page
			( in_array($pagenow, array('admin.php')) && (($_GET['page'] == 'pw-front-page') || ($_GET['page'] == 'pw-search-page')) )
	) {
		if (is_plugin_active('custom-field-list-widget/widget_custom_field_list.php')) {
			wp_enqueue_style('pw-widgets3', WP_PLUGIN_URL . '/custom-field-list-widget/widget_custom_field_list_widgetsettings.css', array());
		}

		// Simple Link List Widget plugin support
		if (is_plugin_active('simple-link-list-widget/simple-link-list-widget.php')) {
			wp_enqueue_style( 'sllw-css', WP_PLUGIN_URL .'/simple-link-list-widget/css/sllw.css');
		}

		if ( version_compare( get_bloginfo('version'), '3.8', '<' ) ) {
			wp_enqueue_style('pw-widgets', plugin_dir_url(__FILE__) . 'assets/css/page-widgets.css', array(), PAGE_WIDGET_VERSION);
		}
	}

	if ( version_compare( get_bloginfo('version'), '3.8', '<' ) ) {
		wp_enqueue_style('pw-style', plugin_dir_url(__FILE__) . 'assets/css/style.css', array(), PAGE_WIDGET_VERSION);
	} else {
		wp_enqueue_style('pw-style', plugin_dir_url(__FILE__) . 'assets/css/style-3.8.css', array(), PAGE_WIDGET_VERSION);
	}

}

function pw_admin_menu() {
	// check user capability (only allow Editor or above to customize Widgets

	$settings = pw_get_settings();

	if (current_user_can('edit_posts')) {
		// add Page Widgets metabox
		foreach ($settings['post_types'] as $post_type) {
			add_meta_box('pw-widgets', __('Page Widgets', 'wp-page-widgets'), 'pw_metabox_content', $post_type, 'advanced', 'high');
		}

		//add Taxonomy Widgets metabox
		foreach ($settings['taxonomies'] as $taxonomy) {
			add_action($taxonomy . '_edit_form', 'pw_showTaxonomyWidget', 99, 2);
		}
	}

	// options page
	// add_options_page('Page Widgets', 'Page Widgets', 'manage_options', 'pw-settings', 'pw_settings_page');
	// Menu page
	add_menu_page('Page Widgets', __('Page Widgets', 'wp-page-widgets'), 'manage_options', 'pw-settings', 'pw_settings_page');

	// Add a submenu to the custom top-level menu: front page
	//add_submenu_page('pw-settings', 'Front page', 'Front page', 'manage_options', 'pw-front-page', 'pw_front_page');
	// Add a submenu to the custom top-level menu: search page
	add_submenu_page('pw-settings', __('Search page', 'wp-page-widgets'), __('Search page', 'wp-page-widgets'), 'manage_options', 'pw-search-page', 'pw_search_page');
}

function pw_settings_page() {
	global $wp_registered_sidebars;

	if (isset($_POST['save-changes'])) {
		$opts = stripslashes_deep($_POST['pw_opts']);

		update_option('pw_options', $opts);
		echo '<div id="message" class="updated fade"><p>'.__('Saved Changes', 'wp-page-widgets').'</p></div>';
	}

	$opts = pw_get_settings();
	$post_types = get_post_types('', false);
	?>

	<div class="wrap">
		<h2><?php _e('Settings - Page Widgets', 'wp-page-widgets'); ?></h2>

		<div class="liquid-wrap">
			<div class="liquid-left">
				<div class="panel-left">
					<form action="" method="post">
						<table class="form-table">
							<tr>
								<th><?php _e('Would you like to make a donation?', 'wp-page-widgets')?></th>
								<td>
									<input type="radio" name="pw_opts[donation]" value="yes" <?php checked("yes", $opts['donation']) ?> /> <?php _e('Yes I have donated at least $5. Thank you for your nice work. And hide the donation message please.', 'wp-page-widgets'); ?> <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=X2CJ88BHMLAT6"><?php _e('Donate Now', 'wp-page-widgets'); ?></a>.
									<br />
									<input type="radio" name="pw_opts[donation]" value="no" <?php checked("no", $opts['donation']) ?> /> <?php _e('No, I want to use this without donation.', 'wp-page-widgets'); ?>

								</td>
							</tr>
							<tr>
								<th><?php _e('Available for post type', 'wp-page-widgets'); ?></th>
								<td>
									<?php
									foreach ($post_types as $post_type => $post_type_obj) {
										if (in_array($post_type, array('attachment', 'revision', 'nav_menu_item')))
											continue;
										echo '<input type="checkbox" name="pw_opts[post_types][]" value="' . $post_type . '" ' . checked(true, in_array($post_type, (array) $opts['post_types']), false) . ' /> ' . $post_type_obj->labels->singular_name . '<br />';
									}
									?>
								</td>
							</tr>
							<tr>
								<th><?php _e('Which sidebars you want to customize', 'wp-page-widgets'); ?></th>
								<td>
									<?php
									foreach ($wp_registered_sidebars as $sidebar => $registered_sidebar) {
										echo '<input type="checkbox" name="pw_opts[sidebars][]" value="' . $sidebar . '" ' . checked(true, in_array($sidebar, (array) $opts['sidebars']), false) . ' /> ' . $registered_sidebar['name'] . '<br />';
									}
									?>
								</td>
							</tr>

                            <tr>
								<th><?php _e('Select Customize by default (when adding new)', 'wp-page-widgets'); ?></th>
								<td>
									<input type="radio" name="pw_opts[customize_by_default]" value="yes" <?php checked("yes", $opts['customize_by_default']) ?> /> <?php _e('Yes, I want to select Customize when adding new things.', 'wp-page-widgets'); ?>
									<br />
									<input type="radio" name="pw_opts[customize_by_default]" value="no" <?php checked("no", $opts['customize_by_default']) ?> /> <?php _e('No, I want to select Default setting when adding new things.', 'wp-page-widgets'); ?>
								</td>
							</tr>
						</table>
						<p class="submit">
							<input type="submit" class="button-primary" name="save-changes" value="<?php _e('Save Changes', 'wp-page-widgets'); ?>" />
						</p>
					</form>
				</div>
			</div>

			<div class="liquid-right">
				<div class="panel-right">
					<!--				<div class="panel-box">
															<div class="handlediv"><br /></div>
															<h3 class="hndle">Test</h3>
															<div class="inside">

															</div>
													</div>-->
				</div>
			</div>
		</div>
	</div>
	<?php
}

// pw_search_page() displays the search page setting on pw widget
function pw_search_page() {
	global $wp_registered_sidebars, $sidebars_widgets, $wp_registered_widgets;

	$settings = pw_get_settings();

	// register the inactive_widgets area as sidebar
	register_sidebar(array(
		'name' => __('Inactive Widgets'),
		'id' => 'wp_inactive_widgets',
		'description' => '',
		'before_widget' => '',
		'after_widget' => '',
		'before_title' => '',
		'after_title' => '',
	));

	$sidebars_widgets = wp_get_sidebars_widgets();
	if (empty($sidebars_widgets))
		$sidebars_widgets = wp_get_widget_defaults();


	$customize = get_option('_pw_search_page', 'no') ? get_option('_pw_search_page', 'no') : 'no';

	// include widgets function
	if (!function_exists('wp_list_widgets'))
		require_once(ABSPATH . '/wp-admin/includes/widgets.php');
	?>
	<div class="wrap">
		<h2><?php _e('Page widgets for Search page', 'wp-page-widgets'); ?></h2>
		<div class="postbox " id="pw-widgets">
			<div title="<?php _e('Click to toggle', 'wp-page-widgets'); ?>" class="handlediv"><br></div>
			<h3 class="hndle"><span><?php _e('Widgets area', 'wp-page-widgets'); ?></span></h3>
			<div class="inside">
				<div style="padding: 5px;">
					<?php
					if ($settings['donation'] != 'yes') {
						echo '<div id="donation-message"><p>'.__('Thank you for using this plugin. If you appreciate our works, please consider to', 'wp-page-widgets').' <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=X2CJ88BHMLAT6">'.__('donate us', 'wp-page-widgets').'</a>. '.__('With your help, we can continue supporting and developing this plugin.', 'wp-page-widgets').'<br /><a href="' . admin_url('options-general.php?page=pw-settings') . '"><small>'.__('Hide this donation message', 'wp-page-widgets').'</small></a>.</p></div>';
					}
					?>
				</div>

				<div style="padding: 5px;">
					<input type="radio" class="pw-toggle-customize" name="pw-customize-sidebars" value="no" <?php checked($customize, 'no') ?> /> <?php _e('Default', 'wp-page-widgets');?> (<?php _e('follow', 'wp-page-widgets'); ?> <a href="<?php echo admin_url('widgets.php') ?>"><?php _e('Widgets settings', 'wp-page-widgets');?></a>)
					&nbsp;&nbsp;&nbsp;<input class="pw-toggle-customize" type="radio" name="pw-customize-sidebars" value="yes" <?php checked($customize, 'yes') ?> /> <?php _e('Customize', 'wp-page-widgets');?>
					<br class="clear" />
				</div>

				<form style="display: none;" action="" method="post"></form>

				<div id="pw-sidebars-customize">
					<input type="hidden" name="pw-sidebar-customize" value="0" />

					<div class="widget-liquid-left">
						<div id="widgets-left">
							<div id="available-widgets" class="widgets-holder-wrap">
								<div class="sidebar-name">
									<div class="sidebar-name-arrow"><br /></div>
									<h3><?php _e('Available Widgets'); ?> <span id="removing-widget"><?php _e('Deactivate'); ?> <span></span></span></h3>
								</div>
								<div class="widget-holder">
									<p class="description"><?php _e('Drag widgets from here to a sidebar on the right to activate them. Drag widgets back here to deactivate them and delete their settings.'); ?></p>
									<div id="widget-list">
										<?php wp_list_widgets(); ?>
									</div>
									<br class='clear' />
								</div>
								<br class="clear" />
							</div>

							<div class="widgets-holder-wrap">
								<div class="sidebar-name">
									<div class="sidebar-name-arrow"><br /></div>
									<h3><?php _e('Inactive Widgets'); ?>
                  	<?php
                    if ( version_compare( get_bloginfo('version'), '3.8', '<' ) ) {
										?>
											<span><img src="<?php echo esc_url(admin_url('images/wpspin_light.gif')); ?>" class="ajax-feedback" title="" alt="" /></span>
										<?php
										} else {
										?>
											<span class="spinner"></span>
										<?php
										}
										?>
									</h3>
								</div>
								<div class="widget-holder inactive">
									<p class="description"><?php _e('Drag widgets here to remove them from the sidebar but keep their settings.'); ?></p>
									<?php wp_list_widget_controls('wp_inactive_widgets'); ?>
									<br class="clear" />
								</div>
							</div>
						</div>
					</div>

					<div class="widget-liquid-right">
						<div id="widgets-right">
							<?php
							$i = 0;
							foreach ($wp_registered_sidebars as $sidebar => $registered_sidebar) {
								if ('wp_inactive_widgets' == $sidebar)
									continue;
								if (!in_array($sidebar, $settings['sidebars']))
									continue;
								$closed = $i ? ' closed' : '';
								?>
								<div class="widgets-holder-wrap<?php echo $closed; ?>">
									<div class="sidebar-name">
										<div class="sidebar-name-arrow"><br /></div>
										<h3><?php echo esc_html($registered_sidebar['name']); ?>
											<?php
											if ( version_compare( get_bloginfo('version'), '3.8', '<' ) ) {
											?>
												<span><img src="<?php echo esc_url(admin_url('images/wpspin_light.gif')); ?>" class="ajax-feedback" title="" alt="" /></span>
											<?php
											} else {
											?>
												<span class="spinner"></span>
											<?php
											}
											?></h3>
									</div>
									<?php wp_list_widget_controls($sidebar); // Show the control forms for each of the widgets in this sidebar ?>
								</div>
								<?php
								$i++;
							}
							?>
						</div>
					</div>
					<form action="" method="post">
						<?php wp_nonce_field('save-sidebar-widgets', '_wpnonce_widgets', false); ?>
					</form>
					<br class="clear" />
					<input type="hidden" id="pw_search_page" value="yes" />
				</div><!-- End #pw-sidebars-customize -->
			</div>
		</div>
	</div>
	<?php
}

// pw_category_page() displays the category page setting on pw widget
function pw_front_page() {
	?>
	<div class="wrap">
		<h2><?php _e('Page widgets for Front page - only latest posts option', 'wp-page-widgets'); ?></h2>
		<div class="liquid-left">
			<div class="panel-left">
				<form action="" method="post">
					<table class="form-table">
						<tr>
							<th><?php _e('Enable for drag edit Category', 'wp-page-widgets'); ?></th>
							<td>
								<input type="checkbox" class="tag-checked" />
							</td>
						</tr>
					</table>
					<p class="submit">
						<input type="submit" class="button-primary" name="save-changes" value="<?php _e('Save Changes', 'wp-page-widgets'); ?>" />
					</p>
				</form>
			</div>
		</div>
	</div>
	<?php
}

// Page widgets get settings
function pw_get_settings() {
	$defaults = array(
		'donation' => 'no',
		'customize_by_default' => 'no',
		'post_types' => array('post', 'page'),
		'sidebars' => array(),
	);
	//get list taxonomies registered in system
	$defaults['taxonomies'] = array();
	$taxonomies = get_taxonomies(array('show_ui' => true));

	foreach ($taxonomies as $taxonomy) {
		$defaults['taxonomies'][] = $taxonomy;
	}

	$settings = get_option('pw_options', array());
	return wp_parse_args($settings, $defaults);

}

function pw_metabox_content($post) {
	global $wp_registered_sidebars, $sidebars_widgets, $wp_registered_widgets;

	$settings = pw_get_settings();

	// register the inactive_widgets area as sidebar
	register_sidebar(array(
		'name' => __('Inactive Widgets'),
		'id' => 'wp_inactive_widgets',
		'description' => '',
		'before_widget' => '',
		'after_widget' => '',
		'before_title' => '',
		'after_title' => '',
	));

	$sidebars_widgets = wp_get_sidebars_widgets();
	if (empty($sidebars_widgets))
		$sidebars_widgets = wp_get_widget_defaults();


	$customize = get_post_meta($post->ID, '_customize_sidebars', true);
	if (!$customize) {
		if ($settings['customize_by_default'] == "yes") {
			$customize = 'yes';
		} else {
			$customize = 'no';
		}
	}

	// include widgets function
	if (!function_exists('wp_list_widgets'))
		require_once(ABSPATH . '/wp-admin/includes/widgets.php');
	?>

	<div style="padding: 5px;">
		<?php
		if ($settings['donation'] != 'yes') {
			echo '<div id="donation-message"><p>'.__('Thank you for using this plugin. If you appreciate our works, please consider to', 'wp-page-widgets').' <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=X2CJ88BHMLAT6">'.__('donate us', 'wp-page-widgets').'</a>. '.__('With your help, we can continue supporting and developing this plugin.', 'wp-page-widgets').'<br /><a href="' . admin_url('options-general.php?page=pw-settings') . '"><small>'.__('Hide this donation message', 'wp-page-widgets').'</small></a>.</p></div>';
		}
		?>
	</div>

	<div style="padding: 5px;">
	<!--	<a id="pw-button-customize" class="<?php //echo $pw_class ?>" href="#"><span class="customize">Customize</span><span class="default">Default</span></a>-->
		<input type="radio" class="pw-toggle-customize" name="pw-customize-sidebars" value="no" <?php checked($customize, 'no') ?> /> <?php _e('Default', 'wp-page-widgets'); ?> (<?php _e('follow', 'wp-page-widgets');?> <a href="<?php echo admin_url('widgets.php') ?>"><?php _e('Widgets settings', 'wp-page-widgets'); ?></a>)
		&nbsp;&nbsp;&nbsp;<input class="pw-toggle-customize" type="radio" name="pw-customize-sidebars" value="yes" <?php checked($customize, 'yes') ?> /> <?php _e('Customize', 'wp-page-widgets'); ?>
		<br class="clear" />
	</div>

	<form style="display: none;" action="" method="post"></form>

	<div id="pw-sidebars-customize">
		<input type="hidden" name="pw-sidebar-customize" value="0" />

		<div class="widget-liquid-left">
			<div id="widgets-left">
				<div id="available-widgets" class="widgets-holder-wrap">
					<div class="sidebar-name">
						<div class="sidebar-name-arrow"><br /></div>
						<h3><?php _e('Available Widgets'); ?> <span id="removing-widget"><?php _e('Deactivate'); ?> <span></span></span></h3>
					</div>
					<div class="widget-holder">
						<p class="description"><?php _e('Drag widgets from here to a sidebar on the right to activate them. Drag widgets back here to deactivate them and delete their settings.'); ?></p>
						<div id="widget-list">
							<?php wp_list_widgets(); ?>
						</div>
						<br class='clear' />
					</div>
					<br class="clear" />
				</div>

				<div class="widgets-holder-wrap">
					<div class="sidebar-name">
						<div class="sidebar-name-arrow"><br /></div>
						<h3><?php _e('Inactive Widgets'); ?>
							<?php
							if ( version_compare( get_bloginfo('version'), '3.8', '<' ) ) {
							?>
								<span><img src="<?php echo esc_url(admin_url('images/wpspin_light.gif')); ?>" class="ajax-feedback" title="" alt="" /></span>
							<?php
							} else {
							?>
								<span class="spinner"></span>
							<?php
							}
							?></h3>
					</div>
					<div class="widget-holder inactive">
						<p class="description"><?php _e('Drag widgets here to remove them from the sidebar but keep their settings.'); ?></p>
						<?php wp_list_widget_controls('wp_inactive_widgets'); ?>
						<br class="clear" />
					</div>
				</div>
			</div>
		</div>

		<div class="widget-liquid-right">
			<div id="widgets-right">
				<?php
				$i = 0;
				foreach ($wp_registered_sidebars as $sidebar => $registered_sidebar) {
					if ('wp_inactive_widgets' == $sidebar)
						continue;
					if (!in_array($sidebar, $settings['sidebars']))
						continue;
					$closed = $i ? ' closed' : '';
					?>
					<div class="widgets-holder-wrap<?php echo $closed; ?>">
						<div class="sidebar-name">
							<div class="sidebar-name-arrow"><br /></div>
							<h3><?php echo esc_html($registered_sidebar['name']); ?>
								<?php
								if ( version_compare( get_bloginfo('version'), '3.8', '<' ) ) {
								?>
									<span><img src="<?php echo esc_url(admin_url('images/wpspin_light.gif')); ?>" class="ajax-feedback" title="" alt="" /></span>
								<?php
								} else {
								?>
									<span class="spinner"></span>
								<?php
								}
								?>
              </h3>
						</div>
						<?php wp_list_widget_controls($sidebar); // Show the control forms for each of the widgets in this sidebar  ?>
					</div>
					<?php
					$i++;
				}
				?>
			</div>
		</div>

		<form action="" method="post">
			<?php wp_nonce_field('save-sidebar-widgets', '_wpnonce_widgets', false); ?>
		</form>
		<br class="clear" />

	</div><!-- End #pw-sidebars-customize -->
	<div class="widgets-chooser">
		<ul class="widgets-chooser-sidebars"></ul>
		<div class="widgets-chooser-actions">
			<button class="button-secondary"><?php _e( 'Cancel' ); ?></button>
			<button class="button-primary"><?php _e( 'Add Widget' ); ?></button>
		</div>
	</div>
	<?php
}

function pw_showTaxonomyWidget($tag, $taxonomy) {
	$taxonomyId = $tag->term_id;
	$taxonomyMetaData = getTaxonomyMetaData($taxonomy, $taxonomyId);

	global $wp_registered_sidebars, $sidebars_widgets, $wp_registered_widgets;

	$settings = pw_get_settings();

	// register the inactive_widgets area as sidebar
	register_sidebar(array(
		'name' => __('Inactive Widgets'),
		'id' => 'wp_inactive_widgets',
		'description' => '',
		'before_widget' => '',
		'after_widget' => '',
		'before_title' => '',
		'after_title' => '',
	));

	$sidebars_widgets = wp_get_sidebars_widgets();
	if (empty($sidebars_widgets))
		$sidebars_widgets = wp_get_widget_defaults();


	$customize = isset($taxonomyMetaData['_customize_sidebars']) ? $taxonomyMetaData['_customize_sidebars'] : "no";

	// include widgets function
	if (!function_exists('wp_list_widgets'))
		require_once(ABSPATH . '/wp-admin/includes/widgets.php');
	?>
	<div class="postbox " id="pw-widgets">
		<div title="Click to toggle" class="handlediv"><br></div>
		<h3 class="hndle"><span><?php echo ucwords(str_replace("_", " ", $taxonomy)) ?> Widgets</span></h3>
		<div class="inside">
			<div style="padding: 5px;">
				<?php
				if ($settings['donation'] != 'yes') {
					echo '<div id="donation-message"><p>'.__('Thank you for using this plugin. If you appreciate our works, please consider to', 'wp-page-widgets').' <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=X2CJ88BHMLAT6">'.__('donate us', 'wp-page-widgets').'</a>. '.__('With your help, we can continue supporting and developing this plugin.', 'wp-page-widgets').'<br /><a href="' . admin_url('options-general.php?page=pw-settings') . '"><small>'.__('Hide this donation message', 'wp-page-widgets').'</small></a>.</p></div>';
				}
				?>
			</div>

			<div style="padding: 5px;">
			<!--	<a id="pw-button-customize" class="<?php echo $pw_class ?>" href="#"><span class="customize">Customize</span><span class="default">Default</span></a>-->
				<input type="radio" class="pw-toggle-customize" name="pw-customize-sidebars" value="no" <?php checked($customize, 'no') ?> /> Default (follow <a href="<?php echo admin_url('widgets.php') ?>"><?php _e('Widgets settings', 'wp-page-widgets');?></a>)
				&nbsp;&nbsp;&nbsp;<input class="pw-toggle-customize" type="radio" name="pw-customize-sidebars" value="yes" <?php checked($customize, 'yes') ?> /> <?php _e('Customize', 'wp-page-widgets');?>
				<br class="clear" />
			</div>

			<form style="display: none;" action="" method="post"></form>

			<div id="pw-sidebars-customize">
				<input type="hidden" name="pw-sidebar-customize" value="0" />

				<div class="widget-liquid-left">
					<div id="widgets-left">
						<div id="available-widgets" class="widgets-holder-wrap">
							<div class="sidebar-name">
								<div class="sidebar-name-arrow"><br /></div>
								<h3><?php _e('Available Widgets'); ?> <span id="removing-widget"><?php _e('Deactivate'); ?> <span></span></span></h3>
							</div>
							<div class="widget-holder">
								<p class="description"><?php _e('Drag widgets from here to a sidebar on the right to activate them. Drag widgets back here to deactivate them and delete their settings.'); ?></p>
								<div id="widget-list">
									<?php wp_list_widgets(); ?>
								</div>
								<br class='clear' />
							</div>
							<br class="clear" />
						</div>

						<div class="widgets-holder-wrap">
							<div class="sidebar-name">
								<div class="sidebar-name-arrow"><br /></div>
								<h3><?php _e('Inactive Widgets'); ?>
									<?php
											if ( version_compare( get_bloginfo('version'), '3.8', '<' ) ) {
											?>
												<span><img src="<?php echo esc_url(admin_url('images/wpspin_light.gif')); ?>" class="ajax-feedback" title="" alt="" /></span>
											<?php
											} else {
											?>
												<span class="spinner"></span>
											<?php
											}
											?></h3>
							</div>
							<div class="widget-holder inactive">
								<p class="description"><?php _e('Drag widgets here to remove them from the sidebar but keep their settings.'); ?></p>
								<?php wp_list_widget_controls('wp_inactive_widgets'); ?>
								<br class="clear" />
							</div>
						</div>
					</div>
				</div>

				<div class="widget-liquid-right">
					<div id="widgets-right">
						<?php
						$i = 0;
						foreach ($wp_registered_sidebars as $sidebar => $registered_sidebar) {
							if ('wp_inactive_widgets' == $sidebar)
								continue;
							if (!in_array($sidebar, $settings['sidebars']))
								continue;
							$closed = $i ? ' closed' : '';
							?>
							<div class="widgets-holder-wrap<?php echo $closed; ?>">
								<div class="sidebar-name">
									<div class="sidebar-name-arrow"><br /></div>
									<h3><?php echo esc_html($registered_sidebar['name']); ?>
										<?php
											if ( version_compare( get_bloginfo('version'), '3.8', '<' ) ) {
											?>
												<span><img src="<?php echo esc_url(admin_url('images/wpspin_light.gif')); ?>" class="ajax-feedback" title="" alt="" /></span>
											<?php
											} else {
											?>
												<span class="spinner"></span>
											<?php
											}
											?></h3>
								</div>
								<?php wp_list_widget_controls($sidebar); // Show the control forms for each of the widgets in this sidebar  ?>
							</div>
							<?php
							$i++;
						}
						?>
					</div>
				</div>

				<form action="" method="post">
					<?php wp_nonce_field('save-sidebar-widgets', '_wpnonce_widgets', false); ?>
				</form>
				<br class="clear" />
				<input type="hidden" id="tag_ID" value="<?php echo $tag->term_id ?>" />
				<input type="hidden" id="taxonomy" name="taxonomyEdited" value="<?php echo $taxonomy ?>" />
			</div><!-- End #pw-sidebars-customize -->
		</div>
	</div>
	<?php
}

/*function pw_returnTaxonomyWidget() {
	$taxonomy = $_POST['taxonomy'];

	//avoid XSS
	$taxonomy = strip_tags($taxonomy);
	$taxonomy = htmlentities($taxonomy, ENT_QUOTES);

	global $wp_registered_sidebars, $sidebars_widgets, $wp_registered_widgets;

	$settings = pw_get_settings();

	// register the inactive_widgets area as sidebar
	register_sidebar(array(
		'name' => __('Inactive Widgets'),
		'id' => 'wp_inactive_widgets',
		'description' => '',
		'before_widget' => '',
		'after_widget' => '',
		'before_title' => '',
		'after_title' => '',
	));

	$sidebars_widgets = wp_get_sidebars_widgets();
	if (empty($sidebars_widgets))
		$sidebars_widgets = wp_get_widget_defaults();


	$customize = "no";

	// include widgets function
	if (!function_exists('wp_list_widgets'))
		require_once(ABSPATH . '/wp-admin/includes/widgets.php');
	ob_start();
	?>
	<div class="postbox " id="pw-widgets">
		<div title="Click to toggle" class="handlediv"><br></div>
		<h3 class="hndle"><span><?php echo ucwords(str_replace("_", " ", $taxonomy)) ?> <?php _e('Widgets', 'wp-page-widgets');?></span></h3>
		<div class="inside">
			<div style="padding: 5px;">
				<?php
				if ($settings['donation'] != 'yes') {
					echo '<div id="donation-message"><p>'.__('Thank you for using this plugin. If you appreciate our works, please consider to', 'wp-page-widgets').' <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=X2CJ88BHMLAT6">'.__('donate us', 'wp-page-widgets').'</a>. '.__('With your help, we can continue supporting and developing this plugin.', 'wp-page-widgets').'<br /><a href="' . admin_url('options-general.php?page=pw-settings') . '"><small>'.__('Hide this donation message', 'wp-page-widgets').'</small></a>.</p></div>';
				}
				?>
			</div>

			<div style="padding: 5px;">
			<!--	<a id="pw-button-customize" class="<?php echo $pw_class ?>" href="#"><span class="customize">Customize</span><span class="default">Default</span></a>-->
				<input type="radio" class="pw-toggle-customize" name="pw-customize-sidebars" value="no" <?php checked($customize, 'no') ?> /> Default (follow <a href="<?php echo admin_url('widgets.php') ?>"><?php _e('Widgets settings', 'wp-page-widgets'); ?></a>)
				&nbsp;&nbsp;&nbsp;<input class="pw-toggle-customize" type="radio" name="pw-customize-sidebars" value="yes" <?php checked($customize, 'yes') ?> /> <?php _e('Customize', 'wp-page-widgets'); ?>
				<br class="clear" />
			</div>

			<form style="display: none;" action="" method="post"></form>

			<div id="pw-sidebars-customize">
				<input type="hidden" name="pw-sidebar-customize" value="0" />

				<div class="widget-liquid-left">
					<div id="widgets-left">
						<div id="available-widgets" class="widgets-holder-wrap">
							<div class="sidebar-name">
								<div class="sidebar-name-arrow"><br /></div>
								<h3><?php _e('Available Widgets'); ?> <span id="removing-widget"><?php _e('Deactivate'); ?> <span></span></span></h3>
							</div>
							<div class="widget-holder">
								<p class="description"><?php _e('Drag widgets from here to a sidebar on the right to activate them. Drag widgets back here to deactivate them and delete their settings.'); ?></p>
								<div id="widget-list">
									<?php wp_list_widgets(); ?>
								</div>
								<br class='clear' />
							</div>
							<br class="clear" />
						</div>

						<div class="widgets-holder-wrap">
							<div class="sidebar-name">
								<div class="sidebar-name-arrow"><br /></div>
								<h3><?php _e('Inactive Widgets'); ?>
									<?php
									if ( version_compare( get_bloginfo('version'), '3.8', '<' ) ) {
									?>
										<span><img src="<?php echo esc_url(admin_url('images/wpspin_light.gif')); ?>" class="ajax-feedback" title="" alt="" /></span>
									<?php
									} else {
									?>
										<span class="spinner"></span>
									<?php
									}
									?></h3>
							</div>
							<div class="widget-holder inactive">
								<p class="description"><?php _e('Drag widgets here to remove them from the sidebar but keep their settings.'); ?></p>
								<?php wp_list_widget_controls('wp_inactive_widgets'); ?>
								<br class="clear" />
							</div>
						</div>
					</div>
				</div>

				<div class="widget-liquid-right">
					<div id="widgets-right">
						<?php
						$i = 0;
						foreach ($wp_registered_sidebars as $sidebar => $registered_sidebar) {
							if ('wp_inactive_widgets' == $sidebar)
								continue;
							if (!in_array($sidebar, $settings['sidebars']))
								continue;
							$closed = $i ? ' closed' : '';
							?>
							<div class="widgets-holder-wrap<?php echo $closed; ?>">
								<div class="sidebar-name">
									<div class="sidebar-name-arrow"><br /></div>
									<h3><?php echo esc_html($registered_sidebar['name']); ?>
										<?php
										if ( version_compare( get_bloginfo('version'), '3.8', '<' ) ) {
										?>
											<span><img src="<?php echo esc_url(admin_url('images/wpspin_light.gif')); ?>" class="ajax-feedback" title="" alt="" /></span>
										<?php
										} else {
										?>
											<span class="spinner"></span>
										<?php
										}
										?></h3>
								</div>
								<?php wp_list_widget_controls($sidebar); // Show the control forms for each of the widgets in this sidebar  ?>
							</div>
							<?php
							$i++;
						}
						?>
					</div>
				</div>

				<form action="" method="post">
					<?php wp_nonce_field('save-sidebar-widgets', '_wpnonce_widgets', false); ?>
				</form>
				<br class="clear" />
				<input type="hidden" id="taxonomy" name="taxonomyEdited" value="<?php echo $taxonomy ?>" />
			</div><!-- End #pw-sidebars-customize -->
		</div>
	</div>
	<?php
	$content = ob_get_clean();
	echo $content;
	exit;
}*/

function pw_ajax_remove_inactive_widget(){
	check_ajax_referer('save-sidebar-widgets', 'savewidgets');

	if (!current_user_can('edit_posts'))
		die('-1');

	if (!$_POST['post_id'])
		die('-1');

	if (isset($_POST['post_id']))
		$post_id = stripslashes($_POST['post_id']);

	$widgets = get_option('sidebars_widgets');
	$widgets['wp_inactive_widgets'] = array();
	update_option('sidebars_widgets', $widgets);

	$_sidebars_widgets = get_post_meta($post_id, '_sidebars_widgets', true);
	$_sidebars_widgets['wp_inactive_widgets'] = array();
	update_post_meta($post_id, '_sidebars_widgets', $_sidebars_widgets);
	echo "ok";exit;
}

function pw_ajax_toggle_customize() {
	$status = stripslashes($_POST['pw-customize-sidebars']);
	$post_id = (int) $_POST['post_id'];

	$search_page = $_POST['search_page'];

	$tag_id = (int) $_POST['tag_id'];
	$taxonomy = $_POST['taxonomy'];

	if (!in_array($status, array('yes', 'no')))
		$status = 'no';

	if (!empty($post_id)) {
		$post_type = get_post_type($post_id);
		$post_type_object = get_post_type_object($post_type);

		if (current_user_can($post_type_object->cap->edit_posts)) {
			update_post_meta($post_id, '_customize_sidebars', $status);
			echo 1;
		}
	}

	// For search page
	else if (!empty($search_page)) {
		update_option('_pw_search_page', $status);
		_e('Updated search page option.', 'wp-page-widgets');
	}

	// For taxonomy page
	else {
		$objTaxonomy = get_taxonomy($taxonomy);
		if (current_user_can($objTaxonomy->cap->edit_terms)) {
			$taxonomyMetaData = getTaxonomyMetaData($taxonomy, $tag_id);
			$taxonomyMetaData['_customize_sidebars'] = $status;
			updateTaxonomiesMetaData($taxonomy, $tag_id, $taxonomyMetaData);
			echo 1;
		}
	}

	exit(0);
}

function pw_save_post($post_id, $post) {
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
		return $post_id;
	//print_r($_POST);
	if (isset($_POST['pw-customize-sidebars'])) {
		$status = stripslashes($_POST['pw-customize-sidebars']);

		if (!in_array($status, array('yes', 'no')))
			$status = 'no';

		$post_type = get_post_type($post);
		$post_type_object = get_post_type_object($post_type);

		if (current_user_can($post_type_object->cap->edit_posts)) {
			update_post_meta($post_id, '_customize_sidebars', $status);
		}
	}
	if ( function_exists( 'w3tc_pgcache_flush' ) ) {
		w3tc_pgcache_flush();
	}
	return $post_id;
}

function pw_save_term($term_id, $tt_id) {
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
		return $term_id;
	if (isset($_POST['pw-customize-sidebars'])) {
		$taxonomyEdited = $_POST['taxonomyEdited'];
		$status = stripslashes($_POST['pw-customize-sidebars']);

		if (!in_array($status, array('yes', 'no')))
			$status = 'no';

		$objTaxonomy = get_taxonomy($taxonomyEdited);
		if (isset($edit_term)) {
			if (current_user_can($objTaxonomy->cap->edit_term)) {
				$taxonomyData = getTaxonomyMetaData($taxonomy, $term_id);
				$taxonomyData['_customize_sidebars'] = $status;
				updateTaxonomiesMetaData($taxonomy, $term_id, $taxonomyData);
			}
		}
	}
	return $term_id;
}

function pw_ajax_widgets_order() {
	check_ajax_referer('save-sidebar-widgets', 'savewidgets');

	if (!current_user_can('edit_posts')) {
		_e('This user is not have access to edit theme options', 'wp-page-widgets');
		die('-1');
	}

	if (!$_POST['post_id'] && !$_POST['tag_id'] && !$_POST['search_page']) {
		_e('Not post, taxonomy or search page.', 'wp-page-widgets');
		die('-1');
	}

	$post_id = stripslashes($_POST['post_id']);
	$tag_id = stripslashes($_POST['tag_id']);
	$taxonomy = stripslashes($_POST['taxonomy']);
	var_dump($taxonomy);

	$search_page = stripslashes($_POST['search_page']);

	unset($_POST['savewidgets'], $_POST['action']);

	// save widgets order for all sidebars
	if (is_array($_POST['sidebars'])) {
		$sidebars = array();
		foreach ($_POST['sidebars'] as $key => $val) {
			$sb = array();
			if (!empty($val)) {
				$val = explode(',', $val);
				foreach ($val as $k => $v) {
					if (strpos($v, 'widget-') === false)
						continue;

					$sb[$k] = substr($v, strpos($v, '_') + 1);
				}
			}
			$sidebars[$key] = $sb;
		}
		if (!empty($post_id)) {
			pw_set_sidebars_widgets($sidebars, $post_id);
		} else if (!empty($search_page)) {
			pw_set_sidebars_widgets($sidebars, NULL, NULL, 'search_page');
		} else {
			pw_set_sidebars_widgets($sidebars, $tag_id, $taxonomy);
		}
		print 'Saved ajax widgets order<br />';
		die('1');
	}
	_e('Not save ajax widgets order', 'wp-page-widgets');
	die('-1');
}

function pw_ajax_save_widget() {

	global $wp_registered_widget_controls, $wp_registered_widgets, $wp_registered_widget_updates;

	check_ajax_referer('save-sidebar-widgets', 'savewidgets');

	if (!current_user_can('edit_posts') || !isset($_POST['id_base']))
		die('-1');

	if (!$_POST['post_id'] && !$_POST['tag_id'] && !$_POST['search_page'])
		die('-1');

	if (isset($_POST['post_id']))
		$post_id = stripslashes($_POST['post_id']);
	if (isset($_POST['tag_id']))
		$tag_id = stripslashes($_POST['tag_id']);
	if (isset($_POST['taxonomy']))
		$taxonomy = stripslashes($_POST['taxonomy']);

	// For search page
	if (isset($_POST['search_page']))
		$search_page = stripslashes($_POST['search_page']);

	unset($_POST['savewidgets'], $_POST['action']);

	do_action('load-widgets.php');
	do_action('widgets.php');
	do_action('sidebar_admin_setup');
	do_action('w3tc_pgcache_flush');

	$id_base = $_POST['id_base'];
	$widget_id = $_POST['widget-id'];
	$sidebar_id = $_POST['sidebar'];
	$multi_number = !empty($_POST['multi_number']) ? (int) $_POST['multi_number'] : 0;
	$settings = isset($_POST['widget-' . $id_base]) && is_array($_POST['widget-' . $id_base]) ? $_POST['widget-' . $id_base] : false;
	$error = '<p>' . __('An error has occured. Please reload the page and try again.') . '</p>';

	$sidebars = wp_get_sidebars_widgets();
	$sidebar = isset($sidebars[$sidebar_id]) ? $sidebars[$sidebar_id] : array();

	// delete
	if (isset($_POST['delete_widget']) && $_POST['delete_widget']) {

		if (!isset($wp_registered_widgets[$widget_id]))
			die($error);

		$sidebar = array_diff($sidebar, array($widget_id));
		$_POST = array('sidebar' => $sidebar_id, 'widget-' . $id_base => array(), 'the-widget-id' => $widget_id, 'delete_widget' => '1');
	} elseif ($settings && preg_match('/__i__|%i%/', key($settings))) {
		if (!$multi_number)
			die($error);

		$_POST['widget-' . $id_base] = array($multi_number => array_shift($settings));
		$widget_id = $id_base . '-' . $multi_number;
		$sidebar[] = $widget_id;
	}
	$_POST['widget-id'] = $sidebar;


	// Save widgets
	//var_dump(isset($_POST));
	if (!isset($_POST['delete_widget']) || !$_POST['delete_widget']) {
	//if (!isset($_POST['delete_widget']) && !$_POST['delete_widget']) {
		foreach ((array) $wp_registered_widget_updates as $name => $control) {

			if ($name == $id_base) {

				if (!is_callable($control['callback']))
					continue;
				// do some hack
				$number = $multi_number > 0 ? $multi_number : (int) $_POST['widget_number'];
				if (is_object($control['callback'][0])) {
					$all_instance = $control['callback'][0]->get_settings();
				}



				if (!isset($all_instance[$number])) { // that's mean new widget was added. => call update function to add widget (globally).
					ob_start();
					call_user_func_array($control['callback'], $control['params']);
					ob_end_clean();
				} else { // mean existing widget was saved. => save separate settings for each post (avoid to overwrite global existing widget data.
					$widget_obj = &$control['callback'][0];
					if (!empty($post_id)) {

						$widget_obj->option_name = 'widget_' . $post_id . '_' . $widget_obj->id_base;
					} else if (!empty($search_page)) {
						$widget_obj->option_name = 'widget_search_' . $widget_obj->id_base;
					} else {
						$optionName = $taxonomy . '_widget_' . $tag_id . '_' . $widget_obj->id_base;
						$widget_obj->option_name = $optionName;
					}

					ob_start();
					call_user_func_array($control['callback'], $control['params']);
					ob_end_clean();
				}
				break;
			}
		}
	}

	if (isset($_POST['delete_widget']) && $_POST['delete_widget']) {
		$sidebars[$sidebar_id] = $sidebar;
		if (!empty($post_id)) {
			pw_set_sidebars_widgets($sidebars, $post_id);
		} else if (!empty($search_page)) {
			pw_set_sidebars_widgets($sidebars, NULL, NULL, 'search_page');
		} else {
			pw_set_sidebars_widgets($sidebars, $tag_id, $taxonomy);
		}
		// echo "deleted:$widget_id";
		die();
	}

	if (!empty($_POST['add_new']))
		die();

	if ($form = $wp_registered_widget_controls[$widget_id])
		call_user_func_array($form['callback'], $form['params']);
		print 'Updated ajax save widget.';
		if ( function_exists( 'w3tc_pgcache_flush' ) ) {
			w3tc_pgcache_flush();
		}
	die();
}

function pw_set_sidebars_widgets($sidebars_widgets, $post_id, $taxonomy = "", $search_page = NULL) {
	if (!isset($sidebars_widgets['array_version']))
		$sidebars_widgets['array_version'] = 3;

	// Search page
	if ($search_page == 'search_page') {
		update_option('_search_page_sidebars_widgets', $sidebars_widgets);
	}

	// For post page
	elseif (empty($taxonomy)) {
		update_post_meta($post_id, '_sidebars_widgets', $sidebars_widgets);
	}

	// Taxonomy page
	else {
		$taxonomyData = getTaxonomyMetaData($taxonomy, $post_id);
		$taxonomyData['_sidebars_widgets'] = $sidebars_widgets;
		updateTaxonomiesMetaData($taxonomy, $post_id, $taxonomyData);
	}
}

function pw_filter_widgets($sidebars_widgets) {
	global $post, $pagenow;

	$objTaxonomy = getTaxonomyAccess();

	if (
			( is_admin()
			&& !in_array($pagenow, array('post-new.php', 'post.php', 'edit-tags.php', 'term.php'))
			&& (!in_array($pagenow, array('admin.php')) && (isset($_GET['page']) && ($_GET['page'] == 'pw-front-page') || isset($_GET['page']) && $_GET['page'] == 'pw-search-page'))
			)
			|| (!is_admin() && !is_singular() && !is_search() && empty($objTaxonomy['taxonomy']))
	) {

		return $sidebars_widgets;
	}


	// Search page
	if (is_search() || (is_admin() && (isset($_GET['page']) && $_GET['page'] == 'pw-search-page'))) {
		$enable_customize = get_option('_pw_search_page', true);
		$_sidebars_widgets = get_option('_search_page_sidebars_widgets', true);
	}


	// Post page
	elseif (empty($objTaxonomy['taxonomy'])) {
		//if admin alway use query string post = ID
		//Fix conflic when other plugins use query post after load editing post!

		if ( is_object($post) && isset($_GET['post']) ) {
			$postID = $_GET['post'];
		}
		if (is_admin() && isset($postID)) {
			if ( !is_object($post) ) $post = new stdClass();
				$post->ID = $postID;
		}
		if (isset($post->ID)) {
		$enable_customize = get_post_meta($post->ID, '_customize_sidebars', true);
		$_sidebars_widgets = get_post_meta($post->ID, '_sidebars_widgets', true); }
	}

	// Taxonomy page
	else {

		$taxonomyMetaData = getTaxonomyMetaData($objTaxonomy['taxonomy'], $objTaxonomy['term_id']);
		$enable_customize = $taxonomyMetaData['_customize_sidebars'];
		$_sidebars_widgets = $taxonomyMetaData['_sidebars_widgets'];
	}

	if (isset($enable_customize) && $enable_customize == 'yes' && !empty($_sidebars_widgets)) {
		if (is_array($_sidebars_widgets) && isset($_sidebars_widgets['array_version']))
			unset($_sidebars_widgets['array_version']);

		$sidebars_widgets = wp_parse_args($_sidebars_widgets, $sidebars_widgets);
	}
	return $sidebars_widgets;
}

function pw_filter_widget_display_instance($instance, $widget, $args) {
	$widget_instance = getWidgetOptionFE($widget->id_base);
	if ($widget_instance && isset($widget_instance[$widget->number]))
		$instance = $widget_instance[$widget->number];
	//echo "<pre>"; print_r($widget_instance); echo "</pre>";
	return $instance;
}

function pw_filter_widget_form_instance($instance, $widget) {
	global $post, $pagenow;

	//print 'Search'; exit();
	$objTaxonomy = getTaxonomyAccess();

	//$isTaxonomyEdit = $pagenow == "edit-tags.php";
	$isTaxonomyEdit = $pagenow == "term.php";

	//$enable_customize = get_post_meta($post->ID, '_customize_sidebars', true);

	if (
			(is_admin() && in_array($pagenow, array('post-new.php', 'post.php', "term.php" )))
			||
			( is_admin() && in_array($pagenow, array('admin.php')) && (($_GET['page'] == 'pw-front-page') || ($_GET['page'] == 'pw-search-page')) )
	) {

		// Search page
		if (in_array($pagenow, array('admin.php')) && (($_GET['page'] == 'pw-front-page') || ($_GET['page'] == 'pw-search-page'))) {
			$widget_instance = get_option('widget_search_' . $widget->id_base);

			/*
			  print '<pre>';
			  var_dump($widget_instance);
			  print '</pre>';
			  exit();	// */

		} elseif (!$isTaxonomyEdit) {
			$widget_instance = get_option('widget_' . $post->ID . '_' . $widget->id_base);
		} elseif (!empty($objTaxonomy['taxonomy'])) {
			$widget_instance = get_option($objTaxonomy['taxonomy'] . '_widget_' . $objTaxonomy['term_id'] . '_' . $widget->id_base);
		}

		if ($widget_instance && isset($widget_instance[$widget->number])) {

			$instance = $widget_instance[$widget->number];
		}
	}
	return $instance;
}

function sidebar_params( $params ) {
	global $wp_registered_widgets;

	$widget_id	= $params[0]['widget_id'];
	$widget_obj	= $wp_registered_widgets[ $widget_id ];
	$widget_id_base = "";
	if(isset($widget_obj['callback']) && is_object($widget_obj['callback'][0]))
		$widget_id_base = $widget_obj['callback'][0]->id_base;
	elseif(isset($widget_obj['_callback']) && is_object($widget_obj['_callback'][0]))
		$widget_id_base = $widget_obj['_callback'][0]->id_base;
	$widget_num	= $widget_obj['params'][0]['number'];

	if(empty($widget_id_base))
		return $params;

	$widget_opt = getWidgetOptionFE($widget_id_base);

	if(!empty($widget_opt)){
		$widget_extra_classes = '';
		if ( isset( $widget_opt[ $widget_num ]['column-classes'] ) && ! empty( $widget_opt[ $widget_num ]['column-classes'] ) ) {
			$widget_extra_classes .= $widget_opt[ $widget_num ]['column-classes'].' ';
		}
		if ( isset( $widget_opt[ $widget_num ]['column-classes-first'] ) && 1 == $widget_opt[ $widget_num ]['column-classes-first'] ) {
			$widget_extra_classes .= 'first ';
		}

		// Remove class added by plugin genesis widget column classes
		$column_classes = array(
			'one-half',
			'one-third',
			'one-fourth',
			'one-sixth',
			'two-thirds',
			'two-fourths',
			'two-sixths',
			'three-fourths',
			'three-sixths',
			'four-sixths',
			'five-sixths'
		);
		foreach($column_classes as $class){
			$params[0]['before_widget'] = str_replace('class="' . $class, 'class="', $params[0]['before_widget']);
		}

		$params[0]['before_widget'] = preg_replace( '/class="/', 'class="'.$widget_extra_classes , $params[0]['before_widget'], 1 );
	}

	return $params;
}

function getWidgetOptionFE($widget_id_base){
	global $post;

	$widget_opt = array();
	$objTaxonomy = getTaxonomyAccess();
	if (is_search()) {
		$enable_customize = get_option('_pw_search_page', true);
		if ($enable_customize == 'yes') {
			$widget_instance = get_option('widget_search_' . $widget_id_base);

			/*
			  print '<pre>';
			  var_dump($widget->number);
			  var_dump($widget_instance);
			  var_dump($widget_instance[3]);
			  print '</pre>';
			  //exit();	//
			 *
			 */

			if ($widget_instance) {
				$widget_opt = $widget_instance;
			}
		}

	// Use custom widgets for taxonomy page.
	} elseif (!empty($objTaxonomy['taxonomy'])) {
		$taxonomy = $objTaxonomy['taxonomy'];
		$tax_id = $objTaxonomy['term_id'];

		$taxonomyMetaData = getTaxonomyMetaData($taxonomy, $tax_id);
		$enable_customize = $taxonomyMetaData['_customize_sidebars'];
		if ($enable_customize == 'yes') {
			$widget_instance = get_option($taxonomy . '_widget_' . $tax_id . '_' . $widget_id_base);
			if ($widget_instance) {
				$widget_opt = $widget_instance;
			}
		}

	} elseif (!empty($post->ID)) {
		$enable_customize = get_post_meta($post->ID, '_customize_sidebars', true);
		if ($enable_customize == 'yes' && is_singular()) {
			$widget_instance = get_option('widget_' . $post->ID . '_' . $widget_id_base);
			if ($widget_instance) {
				$widget_opt = $widget_instance;
			}
		}
	}

	return $widget_opt;
}

function getTaxonomyMetaData($taxonomy, $tag_id) {
	$taxonomiesMetaData = getTaxonomiesMetaData($taxonomy);
	$taxonomyData = isset($taxonomiesMetaData[$tag_id]) ? $taxonomiesMetaData[$tag_id] : array('_customize_sidebars' => 0, '_sidebars_widgets' => array());
	return $taxonomyData;
}

function getTaxonomiesMetaData($taxonomy) {
	$key = "_" . $taxonomy . "_meta_data";
	$taxonomiesMetaData = get_option($key);
	if (empty($taxonomiesMetaData) || !is_array($taxonomiesMetaData)) {
		$taxonomiesMetaData = array();
	}
	return $taxonomiesMetaData;
}

function updateTaxonomiesMetaData($taxonomy, $tax_id, $data) {
	$key = "_" . $taxonomy . "_meta_data";
	$taxonomiesMetaData = get_option($key);
	$taxonomiesMetaData[$tax_id] = $data;
	update_option($key, $taxonomiesMetaData);
}

function getTaxonomyAccess() {
	global $wp_query;
	$return = array(
		'term_id' => "",
		'taxonomy' => ""
	);

	if (!is_admin() && (is_tax() || is_tag() || is_category() ) ) { //&& isset($objRequested)) {
		$objRequested = $wp_query->queried_object;
		if (isset($objRequested) && is_object($objRequested)) {
			$return['term_id'] = isset($objRequested->term_id) ? $objRequested->term_id : "";
			$return['taxonomy'] = isset($objRequested->taxonomy) ? $objRequested->taxonomy : "";
		}
	} else {
		$link = $_SERVER['REQUEST_URI'];
		if (strpos($link, "tag_ID=") !== false && strpos($link, "taxonomy=") !== false) {
			$term_id = preg_replace("#(.*)tag_ID=([^&]+)(.*)#", "$2", $link);
			$taxonomy = preg_replace("#(.*)taxonomy=([^&]+)(.*)#", "$2", $link);

			$return['term_id'] = $term_id;
			$return['taxonomy'] = $taxonomy;
		}
	}

	return $return;
}

/*
Not need this action http://wordpress.org/support/topic/incompatibility-with-black-studio-tinymce-widget?replies=1
add_action('admin_init', 'pw_admin_head');

function pw_admin_head() {
	global $pagenow;
		if (
			in_array($pagenow, array('post-new.php', 'post.php'))
			||
			(($pagenow == 'edit-tags.php') && isset($_GET['action']) && $_GET['action'] == 'edit')
			||
			// Page widget config for front page, search page
			( in_array($pagenow, array('admin.php')) && (($_GET['page'] == 'pw-front-page') || ($_GET['page'] == 'pw-search-page')) )
	) {

		// Compatibility for Black Studio TinyMCE Widget plugin
		if (is_plugin_active('black-studio-tinymce-widget/black-studio-tinymce-widget.php')) {
			add_action( 'admin_head', 'black_studio_tinymce_load_tiny_mce');
			add_filter( 'tiny_mce_before_init', 'black_studio_tinymce_init_editor', 20);
			add_action( 'admin_print_scripts', 'black_studio_tinymce_scripts');
			add_action( 'admin_print_styles', 'black_studio_tinymce_styles');
			add_action( 'admin_print_footer_scripts', 'black_studio_tinymce_footer_scripts');
		}
	}
}
*/