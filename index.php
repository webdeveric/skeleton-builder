<?php
/*
Plugin Name: Skeleton Builder
Version: 0.3.0
Author: <a href="http://webdeveric.com/">Eric King</a>, <a href="http://timwickstrom.com/">Tim Wickstrom</a>
Author URI: http://webdeveric.com/
Description: Batch create blank pages/posts/whatever for your WP site.
*/

defined( 'ABSPATH' ) || exit;

// This plugin only needs to run in the admin so check for that first.
if( ! is_admin() )
	return 1;

if( ! defined( 'WDE_PLUGIN_LIB_VERSION' ) ){

	function skeleton_builder_requirements_not_met(){
		echo '<div class="error"><p><strong><a href="http://webdeveric.com/" target="_blank">WDE Plugin Library</a></strong> is required for Skeleton Builder. Please install and activate that plugin first.</p><p>Skeleton Builder has been deactivated.</p></div>';
		deactivate_plugins( plugin_basename( __FILE__ ) );
		unset( $_GET['activate'] );
	}
	add_action( 'admin_notices', 'skeleton_builder_requirements_not_met' );

	return 1;

}

define('SKELETON_BUILDER_PLUGIN_FILE', __FILE__ );

include __DIR__ . '/Skeleton-Builder-Plugin.php';