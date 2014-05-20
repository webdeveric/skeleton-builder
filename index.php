<?php
/*
Plugin Name: Skeleton Builder
Plugin Group: Utilities
Plugin URI: http://phplug.in/
Version: 0.3.1
Description: Batch create blank pages/posts/whatever for your site.
Author: Eric King
Author URI: http://webdeveric.com/
*/

defined('ABSPATH') || exit;

// This plugin only needs to run in the admin so check for that first.
if ( ! is_admin())
    return;

if ( ! defined('WDE_PLUGIN_LIB_VERSION')) {

    public function skeleton_builder_requirements_not_met()
    {
        echo '<div class="error"><p><strong><a href="http://webdeveric.com/" target="_blank">WDE Plugin Library</a></strong> is required for Skeleton Builder. Please install and activate that plugin first.</p><p>Skeleton Builder has been deactivated.</p></div>';
        deactivate_plugins( plugin_basename( __FILE__ ) );
        unset( $_GET['activate'] );
    }
    add_action('admin_notices', 'skeleton_builder_requirements_not_met');

    return;

}

define('SKELETON_BUILDER_PLUGIN_FILE', __FILE__);

include __DIR__ . '/Skeleton-Builder-Plugin.php';
