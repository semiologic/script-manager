<?php
/**
 * script_manager_admin
 *
 * @package Script Manager
 **/

add_action('admin_menu', array('script_manager_admin', 'admin_menu'));
add_action('settings_page_script-manager', array('script_manager_admin', 'save_options'));

add_action('admin_menu', array('script_manager_admin', 'meta_boxes'), 30);
add_action('save_post', array('script_manager_admin', 'save_entry'));

class script_manager_admin {
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
	 * save_options()
	 *
	 * @return void
	 **/
	
	function save_options() {
		if ( !$_POST )
			return;
		
		check_admin_referer('script_manager');
		
		$options = array();
		
		foreach ( array_keys(script_manager_admin::get_fields()) as $field ) {
			$options[$field] = stripslashes($_POST[$field]);
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
	} # save_options()
	
	
	/**
	 * edit_options()
	 *
	 * @return void
	 **/
	
	function edit_options() {
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
		
		foreach ( $fields as $field => $details )
		{
			echo '<tr valign="top">' . "\n"
				. '<th scope="row">'
				. $details['label']
				. '</th>' . "\n"
				. '<td>' . "\n"
				. '<textarea name="' . $field . '" cols="58" rows="8" class="code" style="width: 90%;">'
				. format_to_edit($options[$field])
				. '</textarea>' . "\n"
				. $details['desc']
				. '</td>' . "\n"
				. '</tr>' . "\n";
		}
		
		echo '</table>' . "\n";
		
		echo '<p class="submit">'
			. '<input type="submit"'
				. ' value="' . attribute_escape(__('Save Changes', 'script-manager')) . '"'
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
	
	function edit_entry($post) {
		$post_id = $post->ID;
		
		echo '<p>'
			. sprintf(__('These fields let you insert entry-specific scripts. They work in exactly the same way as site-wide scripts, which you can configure under <a href="%s/wp-admin/options-general.php?page=script-manager" target="_blank">Settings / Scripts &amp; Meta</a>.', 'script-manager'), attribute_escape(site_url(null, 'admin')))
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
			$value = '';
			if ( $post->ID > 0 )
				$value = get_post_meta($post->ID, '_scripts_' . $field, true);
			
			echo '<tr valign="top">' . "\n"
				. '<th scope="row" width="120px;">'
				. $details['label']
				. '</th>' . "\n"
				. '<td>' . "\n"
				. '<textarea name="scripts[' . $field . ']" cols="58" rows="4" tabindex="5" class="code" style="width: 90%;">'
				. format_to_edit($value)
				. '</textarea>' . "\n"
				. $details['example']
				. '</td>' . "\n"
				. '</tr>' . "\n";
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
		if ( wp_is_post_revision($post_id) )
			return;
		
		if ( current_user_can('unfiltered_html') && current_user_can('edit_post', $post_id) ) {
			if ( $_POST['scripts']['override'] )
				update_post_meta($post_ID, '_scripts_override', '1');
			else
				delete_post_meta($post_ID, '_scripts_override');
			
			foreach ( array_keys(script_manager_admin::get_fields()) as $field )
			{
				$value = stripslashes($_POST['scripts'][$field]);
				$value = trim($value);
				
				if ( $value )
					update_post_meta($post_ID, '_scripts_' . $field, $value);
				else
					delete_post_meta($post_ID, '_scripts_' . $field);
			}
		}
	} # save_entry()
	
	
	/**
	 * get_fields()
	 *
	 * @return array fields
	 **/
	
	function get_fields() {
		$fields = array(
			'head' => array(
					'label' => 'Header Scripts',
					'desc' => '<p>'
						. htmlspecialchars(__('Header scripts get inserted in between the <head> and </head> tags of the web page. Use like:', 'script-manager'))
						. '</p>' . "\n"
						. '<pre>'
						. htmlspecialchars(__('<script type="text/javascript" src="http://domain.com/path/to/script.js"></script>', 'script-manager'))
						. '</pre>' . "\n"
						. '<p>'
						. htmlspecialchars(__('Note that you can also use this field to insert arbitrary <meta> and <style> tags.', 'script-manager'))
						. '</p>' . "\n",
					'example' => '<p>'
						. sprintf(__('Example: %s', 'script-manager'), '<code>' . htmlspecialchars(__('<script type="text/javascript" src="http://domain.com/path/to/script.js"></script>', 'script-manager')) . '</code>')
						. '</p>' . "\n",
					),
			'footer' => array(
					'label' => 'Footer Scripts',
					'desc' => '<p>'
						. htmlspecialchars(__('Footer scripts get inserted at the very bottom of the web page, before the </body> tag. Use like:', 'script-manager'))
						. '</p>' . "\n"
						. '<pre>'
						. htmlspecialchars(__('<script type="text/javascript" src="http://domain.com/path/to/script.js"></script>', 'script-manager'))
						. '</pre>' . "\n"
						. '<p>'
						. htmlspecialchars(__('Note that you can also use this field to insert arbitrary html.', 'script-manager'))
						. '</p>' . "\n",
					'example' => '<p>'
						. sprintf(__('Example: %s', 'script-manager'), '<code>' . htmlspecialchars(__('<script type="text/javascript" src="http://domain.com/path/to/script.js"></script>', 'script-manager')) . '</code>')
						. '</p>' . "\n",
					),
			'onload' => array(
					'label' => 'Onload Events',
					'desc' => '<p>'
						. htmlspecialchars(__('Onload Events get fired once the web page is fully loaded.', 'script-manager'))
						. '</p>' . "\n"
						. '<p>'
						. htmlspecialchars(__('When a script\'s installation instructions tell you to do something like:', 'script-manager'))
						. '</p>' . "\n"
						. '<pre>'
						. htmlspecialchars(__('<body onload="doSomething();">', 'script-manager'))
						. '</pre>' . "\n"
						. '<p>'
						. htmlspecialchars(__('Then insert this part only in the above field:', 'script-manager'))
						. '</p>' . "\n"
						. '<pre>'
						. htmlspecialchars(__('doSomething();', 'script-manager'))
						. '</pre>' . "\n"
						. '<p>'
						. htmlspecialchars(__('DO NOT INCLUDE SCRIPT TAGS IN THE ABOVE FIELD. And be sure to separate multiple function calls with semicolons (;). Else, you will end up with javascript errors.', 'script-manager'))
						. '</p>' . "\n",
					'example' => '<p>'
						. sprintf(__('Example: %s', 'script-manager'), '<code>' . htmlspecialchars(__('doSomething();', 'script-manager')) . '</code>')
						. '</p>' . "\n",
					),
			);
		
		return $fields;
	} # get_fields()
} # script_manager_admin
?>