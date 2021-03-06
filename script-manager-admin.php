<?php
/**
 * script_manager_admin
 *
 * @package Script Manager
 **/


class script_manager_admin {

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
	 * Constructor.
	 *
	 *
	 */
	public function __construct() {
		$this->plugin_url    = plugins_url( '/', __FILE__ );
		$this->plugin_path   = plugin_dir_path( __FILE__ );

		$this->init();
    }

	/**
	 * init()
	 *
	 * @return void
	 **/

	function init() {
		// more stuff: register actions and filters
		add_action('settings_page_script-manager', array($this, 'save_options'), 0);
        add_action('save_post', array($this, 'save_entry'));
	}

    /**
	 * save_options()
	 *
	 * @return void
	 **/
	
	function save_options() {
		if ( !$_POST || !current_user_can('manage_options') || !current_user_can('unfiltered_html') )
			return;
		
		check_admin_referer('script_manager');
		
		$options = array();
		
		foreach ( array_keys(script_manager_admin::get_fields()) as $field ) {
			$options[$field] = ( isset( $_POST[$field] )) ? stripslashes($_POST[$field]) : "";
			$options[$field] = trim($options[$field]);
		}
		
		update_option('script_manager', $options);
		
		echo '<div class="updated fade">' . "\n"
			. '<p>'
				. '<strong>'
				. __('Settings saved.', 'script-manager')
				. '</strong>'
			. '</p>' . "\n"
			. '</div>' . "\n";
		
		if ( strip_tags($options['onload']) != $options['onload'] ) {
			echo '<div class="error">' . "\n"
				. '<p>'
				. __('<strong>Warning</strong>: HTML tags are present in your onload event scripts. Please make sure that you did not include a script or body tag in it.', 'script-manager')
				. '</p>' . "\n"
				. '</div>' . "\n";
		}
                
        do_action('update_option_script_manager');
	} # save_options()
	
	
	/**
	 * edit_options()
	 *
	 * @return void
	 **/
	
	static function edit_options() {
		echo '<div class="wrap">' . "\n"
			. '<form method="post" action="">' . "\n";
		
		wp_nonce_field('script_manager');
		
		$options = script_manager::get_options();

		echo '<h2>'
			. __('Script Manager Settings', 'script-manager')
			. '</h2>' . "\n";
		
		echo '<p>' . __('The script manager lets you insert arbitrary &lt;script&gt; tags on your site, and hook into its &lt;body&gt; tag\'s onload event. Fields similar to the ones below let you do the same on individual posts and pages.', 'script-manager') . '</p>' . "\n";
		
		echo '<table class="form-table">' . "\n";
		
		$fields = script_manager_admin::get_fields();
		
		foreach ( $fields as $field => $details ) {
			echo '<tr valign="top">' . "\n"
				. '<th scope="row">'
				. $details['label']
				. '</th>' . "\n"
				. '<td>' . "\n"
				. '<textarea name="' . $field . '" cols="58" rows="8" class="code widefat">'
				. esc_html($options[$field])
				. '</textarea>' . "\n"
				. $details['desc']
				. '</td>' . "\n"
				. '</tr>' . "\n";
		}
		
		echo '</table>' . "\n";
		
		echo '<p class="submit">'
			. '<input type="submit"'
				. ' value="' . esc_attr(__('Save Changes', 'script-manager')) . '"'
				. ' />'
			. '</p>' . "\n";
		
		echo '</form>' . "\n"
			. '</div>' . "\n";
	} # edit_options()
	
	
	/**
	 * edit_entry($post)
	 *
	 * @param object $post
	 * @return void
	 **/
	
	static function edit_entry($post) {
		
		echo '<p>'
			. __('These fields let you insert entry-specific scripts. They work in exactly the same way as site-wide scripts, which you can configure under <a href="options-general.php?page=script-manager" target="_blank">Settings / Scripts &amp; Meta</a>.', 'script-manager')
			. '</p>' . "\n";
		
		echo '<table style="width: 100%; border-collapse: collapse; padding: 2px 0px; spacing: 2px 0px;">';
		
		if ( $post->ID > 0 )
			$value = get_post_meta($post->ID, '_scripts_override', true);
		else
			$value = false;

		echo '<tr valign="top">' . "\n"
			. '<th scope="row" width="120px;">'
			. __('Behavior', 'script-manager')
			. '</th>' . "\n"
			. '<td>' . "\n"
			. '<label>'
			. '<input type="radio" tabindex="5" name="scripts[override]" value=""'
			. ( !$value
				? ' checked="checked"'
				: ''
				)
			. ' />'
			. '&nbsp;'
			. __('Append to the site-wide scripts and events', 'script-manager')
			. '</label>'
			. '<br />' . "\n"
			. '<label>'
			. '<input type="radio" tabindex="5" name="scripts[override]" value="1"'
			. ( $value
				? ' checked="checked"'
				: ''
				)
			. ' />'
			. '&nbsp;'
			. __('Replace the site-wide scripts and events', 'script-manager')
			. '</label>'
			. '</td>' . "\n"
			. '</tr>' . "\n";
		
		$fields = script_manager_admin::get_fields();
		
		foreach ( $fields as $field => $details ) {
			if ( !$details['site_wide_only'] ) {
				$value = '';
				if ( $post->ID > 0 )
					$value = get_post_meta($post->ID, '_scripts_' . $field, true);

				echo '<tr valign="top">' . "\n"
					. '<th scope="row" width="120px;">'
					. $details['label']
					. '</th>' . "\n"
					. '<td>' . "\n"
					. '<textarea name="scripts[' . $field . ']" cols="58" rows="4" tabindex="5" class="code widefat" style="width: 95%">'
					. esc_html($value)
					. '</textarea>' . "\n"
					. $details['example']
					. '</td>' . "\n"
					. '</tr>' . "\n";
			}
		}
		
		echo '</table>' . "\n";
	} # edit_entry()
	

	/**
	 * save_entry($post_id)
	 *
	 * @param int $post_id
	 * @return void
	 **/

	function save_entry($post_id) {
		if ( !isset($_POST['scripts']) || wp_is_post_revision($post_id) || !current_user_can('edit_post', $post_id) )
			return;
		
		if ( current_user_can('unfiltered_html') && current_user_can('edit_post', $post_id) ) {
			if ( !empty($_POST['scripts']['override']) )
				update_post_meta($post_id, '_scripts_override', '1');
			else
				delete_post_meta($post_id, '_scripts_override');
			
			foreach ( array_keys(script_manager_admin::get_fields()) as $field ) {
				if ( !isset($_POST['scripts'][$field]) )
					continue;
				$value = $_POST['scripts'][$field];
				$value = trim($value);
				
				if ( $value )
					update_post_meta($post_id, '_scripts_' . $field, $value);
				else
					delete_post_meta($post_id, '_scripts_' . $field);
			}
                        
            do_action('save_entry_script_manager');
		}
                
	} # save_entry()
	
	
	/**
	 * get_fields()
	 *
	 * @return array fields
	 **/
	
	static function get_fields() {
		$fields = array(
			'head' => array(
					'label' => __('Header Scripts', 'script-manager'),
					'desc' => '<p>'
						. __('Header scripts get inserted in between the &lt;head&gt; and &lt;/head&gt; tags of the web page. Use like:', 'script-manager')
						. '</p>' . "\n"
						. '<pre>'
						. esc_html(__('<script type="text/javascript" src="http://domain.com/path/to/script.js"></script>', 'script-manager'))
						. '</pre>' . "\n"
						. '<p>'
						. __('Note that you can also use this field to insert arbitrary &lt;meta&gt; and &lt;style&gt; tags.', 'script-manager')
						. '</p>' . "\n",
					'example' => '<p>'
						. sprintf(__('Example: %s', 'script-manager'), '<code>' . esc_html(__('<script type="text/javascript" src="http://domain.com/path/to/script.js"></script>', 'script-manager')) . '</code>')
						. '</p>' . "\n",
					'site_wide_only' => false,
					),
			'body' => array(
					'label' => __('Body Scripts', 'script-manager'),
					'desc' => '<p>'
						. __('Body scripts get inserted in the body of the web page immediately after the &lt;body&gt; tag. Use like:', 'script-manager')
						. '</p>' . "\n"
						. '<pre>'
						. esc_html(__('<script type="text/javascript" src="http://domain.com/path/to/script.js"></script>', 'script-manager'))
						. '</pre>' . "\n"
						. '<p>'
						. __('Note that you can also use this field to insert arbitrary html.', 'script-manager')
						. '</p>' . "\n",
					'example' => '<p>'
						. sprintf(__('Example: %s', 'script-manager'), '<code>' . esc_html(__('<script type="text/javascript" src="http://domain.com/path/to/script.js"></script>', 'script-manager')) . '</code>')
						. '</p>' . "\n",
					'site_wide_only' => true,
					),
			'footer' => array(
					'label' => __('Footer Scripts', 'script-manager'),
					'desc' => '<p>'
						. __('Footer scripts get inserted at the very bottom of the web page, before the &lt;/body&gt; tag. Use like:', 'script-manager')
						. '</p>' . "\n"
						. '<pre>'
						. esc_html(__('<script type="text/javascript" src="http://domain.com/path/to/script.js"></script>', 'script-manager'))
						. '</pre>' . "\n"
						. '<p>'
						. __('Note that you can also use this field to insert arbitrary html.', 'script-manager')
						. '</p>' . "\n",
					'example' => '<p>'
						. sprintf(__('Example: %s', 'script-manager'), '<code>' . esc_html(__('<script type="text/javascript" src="http://domain.com/path/to/script.js"></script>', 'script-manager')) . '</code>')
						. '</p>' . "\n",
					'site_wide_only' => false,
					),
			'onload' => array(
					'label' => __('Onload Events', 'script-manager'),
					'desc' => '<p>'
						. __('Onload Events get fired once the web page is fully loaded. <strong>Do NOT include &lt;body&gt; or &lt;script&gt; tags in the above field</strong>, and don\'t forget trailing semicolons (;). Failing to do so will result in javascript errors.', 'script-manager')
						. '</p>' . "\n"
						. '<p>'
						. __('When a script\'s installation instructions tell you to do something like:', 'script-manager')
						. '</p>' . "\n"
						. '<pre>'
						. esc_html(__('<body onload="doSomething();">', 'script-manager'))
						. '</pre>' . "\n"
						. '<p>'
						. __('Then insert this part only in the above field:', 'script-manager')
						. '</p>' . "\n"
						. '<pre>'
						. __('doSomething();', 'script-manager')
						. '</pre>' . "\n",
					'example' => '<p>'
						. sprintf(__('Example: %s', 'script-manager'), '<code>' . __('doSomething();', 'script-manager') . '</code>')
						. '</p>' . "\n",
					'site_wide_only' => false,
					),
			);
		
		return $fields;
	} # get_fields()
} # script_manager_admin

$script_manager_admin = script_manager_admin::get_instance();
