=== Custom Post Template ===
Contributors: simonwheatley
Donate link: http://www.simonwheatley.co.uk/wordpress/
Tags: post, template, theme
Requires at least: 3.4
Tested up to: 3.4
Stable tag: 1.5

Provides a drop-down to select different templates for posts from the post edit screen. The templates replace single.php for the specified post.

== Description ==

Provides a drop-down to select different templates for posts from the post edit screen. The templates are defined similarly to page templates, and will replace single.php for the specified post. This plugin will NOT switch the templates for the different posts in a listing page, e.g. a date or category archive, it will only affect the template used for single posts (i.e. you can choose a template which is not single.php).

Post templates, as far as this plugin is concerned, are configured similarly to [page templates](http://codex.wordpress.org/Pages#Creating_Your_Own_Page_Templates) in that they have a particular style of PHP comment at the top of them. Each post template must contain the following, or similar, at the top:
<code>
<?php
/*
Template Name Posts: Snarfer
*/
?>
</code>

First note: *Page* templates use "_Template Name:_", whereas *post* templates use "_Template Name Posts:_".

Second note: You *must* have the custom post template files in your theme in the same directory/folder as your index.php template file, *not* in a sub-directory/sub-folder.

== Developers ==

If you want to implement the custom post *templates* on a custom post *type*, you can use the `cpt_post_types` filter, here's an example below of adding the custom post template selector and metabox to the "Movie" and "Actor" custom post types. This code can be added to a plugin or to the `functions.php` file in your theme.

`
/**
 * Hooks the WP cpt_post_types filter 
 *
 * @param array $post_types An array of post type names that the templates be used by
 * @return array The array of post type names that the templates be used by
 **/
function my_cpt_post_types( $post_types ) {
	$post_types[] = 'movie';
	$post_types[] = 'actor';
	return $post_types;
}
add_filter( 'cpt_post_types', 'my_cpt_post_types' );
`

== Installation ==

The plugin is simple to install:

1. Download the plugin, it will arrive as a zip file
1. Unzip it
1. Upload `custom-post-template` directory to your WordPress Plugin directory
1. Go to the plugin management page and enable the plugin
1. Upload your post template files (see the Description for details on configuring these), and choose them through the new menu
1. Give yourself a pat on the back

== Upgrade Notice ==

= 1.5 =

This upgrade REQUIRES WordPress version 3.4 and WILL NOT WORK WITHOUT IT.

== Change Log ==

= v1.5 2012/06/14 =

This upgrade REQUIRES WordPress version 3.4 and WILL NOT WORK WITHOUT IT.

* Updated for compatibility with 3.4, takes advantage of the new WP_Theme class and methods and the get_file_data function.

= v1.4 2011/08/14 =

* Added a filter, `cpt_post_types`, so people can choose which post types this plugin shows the UI for
* Linked to WP.org, not my site, for documentation (quicker to load)

= v1.3 2010/06/17 =

Dear Non-English Custom Post Template Users,

This release includes the facility for Custom Post Template to be translated into languages other than English. Please [contact me](http://www.simonwheatley.co.uk/contact-me/) if you want to translate Custom Post Template into your language.

Sorry it took so long.

* ENHANCEMENT: Now works with child themes, hat-tip Kathy
* LOCALISATION: Now ready for localisation!

= v1.2 2010/04/28 =

* ENHANCEMENT: Now sporting a conditional `is_post_template` function/template tag which is functionally equivalent to the core WordPress [is_page_template](http://codex.wordpress.org/Function_Reference/is_page_template) conditional function/template tag
* ENHANCEMENT: If the theme uses the core WordPress (body_class)[http://codex.wordpress.org/Template_Tags/body_class] template tag, then you will have two new classes added: "post-template" and "post-template-my-post-template-php" (where your post template file is named "my-post-template.php").

= v1.1 2010/01/27 =

* IDIOTFIX: Managed to revert to an old version somehow, this version should fix that.

= v1 2010/01/15 (released 2010/01/26) =

* BUGFIX: Theme templates now come with a complete filepath, so no need to add WP_CONTENT_DIR constant to the beginning.
* ENHANCEMENT: Metabox now shows up on the side, under the publish box... where you'd expect.
* Plugin initially produced on behalf of [Words & Pictures](http://www.wordsandpics.co.uk/).

= v0.9b 2008/11/26 =

* Plugin first released

= v0.91b 2008/11/28 =

* BUGFIX: The plugin was breaking posts using the "default" template, this is now fixed. Apologies for the inconvenience.
* Tested up to WordPress 2.7-beta3-9922

= v0.91b 2008/11/28 =

* BUGFIX: The plugin was breaking posts using the "default" template, this is now fixed. Apologies for the inconvenience.
* Tested up to WordPress 2.7-beta3-9922* Tested up to WordPress 2.7-beta3-9922

= v0.92b 2008/12/04 =

* Minor code tweaks
* Blocked direct access to templates

== Frequently Asked Questions ==

= I get an error like this: <code>Parse error: syntax error, unexpected T_STRING, expecting T_OLD_FUNCTION or T_FUNCTION or T_VAR or '}' in /web/wp-content/plugins/custom-post-template/custom-post-templates.php</code> =

This is because your server is running PHP4. Please see "Other Notes > PHP4" for more information.