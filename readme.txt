=== Anything Order ===
Contributors: pmwp
Tags: admin, custom, drag and drop, menu_order, order, page, post, rearrange, reorder, sort, taxonomy, term_order
Requires at least: 3.8
Tested up to: 3.9
Stable tag: 1.0.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Reorder any post types and taxonomies with drag and drop.


== Description ==

This plugin allows you to arrange any post types and taxonomies with simple drag and drop within the builtin list table on administration screen.

= Features =
* Support for any post types and taxonomies.
* Multiple selection is available.
* Capabilities aware. 'edit_others_posts' for post. 'manage_terms' for taxonomy.
* No additional column in builtin tables.
* No additional table in database.


== Installation ==

1. Upload 'anything-order' to the '/wp-content/plugins/' directory
1. Activate the plugin through the 'Plugins' menu in WordPress


== Frequently Asked Questions ==

= Q. I have a question =
A. Check out the [support forum on WordPress.org](http://wordpress.org/support/plugin/anything-order) or [repository on Github](https://github.com/pimpmysite/anything-order).

= Q. I don't want some post types to be sortable. =
A. Uncheck the "Order" option in "Show on screen" section on [Screen Options](http://codex.wordpress.org/Administration_Screens#Screen_Options) tab to disable sorting.

= Q. How do I reset the order? =
A. Click the "Reset" link next to "Order" option on [Screen Options](http://codex.wordpress.org/Administration_Screens#Screen_Options) tab.

= Q. How do I select multiple items? =
A. Ctrl(or Command on OS X)+Click toggle selection state of current item. Shift+Click select items between first selected item on the list and current item.

= Q. I want change item hierarchy with drag and drop. =
A. __Currently__ not supported.


== Screenshots ==

1. Enable/Disable arrangement with drag and drop on "Screen Options" tab.
1. You can select multiple items.
1. Dragging items.


== Changelog ==

= 1.0.3 =
* Fix - Terms disappear after installing.
* Tweak - Correct the wrong plugin name.

= 1.0.2 =
* Fix - PHP error on wp_get_object_terms() with option 'fields' => 'names', 'slugs', 'tt_ids'

= 1.0.1 =
* Tweak - Remove screenshots from the plugin's release zip files.
* Tweak - Various small code tweaks.

= 1.0.0 =
* Initial Release


== Upgrade Notice ==

The current version of Anything Order requires WordPress 3.8 or higher. If you use older version of WordPress, you need to upgrade WordPress first.