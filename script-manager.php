<?php
/*
Plugin Name: Script Manager
Plugin URI: http://www.semiologic.com/software/script-manager/
Description: Lets you insert scripts, on the entire site under <a href="options-general.php?page=script-manager">Settings / Scripts &amp; Meta</a>, and on individual posts and pages in their respective Scripts &amp; Meta boxes.
Version: 1.4.2
Author: Denis de Bernardy & Mike Koepke
Author URI: http://www.getsemiologic.com
Text Domain: script-manager
Domain Path: /lang
License: Dual licensed under the MIT and GPLv2 licenses
*/

/*
Terms of use
------------

This software is copyright Denis de Bernardy & Mike Koepke, and is distributed under the terms of the MIT and GPLv2 licenses.
**/


/**
 * script_manager
 *
 * @package Script Manager
 **/

class script_manager {

	/**
	 * Plugin instance.
	 *
	 * @see get_instance()
	 * @type object
	 */
	protected static $instance = NULL;

	/**
	 * URL to this plugin's directory.
	 *
	 * @type string
	 */
	public $plugin_url = '';

	/**
	 * Path to this plugin's directory.
	 *
	 * @type string
	 */
	public $plugin_path = '';

	/**
	 * Access this plugin’s working instance
	 *
	 * @wp-hook plugins_loaded
	 * @return  object of this class
	 */
	public static function get_instance()
	{
		NULL === self::$instance and self::$instance = new self;

		return self::$instance;
	}

	/**
	 * Loads translation file.
	 *
	 * Accessible to other classes to load different language files (admin and
	 * front-end for example).
	 *
	 * @wp-hook init
	 * @param   string $domain
	 * @return  void
	 */
	public function load_language( $domain )
	{
		load_plugin_textdomain(
			$domain,
			FALSE,
			dirname(plugin_basename(__FILE__)) . '/lang'
		);
	}

	/**
	 * Constructor.
	 *
	 *
	 */
	public function __construct() {
		$this->plugin_url    = plugins_url( '/', __FILE__ );
		$this->plugin_path   = plugin_dir_path( __FILE__ );
		$this->load_language( 'script-manager' );

		add_action( 'plugins_loaded', array ( $this, 'init' ) );
    } #script_manager()

	/**
	 * init()
	 *
	 * @return void
	 **/

	function init() {
		// more stuff: register actions and filters
		if ( !is_admin() ) {
			add_action('wp_enqueue_scripts', array($this, 'scripts'));
			add_action('wp_head', array($this, 'head'), 50);
			add_action('wp_footer', array($this, 'footer'), 50);
			add_action('wp_footer', array($this, 'onload'), 5000);

			$options = script_manager::get_options();
			if ( !empty( $options['body'] ) ) {
			   add_action('init', array($this, 'ob_start'), 10000);
			}
		}
		else {
			// more stuff: register actions and filters
			add_action('admin_menu', array($this, 'admin_menu'));
            add_action('admin_menu', array($this, 'meta_boxes'), 30);

			foreach ( array('page-new.php', 'page.php', 'post-new.php', 'post.php', 'settings_page_script-manager') as $hook )
				add_action("load-$hook", array($this, 'script_manager_admin'));
		}
	}

	/**
	* script_manager_admin()
	*
	* @return void
	**/
	function script_manager_admin() {
		include_once $this->plugin_path . '/script-manager-admin.php';
	}

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
				|| get_post_meta($post_id, '_scripts_onload', true)
				|| get_post_meta($post_id, '_scripts_body', true);
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
				|| get_post_meta($post_id, '_scripts_onload', true)
				|| get_post_meta($post_id, '_scripts_body', true);
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
				|| get_post_meta($post_id, '_scripts_onload', true)
				|| get_post_meta($post_id, '_scripts_body', true);
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
	* ob_start()
	*
	* @return void
	**/

	function ob_start() {
		static $done = false;

		if ( $done )
			return;

		ob_start(array($this, 'ob_filter'));
		add_action('wp_footer', array($this, 'ob_flush'), 100000);
		$done = true;

	} # ob_start()

	/**
	* ob_filter()
	*
	* @param string $text
	* @return string $text
	**/

	function ob_filter($text) {
		$text = preg_replace_callback("/
			\s*<body\s+
			([^>]*)
			>
			(.*?)
			<\s*\/\s*body\s*>
			/isx", array($this, 'ob_filter_callback'), $text);

		return $text;
	} # ob_filter()


	/**
	* ob_flush()
	*
	* @return void
	**/

	static function ob_flush() {
		static $done = true;

		if ( $done )
			return;

		ob_end_flush();
		$done = true;
	} # ob_flush()


	/**
	* ob_filter_callback()
	*
	* @param array $match
	* @return string $str
	**/

	function ob_filter_callback($match) {

		# skip empty anchors
		if ( !trim($match[2]) )
			return $match[0];

		$options = script_manager::get_options();

		# parse anchor
		$body =  '<body '
		. $match[1]
		. '>' . "\n"
		. $options['body']
		. $match[2]
		. '</body>' . "\n";

		# return anchor
		return $body;
	} # ob_filter_callback()

	/**
	 * get_options()
	 *
	 * @return array options
	 **/
	
    static function get_options() {
		static $o;
		
		if ( isset($o) && !is_admin() )
			return $o;
		
		$o = get_option('script_manager');
		
		if ( $o === false || !isset($o['body']) )
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
			'body' => '',
			);

		update_option('script_manager', $o);
		
		return $o;
	} # init_options()
} # script_manager

$script_manager = script_manager::get_instance();
