<?php
/*
Plugin Name: E-bus Plugin
Plugin URI: http://todo.com
Description: This is a plugin developed for E-Business Course
Version: 1.0.0
Author: Tsiamitros
Author URI: http://todo.com
License: GPLv2
Text Domain: e_bus_plugin
*/

if ( !defined( 'ABSPATH' ) ) 
{
	die;
}

// Include the widget.
include_once plugin_dir_path( __FILE__ ) . 'e_bus_widget.php';

// Register the widget with WordPress. Requires PHP5.3+.
add_action( 'widgets_init', function(){
	register_widget( 'e_bus_widget' );
});

class Wrapper
{
	function __construct()
	{
		add_action( 'init', array( $this, 'custom_post_type'));
		//$this->compare_id_to_db();
		//add_shortcode('E-Bus-plugin-shortcode', 'compare_id_to_db');
	}
	function custom_post_type()
	{
		register_post_type( 'E-Bus-plugin', ['public' => true, 'label' => 'E-Bus plugin']);
	}
	function activate()
	{
		$this->custom_post_type();
		flush_rewrite_rules();
	}
	function deactivate()
	{
		flush_rewrite_rules();
	}
	static function uninstall()
	{
		// if uninstall.php is not called by WordPress, die
		if (!defined('WP_UNINSTALL_PLUGIN')) {
			die;
		}else{
			global $wpdb;
			$table = $wpdb->prefix . "user_viewed_products";
			$q = "drop table if exists " . $table;
			$wpdb->query( $q );
		}
	}
}

if(class_exists('Wrapper'))
{
	$wrapper_instance = new Wrapper();
}

//activation
register_activation_hook( __FILE__, array ($wrapper_instance, 'activate'));

//deactivation
register_deactivation_hook( __FILE__, array($wrapper_instance, 'deactivate'));

//uninstall
register_uninstall_hook( __FILE__, 'uninstall');

