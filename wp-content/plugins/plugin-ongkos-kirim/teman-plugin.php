<?php

if(! class_exists('TemanPluginTonjoo')):

Class TemanPluginTonjoo
{
	function __construct($plugin_name, $prefix, $recommended)
	{
		$this->init();
		$this->plugin_name = $plugin_name;
		$this->prefix = $prefix ? $prefix : 'teman_plugin_tonjoo';
		$this->recommended = $recommended;
		$this->get_plugins = get_plugins();
		$this->status = array();

		$this->get_status(); // get the status

		if($this->status['is_notification']) {
			add_action('admin_notices', array($this, 'admin_notice'));
			add_action('admin_init', array($this, 'notice_nag_ignore'));
		}
	}

	function init()
	{		
		// if undefined get_plugins
		if(! function_exists('get_plugins')) 
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	/** 
	 * Get status of filtered plugins
	 */
	function get_status()
	{
		$this->status['not_installed'] = array();
		$this->status['not_activated'] = array();
		$this->status['is_notification'] = false;
		$all_plugins = array();

		// create array
		foreach ($this->get_plugins as $key => $value) {
			$all_plugins[$value['Name']] = $key;
		}

		// check all
		foreach ($this->recommended as $key => $value) 
		{
			$name = $value['name'];

			// installed but not activated
			if(isset($all_plugins[$name]) && ! is_plugin_active($all_plugins[$name])) {
				$this->status['not_activated'][] = $key;
			}
			// not installed
			else if(! isset($all_plugins[$name])) {
				$this->status['not_installed'][] = $key;
			}
		}

		// return true or false
		$count_not_activated = count($this->status['not_activated']);
		$count_not_installed = count($this->status['not_installed']);

		if($count_not_activated > 0 || $count_not_installed > 0) {
			$this->status['is_notification'] = true;
		}
	}

	/** 
	 * Get text for notification
	 */
	function get_text($type)
	{
		$status = $this->status[$type];
        $text = '';

        // generate a tag
        if(count($status) > 0)
        {
        	foreach ($status as $key => $value)
        	{
        		$text .= sprintf('<a href="%1$s" target="_blank">%2$s</a>, ',
        			$this->recommended[$value]['url'], $this->recommended[$value]['name']
        		);
        	}

        	$text = rtrim($text, ", \t\n");
        }

        return $text != '' ? $text : false;
	}

	/** 
	 * Display a notice that can be dismissed 
	 */
	function admin_notice() 
	{
	    global $current_user ;

	    $user_id = $current_user->ID;
	    $ignore_notice = get_user_meta($user_id, $this->prefix . '_ignore_notice', true);
	    $ignore_count_notice = get_user_meta($user_id, $this->prefix . '_ignore_count_notice', true);
	    $max_count_notice = 15;

	    // if usermeta(ignore_count_notice) is not exist
	    if($ignore_count_notice == "")
	    {
	        add_user_meta($user_id, $this->prefix . '_ignore_count_notice', $max_count_notice, true);

	        $ignore_count_notice = 0;
	    }

	    // display the notice or not
	    if($ignore_notice == 'forever')
	    {
	        $is_ignore_notice = true;
	    }
	    else if($ignore_notice == 'later' && $ignore_count_notice < $max_count_notice)
	    {
	        $is_ignore_notice = true;

	        update_user_meta($user_id, $this->prefix . '_ignore_count_notice', intval($ignore_count_notice) + 1);
	    }
	    else
	    {
	        $is_ignore_notice = false;
	    }

	    /* Check that the user hasn't already clicked to ignore the message & if premium not installed */
	    if(! $is_ignore_notice)
	    {
	        echo '<div class="updated" style="font-weight:bold;"><p>';

	        $not_installed = $this->get_text('not_installed');
	        $not_activated = $this->get_text('not_activated');

	        if($not_installed) {
	        	printf(__('<p>%1$s recommends the following plugin: %2$s</p>', $this->prefix), 
	        		$this->plugin_name, $not_installed);
	        }

	        if($not_activated) {
	        	printf(__('<p>%1$s recommended plugin is currently inactive: %2$s</p>', $this->prefix), 
	        		$this->plugin_name, $not_activated);
	        }

	        // notification message
	        printf(__('%1$s Do not bug me again %2$s Remind me later %3$s',$this->prefix), 
	            '<p style="margin-top:15px;"><a href="?' . $this->prefix . '_nag_ignore=forever" class="button" style="color:#a00;margin-right:10px;">', 
	            '</a><a href="?' . $this->prefix . '_nag_ignore=later" class="button">', '</a></p>');
	        
	        echo "</p></div>";
	    }
	}

	/** 
	 * Catch the notification's click actions
	 */
	function notice_nag_ignore() 
	{
	    global $current_user;
	    $user_id = $current_user->ID;

	    // If user clicks to ignore the notice, add that to their user meta
	    if (isset($_GET[$this->prefix . '_nag_ignore']) && $_GET[$this->prefix . '_nag_ignore'] == 'forever') 
	    {
	        update_user_meta($user_id, $this->prefix . '_ignore_notice', 'forever');        

	        // redirect
	        $this->redirect_action();
	    }
	    else if (isset($_GET[$this->prefix . '_nag_ignore']) && $_GET[$this->prefix . '_nag_ignore'] == 'later') 
	    {
	        update_user_meta($user_id, $this->prefix . '_ignore_notice', 'later');
	        update_user_meta($user_id, $this->prefix . '_ignore_count_notice', 0);

	        // redirect
	        $this->redirect_action();
	    }
	}

	/**
	 * Redirect actions
	 */
	function redirect_action()
	{
	    $location = admin_url("plugins.php");

	    wp_die(sprintf('%1$s <h1>Redirecting..</h1> <p>Click %2$shere%3$s if the auto redirecting is take a long time.</p>',
			"<meta http-equiv='refresh' content='0;url=$location' />",
			"<a href='$location'><strong>", "</strong></a>"));
	    exit();
	}
}

endif;