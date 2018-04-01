=== WP Cron Cleaner ===
Contributors: symptote
Donate Link: http://www.sigmaplugin.com/donation
Tags: plugin, plugins, plugin wordpress, wordpress, cron cleaner, clean cron, cron clean, cron, clean, clean-up, clean up, cleanup, cleaner, schedule, scheduler, schedule clean-up, cron job, clean scheduled tasks, delete, delete cron, delete tasks, view, view cron, view cron job, view scheduled tasks, orphan, orphan tasks, orphan cron, tasks, task, scheduled tasks
Requires at least: 3.1.0
Tested up to: 4.6
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

View the list of all your cron scheduled tasks, then clean what you want.

== Description ==

"WP Cron Cleaner" is a useful plugin to view the list of all your scheduled tasks. Indeed, your site may contain some orphan scheduled tasks that should be cleaned. "WP Cron Cleaner" will display all your tasks so you can identify those that should be cleaned. Moreover, you will be able to see what happens in your site behind the scenes.

= Main Features =
* Displays all cron jobs (scheduled tasks) names
* Displays frequency of each task
* Displays "next run" of each task
* Allows you choose what tasks to clean
* Supports multisite installation

= Pro Features =
* Detects orphan cron scheduled tasks
* Detects plugins cron scheduled tasks
* Detects themes cron scheduled tasks
* Detects WordPress cron scheduled tasks

= Multisite Support =
* Only the main site can view and clean tasks in the whole network. Other sites in the network cannot perform these tasks. We have opted for this philosophy because we are sure that only the super administrator can perform such actions.

== Installation ==

This section describes how to install the plugin and get it working.

= Single site installation =
* After extraction, upload the Plugin to your `/wp-content/plugins/` directory
* Go to "Dashboard" &raquo; "Plugins" and choose 'Activate'
* The plugin page can be accessed via "Dashboard" &raquo; "Tools" &raquo; "WP Cron Cleaner"

= Multisite installation =
* Login to your primary site and go to "My Sites" &raquo; "Network Admin" &raquo; "Plugins"
* Install the plugin as usual for multisite
* Network activate the plugin
* Only the main site can have access to the plugin

== Screenshots ==

1. View and clean scheduled tasks (Free version)
2. Detect orphan tasks, plugins tasks, themes tasks and WP tasks (Pro version)

== Changelog ==

= 1.0.0 =
* First release: Hello world!

== Frequently Asked Questions ==

= What does mean "scheduled tasks"? =
A scheduled task enables plugins to execute some actions at specified times, without having to manually execute code at that time. WordPress itself uses some scheduled tasks to perform some regular actions.

= What does mean "orphan scheduled tasks"? =
As you know, not all plugins care about the housekeeping of your site. Some scheduled tasks may not be removed even if the responsible plugins are deleted from your WordPress installation. These tasks are called "orphan scheduled tasks". Hence, deleting these unnecessary tasks may help in cleaning your site.

= Is it safe to clean a scheduled tasks? =
It should be noted that cleaning scheduled tasks is safe as long as you know what tasks to clean. If your are not sure, it is better to not clean any task.

= Is this plugin compatible with multisite? =
Yes, it is compatible with multisite. It should be noted that only the main site can view and clean tasks in the whole network. Other sites in the network cannot perform these tasks. We have opted for this philosophy because we are sure that only the super administrator can perform such actions. Your network is precious!

= Does this plugin cleans itself after the uninstall? =
We do clean-up of your WordPress site, it will be a shame if the plugin does not clean itself after an uninstall! Of course yes, the plugin cleans itself and removes any data used to store its settings once uninstalled.