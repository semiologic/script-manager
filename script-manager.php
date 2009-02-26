<?php
/*
Plugin Name: Script Manager
Plugin URI: http://www.semiologic.com/software/marketing/script-manager/
Description: Lets you insert scripts, on the entire site (Settings / Scripts &amp; Meta) and on individual posts and pages (Scripts &amp; Meta panel in the editor)
Version: 1.1 alpha
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/


load_plugin_textdomain('script-manager', null, basename(dirname(__FILE__)) . '/lang');


/**
 * script_manager
 *
 * @package Script Manager
 * @author Denis
 **/

add_action('wp_head', array('script_manager', 'head'), 1000);
add_action('wp_footer', array('script_manager', 'footer'), 1000000);

class script_manager {
	/**
	 * head()
	 *
	 * @return void
	 **/
	
	function head() {
		if ( is_singular() )
			$post_id = $GLOBALS['wp_query']->get_queried_object_id();
		else
			$post_id = false;
		
		$override = $post_id && get_post_meta($post_id, '_scripts_override', true);
		
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
		if ( is_singular() )
			$post_id = $GLOBALS['wp_query']->get_queried_object_id();
		else
			$post_id = false;
		
		$override = $post_id && get_post_meta($post_id, '_scripts_override', true);
		
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

		if ( !$override ) {
			if ( $options['onload'] ) {
				echo '<script type="text/javascript">' . "\n"
					. $options['onload'] . "\n"
					. "</script>" . "\n";
			}
		}
		
		if ( $post_id && ( $script = get_post_meta($post_id, '_scripts_onload', true) ) ) {
			echo '<script type="text/javascript">' . "\n"
				. $script . "\n"
				. "</script>" . "\n";
		}
	} # footer()
	
	
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

if ( is_admin() )
	include dirname(__FILE__) . '/script-manager-admin.php';
?>