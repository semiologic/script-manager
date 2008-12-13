<?php
/*
Plugin Name: Script Manager
Plugin URI: http://www.semiologic.com/software/marketing/script-manager/
Description: Lets you insert scripts on your site, both globally for your site (under Settings / Scripts), and locally for your individual posts and pages (under Scripts, in the editor's advanced options)
Author: Denis de Bernardy
Version: 1.0
Author URI: http://www.getsemiologic.com
Update Service: http://version.semiologic.com/wordpress
Update Tag: script_manager
Update Package: http://www.semiologic.com/media/software/marketing/script-manager/script-manager.zip
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/


class script_manager
{
	#
	# init()
	#

	function init()
	{
		add_action('wp_head', array('script_manager', 'head'), 1000);
		add_action('wp_footer', array('script_manager', 'footer'), 1000000);
	} # init()
	
	
	#
	# head()
	#
	
	function head()
	{
		if ( is_singular() )
		{
			$post_id = $GLOBALS['wp_query']->get_queried_object_id();
		}
		else
		{
			$post_id = false;
		}
		
		if ( !$post_id
			|| !( in_array('_scripts_override', (array) get_post_custom_keys($post_id))
				&& get_post_meta($post_id, '_scripts_override', true)
				)
			)
		{
			$options = script_manager::get_options();

			echo $options['head'] . "\n";
		}
		
		if ( $post_id
			&& ( $script = get_post_meta($post_id, '_scripts_head', true) )
			)
		{
			echo $script . "\n";
		}
	} # head()
	
	
	#
	# footer()
	#
	
	function footer()
	{
		if ( is_singular() )
		{
			$post_id = $GLOBALS['wp_query']->get_queried_object_id();
		}
		else
		{
			$post_id = false;
		}
		
		if ( !$post_id
			|| !( in_array('_scripts_override', (array) get_post_custom_keys($post_id))
				&& get_post_meta($post_id, '_scripts_override', true)
				)
			)
		{
			$options = script_manager::get_options();

			if ( $options['footer'] )
			{
				echo '<div class="scripts">'
					. $options['footer']
					. '</div>' . "\n";
			}
		}
		
		if ( $post_id
			&& ( $script = get_post_meta($post_id, '_scripts_footer', true) )
			)
		{
			echo '<div class="scripts">'
				. $script
				. '</div>' . "\n";
		}

		if ( !$post_id
			|| !( in_array('_scripts_override', (array) get_post_custom_keys($post_id))
				&& get_post_meta($post_id, '_scripts_override', true)
				)
			)
		{
			if ( $options['onload'] )
			{
				echo '<script type="text/javascript">' . "\n"
					. $options['onload'] . "\n"
					. "</script>" . "\n";
			}
		}
		
		if ( $post_id
			&& ( $script = get_post_meta($post_id, '_scripts_onload', true) )
			)
		{
			echo '<script type="text/javascript">' . "\n"
				. $script . "\n"
				. "</script>" . "\n";
		}
	} # footer()
	
	
	#
	# get_options()
	#
	
	function get_options()
	{
		if ( ( $o = get_option('script_manager') ) === false )
		{
			$o = array(
				'head' => '',
				'footer' => '',
				'onload' => '',
				);

			update_option('script_manager', $o);
		}
		
		return $o;
	} # get_options()
} # script_manager

script_manager::init();


if ( is_admin() )
{
	include dirname(__FILE__) . '/script-manager-admin.php';
}
?>