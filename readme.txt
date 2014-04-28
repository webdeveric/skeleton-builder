=== Skeleton Builder ===
Contributors: webdeveric, timwickstrom
Donate link: http://webdeveric.com/donate/
Tags: disable, plugins, update, admin
Requires at least: 3.5.0
Tested up to: 3.9
Stable tag: 0.3.1

When you have a fresh install of WP and you're about to enter content, this plugin can save you a lot of time by automatically creating blank pages/posts/custom post type.
It can also create a nav menu based on your site structure so you don't have to manually create one later.

== Description ==

When you have a fresh install of WP and you're about to enter content, this plugin can save you a lot of time by automatically creating blank pages/posts/custom post type.
It can also create a nav menu based on your site structure so you don't have to manually create one later.

Features:

* Batch create blank pages/posts/whatever in your posts table.
* Batch create nav menus based on your skeleton.
* You can choose your post type when the batch runs.

== Installation ==

1. Upload `skeleton-builder` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Use it in `Tools > Skeleton Builder`

== Changelog ==

= 0.3 =
* Big overhaul - nearly complete rewrite. This plugin now uses my WDE Plugin Library.
* New user interface - it mimics the UI of the WP menu manager so it should look familiar to WordPress users.
* There is a new JavaScript based Skeleton Parser. It can parse MS Word style nested lists into something this plugin can use.
* New drag and drop interface to tweak you skeleton before you build it.
* You can drag and drop a text file containing your skeleton into the textarea and the plugin will parse it for you.
* Added instructions / examples to the help menu at the top of the screen.


= 0.2 =
* Added the option to automatically create a nav menu based on your skeleton.
* Added a select box so you can choose your own post type, instead of being limited only to creating pages.

= 0.1 =
* Initial build.