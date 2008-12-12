<?php

class script_manager_admin
{
	#
	# init()
	#
	
	function init()
	{
		add_filter('sem_api_key_protected', array('script_manager_admin', 'sem_api_key_protected'));

		add_action('admin_menu', array('script_manager_admin', 'admin_menu'));
		add_action('admin_menu', array('script_manager_admin', 'meta_boxes'), 30);
	} # init()


	#
	# sem_api_key_protected()
	#
	
	function sem_api_key_protected($array)
	{
		$array[] = 'http://www.semiologic.com/media/software/marketing/script-manager/script-manager.zip';
		
		return $array;
	} # sem_api_key_protected()
	
	
	#
	# admin_menu()
	#
	
	function admin_menu()
	{
		if ( current_user_can('unfiltered_html') )
		{
			add_options_page(
				__('Scripts'),
				__('Scripts'),
				'manage_options',
				__FILE__,
				array('script_manager_admin', 'options_page')
				);
		}
	} # admin_menu()
	
	
	#
	# update_options()
	#
	
	function update_options()
	{
		check_admin_referer('script_manager');
		
		$options = array();
		
		foreach ( array_keys(script_manager_admin::get_fields()) as $field )
		{
			$options[$field] = stripslashes($_POST[$field]);
			$options[$field] = trim($options[$field]);
		}
		
		update_option('script_manager', $options);
	} # update_options()
	
	
	#
	# options_page()
	#
	
	function options_page()
	{
		echo '<div class="wrap">'
			. '<form method="post" action="">'
			. '<input type="hidden" name="update_script_manager" value="1" />' . "\n";
		
		if ( $_POST['update_script_manager'] )
		{
			script_manager_admin::update_options();

			echo '<div class="updated">' . "\n"
				. '<p>'
					. '<strong>'
					. __('Settings saved.')
					. '</strong>'
				. '</p>' . "\n"
				. '</div>' . "\n";
		}
		
		if ( function_exists('wp_nonce_field') ) wp_nonce_field('script_manager');
		
		$options = script_manager::get_options();

		echo '<h2>'
			. 'Script Manager Settings'
			. '</h2>';
		
		$str = <<<EOF
<p>The script manager let you insert arbitrary &lt;script&gt; tags on your site, and hook into its &lt;body&gt; tag's onload event. Fields similar to the ones below let you do the same on individual posts and pages.</p>
EOF;

		echo $str;
		
		echo '<table class="form-table">';
		
		$fields = script_manager_admin::get_fields();
		
		foreach ( $fields as $field => $details )
		{
			echo '<tr valign="top">'
				. '<th scope="row">'
				. $details['label']
				. '</th>'
				. '<td>'
				. '<textarea name="' . $field . '" cols="58" rows="8" class="code" style="width: 90%;">'
				. format_to_edit($options[$field])
				. '</textarea>'
				. $details['desc']
				. '</td>'
				. '</tr>';
		}
		
		echo '</table>';
		
		echo '<p class="submit">'
			. '<input type="submit"'
				. ' value="' . attribute_escape(__('Save Changes')) . '"'
				. ' />'
			. '</p>' . "\n";
		
		echo '</form>'
			. '</div>';
	} # options_page()
	
	
	#
	# meta_boxes()
	#
	
	function meta_boxes()
	{
		if ( current_user_can('unfiltered_html') )
		{
			add_meta_box('script_manager', 'Scripts', array('script_manager_admin', 'entry_editor'), 'post');
			add_meta_box('script_manager', 'Scripts', array('script_manager_admin', 'entry_editor'), 'page');
			add_action('save_post', array('script_manager_admin', 'save_entry'));
		}
	} # meta_boxes()
	
	
	#
	# entry_editor()
	#
	
	function entry_editor()
	{
		$post_ID = isset($GLOBALS['post_ID']) ? $GLOBALS['post_ID'] : $GLOBALS['temp_ID'];
		
		echo '<p>These fields let you insert entry-specific scripts. They work in exactly the same way as site-wide scripts, which you can configure under <a href="' . trailingslashit(get_option('siteurl')) . 'wp-admin/options-general.php?page=' . plugin_basename(__FILE__) . '" target="_blank">Settings / Scripts</a>.</p>';
		
		echo '<table style="width: 100%; border-collapse: collapse; padding: 2px 0px; spacing: 2px 0px;">';
		
		if ( $post_ID > 0 )
		{
			$value = get_post_meta($post_ID, '_scripts_override', true);
		}
		else
		{
			$value = false;
		}

		echo '<tr valign="top">'
			. '<th scope="row" width="120px;">'
			. 'Behavior'
			. '</th>'
			. '<td>'
			. '<label>'
			. '<input type="radio" tabindex="5" name="scripts[override]" value=""'
			. ( !$value
				? ' checked="checked"'
				: ''
				)
			. ' />'
			. '&nbsp;'
			. 'Append to the site-wide scripts and events'
			. '</label>'
			. '<br />'
			. '<label>'
			. '<input type="radio" tabindex="5" name="scripts[override]" value="1"'
			. ( $value
				? ' checked="checked"'
				: ''
				)
			. ' />'
			. '&nbsp;'
			. 'Replace the site-wide scripts and events'
			. '</label>'
			. '</td>'
			. '</tr>';
		
		$fields = script_manager_admin::get_fields();
		$value = '';
		
		foreach ( $fields as $field => $details )
		{
			if ( $post_ID > 0 )
			{
				$value = get_post_meta($post_ID, '_scripts_' . $field, true);
			}
			
			echo '<tr valign="top">'
				. '<th scope="row" width="120px;">'
				. $details['label']
				. '</th>'
				. '<td>'
				. '<textarea name="scripts[' . $field . ']" cols="58" rows="4" tabindex="5" class="code" style="width: 90%;">'
				. format_to_edit($value)
				. '</textarea>'
				. '</td>'
				. '</tr>';
		}
		
		echo '</table>';
	} # entry_editor()
	

	#
	# save_entry()
	#

	function save_entry($post_ID)
	{
		if ( current_user_can('unfiltered_html') )
		{
			delete_post_meta($post_ID, '_scripts_override');
			
			if ( $_POST['scripts']['override'] )
			{
				add_post_meta($post_ID, '_scripts_override', '1', true);
			}
			
			foreach ( array_keys(script_manager_admin::get_fields()) as $field )
			{
				delete_post_meta($post_ID, '_scripts_' . $field);
				
				$value = stripslashes($_POST['scripts'][$field]);
				$value = trim($value);
				
				if ( $value )
				{
					add_post_meta($post_ID, '_scripts_' . $field, $value, true);
				}
			}
		}
	} # save_entry()
	
	
	#
	# get_fields()
	#
	
	function get_fields()
	{
		$fields = array(
			'head' => array(
					'label' => 'Header Scripts',
					'desc' => <<<EOF
<p>Header scripts get inserted in between the &lt;head&gt; and &lt;/head&gt; tags of the web page. Use like:</p>
<pre>&lt;script type=&quot;text/javascript&quot; src=&quot;http://domain.com/path/to/script.js&quot;&gt;&lt;/script&gt;</pre>
<p>Note that you can also use this field to insert arbitrary &lt;meta&gt; and &lt;style&gt; tags.
EOF
					),
			'footer' => array(
					'label' => 'Footer Scripts',
					'desc' => <<<EOF
<p>Footer scripts get inserted at the very bottom of the web page, before the &lt;/body&gt; tag. Use like:</p>
<pre>&lt;script type=&quot;text/javascript&quot; src=&quot;http://domain.com/path/to/script.js&quot;&gt;&lt;/script&gt;</pre>
<p>Note that you can also use this field to insert arbitrary html.
EOF
					),
			'onload' => array(
					'label' => 'Onload Events',
					'desc' => <<<EOF
<p>Onload Events get fired once the web page is fully loaded.</p>
<p>When a script's installation instructions tell you to do something like <code>&lt;body onload=&quot;doSomething();&quot;&gt;</code>, paste the <code>doSomething();</code> code in the above field instead.</p>
EOF
					)
			);
		
		return $fields;
	} # get_fields()
} # script_manager_admin

script_manager_admin::init();

?>