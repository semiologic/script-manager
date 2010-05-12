<?php
/*
Plugin Name: Script Manager
Plugin URI: http://www.semiologic.com/software/script-manager/
Description: Lets you insert scripts, on the entire site under <a href="options-general.php?page=script-manager">Settings / Scripts &amp; Meta</a>, and on individual posts and pages in their respective Scripts &amp; Meta boxes.
Version: 1.1.1 RC
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
Text Domain: script-manager
Domain Path: /lang
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/


load_plugin_textdomain('script-manager', false, dirname(plugin_basename(__FILE__)) . '/lang');


/**
 * script_manager
 *
 * @package Script Manager
 **/

class script_manager {
	/**
	 * admin_menu()
	 *
	 * @return void
	 **/
	
	function admin_menu() {
		if ( current_user_can('unfiltered_html') ) {
			add_options_page(
				__('Script Manager Settings', 'script-manager'),
				__('Scripts &amp; Meta', 'script-manager'),
				'manage_options',
				'script-manager',
				array('script_manager_admin', 'edit_options')
				);
		}
	} # admin_menu()
	
	
	/**
	 * meta_boxes()
	 *
	 * @return void
	 **/
	
	function meta_boxes() {
		if ( current_user_can('unfiltered_html') ) {
			if ( current_user_can('edit_posts') )
				add_meta_box('script_manager', __('Scripts &amp; Meta', 'script-manager'), array('script_manager_admin', 'edit_entry'), 'post');
			if ( current_user_can('edit_pages') )
				add_meta_box('script_manager', __('Scripts &amp; Meta', 'script-manager'), array('script_manager_admin', 'edit_entry'), 'page');
		}
	} # meta_boxes()
	
	
	/**
	 * scripts()
	 *
	 * @return void
	 **/

	function scripts() {
		wp_enqueue_script('jquery');
	} # scripts()
	
	
	/**
	 * head()
	 *
	 * @return void
	 **/
	
	function head() {
		if ( is_singular() ) {
			global $wp_the_query;
			$post_id = $wp_the_query->get_queried_object_id();
		} else {
			$post_id = false;
		}
		
		$override = $post_id && get_post_meta($post_id, '_scripts_override', true);
		
		if ( $override ) {
			$override = get_post_meta($post_id, '_scripts_footer', true)
				|| get_post_meta($post_id, '_scripts_head', true)
				|| get_post_meta($post_id, '_scripts_onload', true);
		}
		
		if ( !$override ) {
			$options = script_manager::get_options();
			
			if ( $options['head'] ) {
				echo $options['head'] . "\n";
			}
		}
		
		if ( $post_id && ( $script = get_post_meta($post_id, '_scripts_head', true) ) ) {
			echo $script . "\n";
		}
	} # head()
	
	
	/**
	 * footer()
	 *
	 * @return void
	 **/
	
	function footer() {
		if ( is_singular() ) {
			global $wp_the_query;
			$post_id = $wp_the_query->get_queried_object_id();
		} else {
			$post_id = false;
		}
		
		$override = $post_id && get_post_meta($post_id, '_scripts_override', true);
		
		if ( $override ) {
			$override = get_post_meta($post_id, '_scripts_footer', true)
				|| get_post_meta($post_id, '_scripts_head', true)
				|| get_post_meta($post_id, '_scripts_onload', true);
		}
		
		if ( !$override ) {
			$options = script_manager::get_options();

			if ( $options['footer'] ) {
				echo '<div class="scripts">' . "\n"
					. $options['footer'] . "\n"
					. '</div>' . "\n";
			}
		}
		
		if ( $post_id && ( $script = get_post_meta($post_id, '_scripts_footer', true) ) ) {
			echo '<div class="scripts">' . "\n"
				. $script . "\n"
				. '</div>' . "\n";
		}
	} # footer()
	
	
	/**
	 * onload()
	 *
	 * @return void
	 **/
	
	function onload() {
		if ( is_singular() ) {
			global $wp_the_query;
			$post_id = $wp_the_query->get_queried_object_id();
		} else {
			$post_id = false;
		}
		
		$override = $post_id && get_post_meta($post_id, '_scripts_override', true);
		
		if ( $override ) {
			$override = get_post_meta($post_id, '_scripts_footer', true)
				|| get_post_meta($post_id, '_scripts_head', true)
				|| get_post_meta($post_id, '_scripts_onload', true);
		}
		
		if ( !$override ) {
			$options = script_manager::get_options();
			
			if ( $options['onload'] ) {
				echo <<<EOS

<script type="text/javascript">
jQuery(document).ready(function() {
{$options['onload']}
});
</script>

EOS;

			}
		}
		
		if ( $post_id && ( $script = get_post_meta($post_id, '_scripts_onload', true) ) ) {
			echo <<<EOS

<script type="text/javascript">
jQuery(document).ready(function() {
$script
});
</script>

EOS;
		}
	} # onload()
	
	
	/**
	 * get_options()
	 *
	 * @return array options
	 **/
	
	function get_options() {
		static $o;
		
		if ( isset($o) && !is_admin() )
			return $o;
		
		$o = get_option('script_manager');
		
		if ( $o === false )
			$o = script_manager::init_options();
		
		return $o;
	} # get_options()
	
	
	/**
	 * init_options()
	 *
	 * @return array default options
	 **/
	
	function init_options() {
		$o = array(
			'head' => '',
			'footer' => '',
			'onload' => '',
			);

		update_option('script_manager', $o);
		
		return $o;
	} # init_options()
} # script_manager


function script_manager_admin() {
	include dirname(__FILE__) . '/script-manager-admin.php';
}

foreach ( array('page-new.php', 'page.php', 'post-new.php', 'post.php', 'settings_page_script-manager') as $hook )
	add_action("load-$hook", 'script_manager_admin');
	

add_action('admin_menu', array('script_manager', 'admin_menu'));
add_action('admin_menu', array('script_manager', 'meta_boxes'), 30);

if ( !is_admin() ) {
	add_action('wp_print_scripts', array('script_manager', 'scripts'));
	add_action('wp_head', array('script_manager', 'head'), 50);
	add_action('wp_footer', array('script_manager', 'footer'), 50);
	add_action('wp_footer', array('script_manager', 'onload'), 5000);
}
?>